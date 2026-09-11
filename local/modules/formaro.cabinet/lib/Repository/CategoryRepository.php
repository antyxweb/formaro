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
            'ACTIVE' => ($payload['status'] ?? 'active') === 'hidden' ? 'N' : 'Y',
            'DESCRIPTION' => (string)($payload['short_desc'] ?? ''),
            'DESCRIPTION_TYPE' => 'text',
            'UF_FULL_DESC' => (string)($payload['full_desc'] ?? ''),
            'UF_PARTNER_ID' => $partnerId,
            'UF_IS_SYSTEM' => 0,
        ];

        $this->applyImage($fields, 'PICTURE', $payload['preview_image'] ?? null, $existing['preview_image'] ?? null);
        $this->applyImage($fields, 'UF_FULL_IMAGE', $payload['full_image'] ?? null, $existing['full_image'] ?? null);

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

        return $this->get($id);
    }

    /** @throws \Exception если запись чужая/системная, есть подкатегории или её нельзя удалить */
    public function delete(int $partnerId, int $id): bool
    {
        $row = $this->get($id);
        if (!$row || !$this->canEdit($partnerId, $row)) {
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

        return CIBlockSection::Delete($id);
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
            'status' => $section['ACTIVE'] === 'N' ? 'hidden' : 'active',
        ];
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
