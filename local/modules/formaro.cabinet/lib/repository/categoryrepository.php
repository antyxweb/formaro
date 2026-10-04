<?php

namespace Formaro\Cabinet\Repository;

use Bitrix\Main\Loader;
use CIBlock;
use CIBlockSection;
use Formaro\Cabinet\Upload\FileUploader;

/**
 * Категории кабинета = разделы инфоблока cabinet_catalog. Системные —
 * UF_IS_SYSTEM=1, без UF_PARTNER_ID, общие для всех, редактировать нельзя.
 * Партнёрские — UF_PARTNER_ID = ELEMENT_ID партнёра. Ровно тот же принцип
 * видимости/прав, что был в cabinet-html/assets/js/common.js
 * (isCategoryVisible/canEditCategory), только теперь проверяется и на
 * сервере — фронтенд может показывать/скрывать кнопки, но не может обойти
 * проверку владения, отправив чужой id напрямую в AJAX.
 *
 * Все методы возвращают/принимают массивы В ТОМ ЖЕ формате, что и старый
 * data/categories.json прототипа (id/parent_id/name/slug/...) — так JS
 * (categories.js/category-detail.js), написанный под тот формат, работает
 * без переделки, только источник данных сменился с localStorage на AJAX.
 */
class CategoryRepository
{
    private const IBLOCK_CODE = 'cabinet_catalog';
    private const IBLOCK_TYPE = 'catalog';
    private const UPLOAD_SUBDIR = 'cabinet/catalog';

    private int $iblockId;

    public function __construct()
    {
        Loader::includeModule('iblock');
        $this->iblockId = $this->resolveIblockId();
    }

    public function listAll(): array
    {
        $rows = [];
        $res = CIBlockSection::GetList(
            ['SORT' => 'ASC'],
            ['IBLOCK_ID' => $this->iblockId, 'CHECK_PERMISSIONS' => 'N'],
            false,
            ['*', 'UF_*']
        );
        while ($section = $res->Fetch()) {
            $rows[] = $this->toArray($section);
        }

        return $rows;
    }

    /** Системные + свои — без чужих партнёрских (как visibleCategories() в прототипе) */
    public function listVisible(int $partnerId): array
    {
        return array_values(array_filter(
            $this->listAll(),
            static fn(array $c) => $c['is_system'] || $c['partner_id'] === $partnerId
        ));
    }

    public function get(int $id): ?array
    {
        $section = CIBlockSection::GetList(
            [],
            ['IBLOCK_ID' => $this->iblockId, 'ID' => $id, 'CHECK_PERMISSIONS' => 'N'],
            false,
            ['*', 'UF_*']
        )->Fetch();

        return $section ? $this->toArray($section) : null;
    }

    public function canEdit(int $partnerId, array $row): bool
    {
        return !$row['is_system'] && $row['partner_id'] === $partnerId;
    }

