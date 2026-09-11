<?php

namespace Formaro\Cabinet\Repository;

use Bitrix\Main\Loader;
use CIBlock;
use CIBlockElement;
use Formaro\Cabinet\Upload\FileUploader;

/**
 * Товары кабинета = элементы инфоблока cabinet_catalog (тот же инфоблок,
 * что и категории — категория это раздел, товар это элемент). Формат
 * возвращаемых/принимаемых массивов совпадает с cabinet-html/data/
 * products.json, чтобы products.js/product-detail.js работали без
 * переделки (см. CategoryRepository — тот же принцип).
 */
class ProductRepository
{
    private const IBLOCK_CODE = 'cabinet_catalog';
    private const IBLOCK_TYPE = 'catalog';
    private const UPLOAD_SUBDIR = 'cabinet/products';

    private int $iblockId;

    public function __construct()
    {
        Loader::includeModule('iblock');
        $this->iblockId = $this->resolveIblockId();
    }

    /** @return array все товары партнёра (ownOnly() в прототипе делал это на клиенте) */
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
     * @param array $payload id?/name/slug/short_desc/full_desc/preview_image/
     *                       gallery[]/sku/color/size/price/stock/custom_props[]/
     *                       is_preorder/tags[]/variant_group_id/category_ids[]/status
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
            'NAME' => (string)($payload['name'] ?? ''),
            'CODE' => (string)($payload['slug'] ?? ''),
            'ACTIVE' => ($payload['status'] ?? 'active') === 'hidden' ? 'N' : 'Y',
            'PREVIEW_TEXT' => (string)($payload['short_desc'] ?? ''),
            'DETAIL_TEXT' => (string)($payload['full_desc'] ?? ''),
        ];

        $this->applyPreviewImage($fields, $payload['preview_image'] ?? null, $existing['preview_image'] ?? null);

        if ($existing) {
            $ok = (new CIBlockElement())->Update($id, $fields);
            if (!$ok) {
                throw new \RuntimeException('Не удалось сохранить товар (ID=' . $id . ')');
            }
        } else {
            $element = new CIBlockElement();
            $id = $element->Add($fields);
            if (!$id) {
                throw new \RuntimeException('Не удалось создать товар: ' . $element->LAST_ERROR);
            }
        }

        $customProps = array_values(array_filter((array)($payload['custom_props'] ?? []), static function ($p) {
            return !empty($p['name']) && !empty($p['value']);
        }));

        $galleryFileIds = $this->applyGallery(
            (array)($payload['gallery'] ?? []),
            (array)($existing['gallery_file_ids'] ?? [])
        );

        CIBlockElement::SetPropertyValuesEx($id, $this->iblockId, [
            'SKU' => (string)($payload['sku'] ?? ''),
            'COLOR' => (string)($payload['color'] ?? ''),
            'SIZE' => (string)($payload['size'] ?? ''),
            'PRICE' => (float)($payload['price'] ?? 0),
            'STOCK' => (int)($payload['stock'] ?? 0),
            'GALLERY' => $galleryFileIds,
            'CUSTOM_PROPS_JSON' => $customProps ? json_encode($customProps, JSON_UNESCAPED_UNICODE) : '',
            'IS_PREORDER' => !empty($payload['is_preorder']) ? 'Y' : 'N',
            'TAGS' => array_values(array_filter((array)($payload['tags'] ?? []))),
            'VARIANT_GROUP_ID' => (int)($payload['variant_group_id'] ?? 0),
            'PARTNER_ID' => $partnerId,
        ]);

        CIBlockElement::SetElementSection($id, array_map('intval', (array)($payload['category_ids'] ?? [])));

        return $this->get($id);
    }

    /** @throws \Exception если товар чужой/не найден */
    public function delete(int $partnerId, int $id): bool
    {
        $row = $this->get($id);
        if (!$row || !$this->canEdit($partnerId, $row)) {
            throw new \RuntimeException('Товар не найден или недоступен для удаления');
        }

        FileUploader::delete($row['preview_image_file_id'] ?? null);
        foreach ((array)($row['gallery_file_ids'] ?? []) as $fileId) {
            FileUploader::delete($fileId);
        }

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

    private function toArray(array $el): array
    {
        $galleryFileIds = array_values(array_filter((array)($el['PROPERTY_GALLERY_VALUE'] ?? [])));
        $customProps = json_decode((string)($el['PROPERTY_CUSTOM_PROPS_JSON_VALUE'] ?? ''), true);
        $tags = array_values(array_filter((array)($el['PROPERTY_TAGS_VALUE'] ?? [])));

        return [
            'id' => (int)$el['ID'],
            'name' => $el['NAME'],
            'slug' => $el['CODE'],
            'short_desc' => $el['PREVIEW_TEXT'] ?? '',
            'full_desc' => $el['DETAIL_TEXT'] ?? '',
            'preview_image' => FileUploader::getPath($el['PREVIEW_PICTURE'] ?: null),
            'preview_image_file_id' => $el['PREVIEW_PICTURE'] ?: null,
            'gallery' => array_map(static fn($fid) => FileUploader::getPath($fid), $galleryFileIds),
            'gallery_file_ids' => $galleryFileIds,
            'sku' => $el['PROPERTY_SKU_VALUE'] ?? '',
            'color' => $el['PROPERTY_COLOR_VALUE'] ?? '',
            'size' => $el['PROPERTY_SIZE_VALUE'] ?? '',
            'price' => (float)($el['PROPERTY_PRICE_VALUE'] ?? 0),
            'stock' => (int)($el['PROPERTY_STOCK_VALUE'] ?? 0),
            'custom_props' => is_array($customProps) ? $customProps : [],
            'is_preorder' => ($el['PROPERTY_IS_PREORDER_VALUE'] ?? 'N') === 'Y',
            'tags' => $tags,
            'variant_group_id' => (int)($el['PROPERTY_VARIANT_GROUP_ID_VALUE'] ?? 0),
            'partner_id' => (int)($el['PROPERTY_PARTNER_ID_VALUE'] ?? 0),
            'category_ids' => array_map('intval', CIBlockElement::GetElementGroups($el['ID'], true)),
            'status' => $el['ACTIVE'] === 'N' ? 'hidden' : 'active',
            'created_at' => $el['DATE_CREATE'] ?? null,
        ];
    }

    private function applyPreviewImage(array &$fields, ?string $newValue, ?string $oldValue): void
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

    /**
     * Галерея — просто пришедший от клиента список: уже загруженные картинки
     * приходят как data:-URL из уже сохранённого файла (клиент их не трогал)
     * ИЛИ как новые data:-URL. Проще всего для MVP считать: если строка не
     * похожа на data:-URL — это уже реальный сохранённый путь (сопоставляем
     * по позиции со старым списком id), если похожа — заливаем как новый файл.
     * Файлы, которых не стало в новом списке, удаляем.
     *
     * @return int[] финальный список file id для свойства GALLERY
     */
    private function applyGallery(array $newValues, array $oldFileIds): array
    {
        $result = [];
        $keptOldIds = [];

        foreach ($newValues as $i => $value) {
            if (is_string($value) && str_starts_with($value, 'data:')) {
                $fileId = FileUploader::saveFromDataUrl($value, self::UPLOAD_SUBDIR);
                if ($fileId) {
                    $result[] = $fileId;
                }
                continue;
            }
            // не data: — считаем, что это неизменённая картинка на той же позиции
            if (isset($oldFileIds[$i])) {
                $result[] = $oldFileIds[$i];
                $keptOldIds[] = $oldFileIds[$i];
            }
        }

        foreach (array_diff($oldFileIds, $keptOldIds) as $removedId) {
            FileUploader::delete($removedId);
        }

        return $result;
    }
}
