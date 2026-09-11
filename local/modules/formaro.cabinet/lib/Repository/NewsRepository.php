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
            ['*', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'PROPERTY_*']
        );
        while ($el = $res->Fetch()) {
            $rows[] = $this->toArray($el);
        }

        return $rows;
    }

    public function get(int $id): ?array
    {
        $el = CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $this->iblockId, 'ID' => $id, 'CHECK_PERMISSIONS' => 'N'],
            false,
            false,
            ['*', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'PROPERTY_*']
        )->Fetch();

        return $el ? $this->toArray($el) : null;
    }

    public function canEdit(int $partnerId, array $row): bool
    {
        return $row['partner_id'] === $partnerId;
    }

    /**
     * @param array $payload id?/title/slug/short_desc/full_desc/image/
     *                       created_at/status
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
        ];
        if (!empty($payload['created_at'])) {
            // DATE_CREATE — обычное поле таблицы, CIBlockElement::Add()/Update()
            // принимают его как строку в формате сайта (см. ConvertTimeStamp() —
            // стандартный способ получить такую строку из timestamp в старом API
            // инфоблоков). Используем это поле как "дату публикации" вместо
            // отдельного UF-поля — партнёр может назначить и будущую дату (см.
            // news-detail.js: date-picker без верхней границы, minDate: today).
            $ts = strtotime((string)$payload['created_at']);
            if ($ts) {
                $fields['DATE_CREATE'] = ConvertTimeStamp($ts, 'FULL');
            }
        }

        $this->applyImage($fields, $payload['image'] ?? null, $existing['image'] ?? null);

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

        CIBlockElement::SetPropertyValuesEx($id, $this->iblockId, [
            'PARTNER_ID' => $partnerId,
        ]);

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

        return CIBlockElement::Delete($id);
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
            'partner_id' => (int)($el['PROPERTY_PARTNER_ID_VALUE'] ?? 0),
            'status' => $el['ACTIVE'] === 'N' ? 'hidden' : 'active',
            'created_at' => $el['DATE_CREATE'] ?? null,
        ];
    }

    private function applyImage(array &$fields, ?string $newValue, ?string $oldValue): void
    {
        if ($newValue === null || $newValue === $oldValue) {
            return;
        }
        if ($newValue === '') {
            $fields['PREVIEW_PICTURE'] = false;
            return;
        }
        $fileId = FileUploader::saveFromDataUrl($newValue, self::UPLOAD_SUBDIR);
        if ($fileId) {
            $fields['PREVIEW_PICTURE'] = $fileId;
        }
    }
}