    /**
     * @param array $payload id?/parent_id/name/slug/short_desc/full_desc/
     *                       preview_image/full_image/status
     * @throws \Exception если запись существует, но принадлежит не этому партнёру
     */
    public function save(int $partnerId, array $payload): array
    {
        $id = (int)($payload['id'] ?? 0);
        $existing = $id ? $this->get($id) : null;

        // Владение проверяем и на существование, и на партнёра — если id прислали,
        // а строки с таким id нет ИЛИ она принадлежит другому партнёру/системная,
        // это не "обновление чужого", а создание новой записи (см. FileUploader —
        // клиент присылает id ещё до реального сохранения, см. common.js dsNextId).
        if ($existing && !$this->canEdit($partnerId, $existing)) {
            $existing = null;
            $id = 0;
        }

        $fields = [
            'IBLOCK_ID' => $this->iblockId,
            'IBLOCK_SECTION_ID' => (int)($payload['parent_id'] ?? 0) ?: false,
            'NAME' => (string)($payload['name'] ?? ''),
            'CODE' => (string)($payload['slug'] ?? ''),
            'SORT' => (int)($payload['sort_order'] ?? 500),
            'DESCRIPTION' => (string)($payload['short_desc'] ?? ''),
            'DESCRIPTION_TYPE' => 'text',
            'UF_FULL_DESC' => (string)($payload['full_desc'] ?? ''),
            'UF_PARTNER_ID' => $partnerId,
            'UF_IS_SYSTEM' => 0,
        ];

        // 'status' отсутствует в payload, пока категория на проверке — поле
        // "Статус" на клиенте задизейблено (см. category-detail.js,
        // lockStatusPending()). Для уже существующей записи в этом случае
        // просто не трогаем ACTIVE, чтобы не затереть его дефолтом; для
        // новой — как и раньше, партнёр по умолчанию активен.
        if (array_key_exists('status', $payload)) {
            $fields['ACTIVE'] = $payload['status'] === 'hidden' ? 'N' : 'Y';
        } elseif (!$existing) {
            $fields['ACTIVE'] = 'Y';
        }

        $this->applyImage($fields, 'PICTURE', $payload['preview_image'] ?? null, $existing['preview_image'] ?? null);
        $this->applyImage($fields, 'UF_FULL_IMAGE', $payload['full_image'] ?? null, $existing['full_image'] ?? null);

        // Ещё не допущенная (новая, на проверке или отклонённая) категория
        // после правки партнёра снова уходит на проверку — причина прежнего
        // отклонения больше не актуальна, администратору — уведомление.
        $toModeration = !$existing || in_array($existing['status'], ['pending', 'rejected'], true);
        if ($toModeration) {
            $fields['UF_REJECT_REASON'] = '';
        }

        if ($existing) {
            $ok = (new CIBlockSection())->Update($id, $fields);
            if (!$ok) {
                throw new \RuntimeException('Не удалось сохранить категорию (ID=' . $id . ')');
            }
        } else {
            $section = new CIBlockSection();
            $id = $section->Add($fields);
            if (!$id) {
                throw new \RuntimeException('Не удалось создать категорию: ' . $section->LAST_ERROR);
            }
        }

        if ($toModeration) {
            NotificationRepository::notifyAdmin(
                'moderation',
                $existing ? 'Категория партнёра изменена' : 'Новая категория партнёра',
                PartnerRepository::nameOf($partnerId) . ' — категория «' . $fields['NAME'] . '» ждёт проверки.',
                'categories/edit/' . $id . '/'
            );
        }

        return $this->get($id);
    }

    /**
     * Сохранение из кабинета администратора: любая категория — общая
     * (системная) или партнёрская. Новая без партнёра — общая категория
     * площадки, сразу допущенная к показу. Владельца существующей категории
     * администратор не меняет; допуск — approve()/reject().
     */
    public function adminSave(array $payload): array
    {
        $id = (int)($payload['id'] ?? 0);
        $existing = $id ? $this->get($id) : null;

        $fields = [
            'IBLOCK_ID' => $this->iblockId,
            'IBLOCK_SECTION_ID' => (int)($payload['parent_id'] ?? 0) ?: false,
            'NAME' => (string)($payload['name'] ?? ''),
            'CODE' => (string)($payload['slug'] ?? ''),
            'DESCRIPTION' => (string)($payload['short_desc'] ?? ''),
            'DESCRIPTION_TYPE' => 'text',
            'UF_FULL_DESC' => (string)($payload['full_desc'] ?? ''),
            'ACTIVE' => ($payload['active'] ?? true) ? 'Y' : 'N',
        ];
        if (isset($payload['sort_order']) || !$existing) {
            $fields['SORT'] = (int)($payload['sort_order'] ?? 500);
        }
        if (!$existing) {
            $partnerId = (int)($payload['partner_id'] ?? 0);
            $fields['UF_PARTNER_ID'] = $partnerId ?: false;
            $fields['UF_IS_SYSTEM'] = $partnerId ? 0 : 1;
            $fields['UF_APPROVED'] = 1;
        }

        $this->applyImage($fields, 'PICTURE', $payload['preview_image'] ?? null, $existing['preview_image'] ?? null);
        $this->applyImage($fields, 'UF_FULL_IMAGE', $payload['full_image'] ?? null, $existing['full_image'] ?? null);

        $section = new CIBlockSection();
        if ($existing) {
            if (!$section->Update($id, $fields)) {
                throw new \RuntimeException('Не удалось сохранить категорию: ' . $section->LAST_ERROR);
            }
        } else {
            $id = (int)$section->Add($fields);
            if (!$id) {
                throw new \RuntimeException('Не удалось создать категорию: ' . $section->LAST_ERROR);
            }
        }

        return $this->get($id);
    }

