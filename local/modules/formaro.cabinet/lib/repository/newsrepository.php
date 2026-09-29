<?php

namespace Formaro\Cabinet\Repository;

use Bitrix\Main\Loader;
use CIBlock;
use CIBlockElement;
use CIBlockSection;
use Formaro\Cabinet\Upload\FileUploader;

/**
 * Новости кабинета = элементы инфоблока cabinet_news (тип content). Раздел
 * один на всех партнёров — "partners" (код partners, см. миграцию
 * Version20260911193003), свойство PARTNER_ID на элементе разруливает "чьё
 * это" (в отличие от категорий, здесь нет системных/общих новостей — все
 * новости партнёрские). Формат массивов совпадает с cabinet-html/data/
 * news.json, чтобы news.js/news-detail.js работали без переделки.
 */
class NewsRepository
{
    private const IBLOCK_CODE = 'cabinet_news';
    private const IBLOCK_TYPE = 'content';
    private const SECTION_CODE = 'partners';
    private const UPLOAD_SUBDIR = 'cabinet/news';

    /** См. ProductRepository::SELECT_FIELDS — 'PROPERTY_*' отдаёт значения
     *  по ID свойства, а не по коду, который читает toArray(). DETAIL_PICTURE
     *  ("детальная картинка") — такое же нативное поле элемента, как
     *  PREVIEW_PICTURE, отдельного свойства заводить не нужно. */
    private const SELECT_FIELDS = ['*', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'PROPERTY_PARTNER_ID'];

    private int $iblockId;
    private int $sectionId;

    public function __construct()
    {
        Loader::includeModule('iblock');
        $this->iblockId = $this->resolveIblockId();
        $this->sectionId = $this->resolveSectionId();
    }

    public function listOwn(int $partnerId): array
    {
        $rows = [];
        $res = CIBlockElement::GetList(
            ['ID' => 'DESC'],
            ['IBLOCK_ID' => $this->iblockId, 'PROPERTY_PARTNER_ID' => $partnerId, 'CHECK_PERMISSIONS' => 'N'],
            false,
            false,
            self::SELECT_FIELDS
        );
        while ($el = $res->Fetch()) {
            $rows[] = $this->toArray($el);
        }

        return $this->attachCatalogLinks($rows);
    }

    public function get(int $id): ?array
    {
        $el = CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $this->iblockId, 'ID' => $id, 'CHECK_PERMISSIONS' => 'N'],
            false,
            false,
            self::SELECT_FIELDS
        )->Fetch();

        return $el ? $this->attachCatalogLinks([$this->toArray($el)])[0] : null;
    }

    public function canEdit(int $partnerId, array $row): bool
    {
        return $row['partner_id'] === $partnerId;
    }

    /**
     * @param array $payload id?/title/slug/short_desc/full_desc/image/
     *                       created_at/status/catalog_section_ids/
     *                       catalog_product_ids
     */
    public function save(int $partnerId, array $payload): array
    {
        $id = (int)($payload['id'] ?? 0);
        $existing = $id ? $this->get($id) : null;

        // См. CategoryRepository::save() — тот же "не найдено/не моё => это создание" разбор
        if ($existing && !$this->canEdit($partnerId, $existing)) {
            $existing = null;
            $id = 0;
        }

        $fields = [
            'IBLOCK_ID' => $this->iblockId,
            'IBLOCK_SECTION_ID' => $this->sectionId,
            'NAME' => (string)($payload['title'] ?? ''),
            'CODE' => (string)($payload['slug'] ?? ''),
            'ACTIVE' => ($payload['status'] ?? 'active') === 'hidden' ? 'N' : 'Y',
            'PREVIEW_TEXT' => (string)($payload['short_desc'] ?? ''),
            'DETAIL_TEXT' => (string)($payload['full_desc'] ?? ''),
            // full_desc приходит из trumbowyg (news-detail.js) — реальный HTML
            // (<p>, <b>...), не голый текст. Без DETAIL_TEXT_TYPE='html'
            // Bitrix трактует поле как обычный текст и экранирует теги на
            // публичной странице (bitrix:news.detail) — там же обнаружилось
            // при переключении /news/ на этот инфоблок: заголовки/абзацы
            // показывались как видимый текст "<p>...</p>" вместо разметки.
            'DETAIL_TEXT_TYPE' => 'html',
        ];
        if (!empty($payload['created_at'])) {
            // ACTIVE_FROM ("Начало активности") — нативное поле инфоблока
            // именно под дату публикации (в отличие от DATE_CREATE, которое
            // должно отражать момент реального создания записи, а не
            // назначаемую партнёром дату) — Bitrix сам использует его для
            // CHECK_DATES/ACTIVE_FROM-фильтрации при выводе. Принимает
            // строку в формате сайта через ConvertTimeStamp(), как и
            // DATE_CREATE. Партнёр может назначить и будущую дату (см.
            // news-detail.js: date-picker без верхней границы, minDate: today).
            $ts = strtotime((string)$payload['created_at']);
            if ($ts) {
                $fields['ACTIVE_FROM'] = ConvertTimeStamp($ts, 'FULL');
            }
        }

        $this->applyImage($fields, 'PREVIEW_PICTURE', $payload['image'] ?? null, $existing['image'] ?? null);
        $this->applyImage($fields, 'DETAIL_PICTURE', $payload['full_image'] ?? null, $existing['full_image'] ?? null);

        if ($existing) {
            $ok = (new CIBlockElement())->Update($id, $fields);
            if (!$ok) {
                throw new \RuntimeException('Не удалось сохранить новость (ID=' . $id . ')');
            }
        } else {
            $element = new CIBlockElement();
            $id = $element->Add($fields);
            if (!$id) {
                throw new \RuntimeException('Не удалось создать новость: ' . $element->LAST_ERROR);
            }
        }

        $props = ['PARTNER_ID' => $partnerId];
        // Вкладка «Каталог»: ключи есть в payload — значит форма их прислала
        // (пустой массив = отвязать всё); нет ключа — не трогаем.
        if (array_key_exists('catalog_section_ids', $payload)) {
            $sectionIds = $this->filterVisibleSections($partnerId, (array)$payload['catalog_section_ids']);
            $props['CATALOG_SECTIONS'] = $sectionIds ?: false;
        }
        if (array_key_exists('catalog_product_ids', $payload)) {
            $productIds = $this->filterOwnProducts($partnerId, (array)$payload['catalog_product_ids']);
            $props['CATALOG_PRODUCTS'] = $productIds ?: false;
        }
        CIBlockElement::SetPropertyValuesEx($id, $this->iblockId, $props);

        return $this->get($id);
    }

    /** @throws \Exception если новость чужая/не найдена */
    public function delete(int $partnerId, int $id): bool
    {
        $row = $this->get($id);
        if (!$row || !$this->canEdit($partnerId, $row)) {
            throw new \RuntimeException('Новость не найдена или недоступна для удаления');
        }

        FileUploader::delete($row['image_file_id'] ?? null);
        FileUploader::delete($row['full_image_file_id'] ?? null);

        // См. CategoryRepository::delete() — ::Delete() без типа возврата
        // в самом ядре, приводим явно к bool под наше ": bool".
        return (bool)CIBlockElement::Delete($id);
    }

    /**
     * Привязка к каталогу (CATALOG_SECTIONS/CATALOG_PRODUCTS, миграция
     * Version20260929140001). Множественные свойства читаем отдельно:
     * 'PROPERTY_*' в select GetList размножил бы строки новости по числу
     * значений.
     */
    private function attachCatalogLinks(array $rows): array
    {
        if (!$rows) {
            return $rows;
        }

        $values = [];
        foreach ($rows as $row) {
            $values[$row['id']] = [];
        }
        CIBlockElement::GetPropertyValuesArray(
            $values,
            $this->iblockId,
            ['ID' => array_keys($values)],
            ['CODE' => ['CATALOG_SECTIONS', 'CATALOG_PRODUCTS']]
        );

        $toIds = static fn($prop) => array_values(array_filter(array_map('intval', (array)($prop['VALUE'] ?? []))));
        foreach ($rows as &$row) {
            $props = $values[$row['id']] ?? [];
            $row['catalog_section_ids'] = $toIds($props['CATALOG_SECTIONS'] ?? []);
            $row['catalog_product_ids'] = $toIds($props['CATALOG_PRODUCTS'] ?? []);
        }
        unset($row);

        return $rows;
    }

    /** Только категории, которые партнёр видит в кабинете (системные и свои). */
    private function filterVisibleSections(int $partnerId, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) {
            return [];
        }
        $visible = array_column((new CategoryRepository())->listVisible($partnerId), 'id');

        return array_values(array_intersect($ids, array_map('intval', $visible)));
    }

    /** Только свои товары партнёра — к чужим привязывать нельзя. */
    private function filterOwnProducts(int $partnerId, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) {
            return [];
        }

        $own = [];
        $res = CIBlockElement::GetList(
            [],
            ['IBLOCK_CODE' => 'cabinet_catalog', 'ID' => $ids, 'PROPERTY_PARTNER_ID' => $partnerId, 'CHECK_PERMISSIONS' => 'N'],
            false,
            false,
            ['ID']
        );
        while ($row = $res->Fetch()) {
            $own[(int)$row['ID']] = true;
        }

        return array_values(array_filter($ids, static fn(int $id) => isset($own[$id])));
    }

    private function resolveIblockId(): int
    {
        $iblock = CIBlock::GetList([], [
            'CODE' => self::IBLOCK_CODE,
            'TYPE' => self::IBLOCK_TYPE,
            'CHECK_PERMISSIONS' => 'N',
        ])->Fetch();

        if (!$iblock) {
            throw new \RuntimeException(
                'Инфоблок "' . self::IBLOCK_CODE . '" не найден — прогнаны ли миграции модуля formaro.cabinet?'
            );
        }

        return (int)$iblock['ID'];
    }

    private function resolveSectionId(): int
    {
        $section = CIBlockSection::GetList(
            [],
            ['IBLOCK_ID' => $this->iblockId, 'CODE' => self::SECTION_CODE, 'CHECK_PERMISSIONS' => 'N'],
            false,
            ['ID']
        )->Fetch();

        if (!$section) {
            throw new \RuntimeException(
                'Раздел "' . self::SECTION_CODE . '" не найден в инфоблоке "' . self::IBLOCK_CODE . '"'
            );
        }

        return (int)$section['ID'];
    }

    private function toArray(array $el): array
    {
        return [
            'id' => (int)$el['ID'],
            'title' => $el['NAME'],
            'slug' => $el['CODE'],
            'short_desc' => $el['PREVIEW_TEXT'] ?? '',
            'full_desc' => $el['DETAIL_TEXT'] ?? '',
            'image' => FileUploader::getPath($el['PREVIEW_PICTURE'] ?: null),
            'image_file_id' => $el['PREVIEW_PICTURE'] ?: null,
            'full_image' => FileUploader::getPath($el['DETAIL_PICTURE'] ?: null),
            'full_image_file_id' => $el['DETAIL_PICTURE'] ?: null,
            'partner_id' => (int)($el['PROPERTY_PARTNER_ID_VALUE'] ?? 0),
            'status' => $el['ACTIVE'] === 'N' ? 'hidden' : 'active',
            'created_at' => $el['ACTIVE_FROM'] ?? null,
            'public_url' => $this->getPublicUrl($el),
        ];
    }

    /** См. CategoryRepository::getPublicUrl() — тот же принцип (страница
     *  новости на сайте — /news/, DETAIL_PAGE_URL инфоблока cabinet_news);
     *  нужна кнопкам «скопировать ссылку» и «Предпросмотр» в форме новости. */
    private function getPublicUrl(array $el): string
    {
        $iblock = CIBlock::GetArrayByID($this->iblockId);
        $template = (string)($iblock['DETAIL_PAGE_URL'] ?? '');
        if ($template === '') {
            return '';
        }

        return CIBlock::ReplaceDetailUrl($template, [
            'ID' => (int)$el['ID'],
            'CODE' => $el['CODE'],
            'IBLOCK_SECTION_ID' => (int)($el['IBLOCK_SECTION_ID'] ?? 0),
        ], false, 'E');
    }

    /** PREVIEW_PICTURE/DETAIL_PICTURE — нативные поля элемента; требуют
     *  $_FILES-подобный массив, не голый ID файла (см. докблок
     *  FileUploader::dataUrlToFileArray()). Для явной очистки — тоже массив,
     *  ['del' => 'Y']: CIBlockElement::Update() (classes/mysql/iblockelement.php)
     *  обрабатывает эти поля только если is_array($arFields[$fieldCode]) —
     *  false/''/0 не проходят эту проверку и молча игнорируются, картинка
     *  остаётся как была (проверено вручную — баг найден именно на этом шаге). */
    private function applyImage(array &$fields, string $fieldCode, ?string $newValue, ?string $oldValue): void
    {
        if ($newValue === null || $newValue === $oldValue) {
            return;
        }
        if ($newValue === '') {
            $fields[$fieldCode] = ['del' => 'Y'];
            return;
        }
        $fileArray = FileUploader::dataUrlToFileArray($newValue);
        if ($fileArray) {
            $fields[$fieldCode] = $fileArray;
        }
    }
}