    /** Удаление из кабинета администратора — те же проверки, что у партнёра, без проверки владельца. */
    public function adminDelete(int $id): bool
    {
        $row = $this->get($id);
        if (!$row) {
            throw new \RuntimeException('Категория не найдена');
        }

        return $this->delete((int)$row['partner_id'], $id, true);
    }

    /** Допустить категорию партнёра к показу на сайте. */
    public function approve(int $id): array
    {
        $row = $this->requireRow($id);
        (new CIBlockSection())->Update($id, ['UF_APPROVED' => 1, 'UF_REJECT_REASON' => '']);
        if ($row['partner_id']) {
            (new NotificationRepository())->add(
                $row['partner_id'],
                'product',
                'Категория одобрена',
                'Категория «' . $row['name'] . '» прошла проверку и видна на сайте.',
                'categories/edit/' . $id . '/'
            );
        }

        return $this->get($id);
    }

    /** Отклонить категорию партнёра с причиной (партнёр поправит и отправит снова). */
    public function reject(int $id, string $reason): array
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new \RuntimeException('Укажите причину отклонения');
        }
        $row = $this->requireRow($id);
        (new CIBlockSection())->Update($id, ['UF_APPROVED' => 0, 'UF_REJECT_REASON' => $reason]);
        if ($row['partner_id']) {
            (new NotificationRepository())->add(
                $row['partner_id'],
                'product',
                'Категория отклонена',
                'Категория «' . $row['name'] . '» не прошла проверку. Причина: ' . $reason,
                'categories/edit/' . $id . '/'
            );
        }

        return $this->get($id);
    }

    private function requireRow(int $id): array
    {
        $row = $this->get($id);
        if (!$row) {
            throw new \RuntimeException('Категория не найдена');
        }

        return $row;
    }

    /** @throws \Exception если запись чужая/системная, есть подкатегории или её нельзя удалить */
    public function delete(int $partnerId, int $id, bool $asAdmin = false): bool
    {
        $row = $this->get($id);
        if (!$row || (!$asAdmin && !$this->canEdit($partnerId, $row))) {
            throw new \RuntimeException('Категория не найдена или недоступна для удаления');
        }

        $hasChildren = (bool)CIBlockSection::GetList(
            [],
            ['IBLOCK_ID' => $this->iblockId, 'SECTION_ID' => $id, 'CHECK_PERMISSIONS' => 'N'],
            false,
            ['ID']
        )->Fetch();
        if ($hasChildren) {
            throw new \RuntimeException('Нельзя удалить категорию — в ней есть подкатегории');
        }

        $hasProducts = (bool)\CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $this->iblockId, 'SECTION_ID' => $id, 'CHECK_PERMISSIONS' => 'N', 'INCLUDE_SUBSECTIONS' => 'N'],
            false,
            false,
            ['ID']
        )->Fetch();
        if ($hasProducts) {
            throw new \RuntimeException('Нельзя удалить категорию — в ней есть товары');
        }

        $this->deleteImage($row['preview_image_file_id'] ?? null);
        $this->deleteImage($row['full_image_file_id'] ?? null);

        // CIBlockSection::Delete() не имеет типизированной сигнатуры и на
        // успешном пути реально возвращает CDBResult (не bool) — приводим
        // явно, иначе PHP 8 падает с TypeError на объявленном ": bool" тут.
        return (bool)CIBlockSection::Delete($id);
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

    private function toArray(array $section): array
    {
        // Статус "На проверке" — не сам ACTIVE (это выбор партнёра, показывать
        // категорию или нет), а отдельное поле UF_APPROVED (см.
        // Version20260923150001): пока администратор площадки не поставил
        // галочку "Допущен к показу" на разделе в админке, в кабинете вместо
        // Активен/Скрыт всегда показываем "На проверке".
        $approved = !empty($section['UF_APPROVED']);
        $rejectReason = trim((string)($section['UF_REJECT_REASON'] ?? ''));
        $moderation = $rejectReason !== '' ? 'rejected' : 'pending';

        return [
            'id' => (int)$section['ID'],
            'parent_id' => (int)$section['IBLOCK_SECTION_ID'],
            'sort_order' => (int)$section['SORT'],
            'name' => $section['NAME'],
            'slug' => $section['CODE'],
            'short_desc' => $section['DESCRIPTION'],
            'full_desc' => $section['UF_FULL_DESC'] ?? '',
            'preview_image' => FileUploader::getPath($section['PICTURE'] ?: null),
            'preview_image_file_id' => $section['PICTURE'] ?: null,
            'full_image' => FileUploader::getPath($section['UF_FULL_IMAGE'] ?: null),
            'full_image_file_id' => $section['UF_FULL_IMAGE'] ?: null,
            'is_system' => !empty($section['UF_IS_SYSTEM']),
            'partner_id' => $section['UF_PARTNER_ID'] ? (int)$section['UF_PARTNER_ID'] : null,
            'status' => !$approved ? $moderation : ($section['ACTIVE'] === 'N' ? 'hidden' : 'active'),
            'active' => $section['ACTIVE'] !== 'N',
            'approved' => $approved,
            'reject_reason' => $rejectReason,
            'public_url' => $this->getPublicUrl($section),
        ];
    }

    /** Публичная ссылка раздела по ЧПУ инфоблока (SECTION_PAGE_URL) — для
     *  кнопки "скопировать ссылку" на форме категории. Относительный путь
     *  без домена (домен подставляет клиент из window.location.origin) —
     *  так работает одинаково и на деве, и на проде. Пусто, если у
     *  инфоблока вообще не настроен SECTION_PAGE_URL (нет публичной страницы). */
    private function getPublicUrl(array $section): string
    {
        $iblock = CIBlock::GetArrayByID($this->iblockId);
        $template = (string)($iblock['SECTION_PAGE_URL'] ?? '');
        if ($template === '') {
            return '';
        }

        return CIBlock::ReplaceDetailUrl($template, [
            'ID' => (int)$section['ID'],
            'CODE' => $section['CODE'],
        ], false, 'S');
    }

    /**
     * @param array $fields ссылкой — сюда пишем итоговое значение поля с картинкой
     * @param string|null $newValue то, что прислал клиент: URL без изменений,
     *                              новая data:-строка, или '' (удалить картинку)
     * @param string|null $oldValue текущее значение (URL), чтобы понять, менялось ли поле
     */
    private function applyImage(array &$fields, string $fieldCode, ?string $newValue, ?string $oldValue): void
    {
        if ($newValue === null || $newValue === $oldValue) {
            return; // поле не трогали — не переписываем существующий файл
        }

        if ($newValue === '') {
            $fields[$fieldCode] = false; // очистить картинку
            return;
        }

        $fileId = FileUploader::saveFromDataUrl($newValue, self::UPLOAD_SUBDIR);
        if ($fileId) {
            $fields[$fieldCode] = $fileId;
        }
        // если newValue — обычный URL (не data:, например уже сохранённый путь,
        // клиент его не менял и просто прислал обратно) — сюда не попадём
        // благодаря проверке $newValue === $oldValue выше в частых случаях;
        // если же URL всё-таки другой, но не data: — просто игнорируем правку.
    }

    private function deleteImage($fileId): void
    {
        if ($fileId) {
            FileUploader::delete((int)$fileId);
        }
    }
}
