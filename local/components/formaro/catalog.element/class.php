<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Formaro\Cabinet\Repository\DiscountRepository;
use Formaro\Cabinet\Repository\ProductRepository;
use Formaro\Cabinet\Service\CatalogFilterService;
use Formaro\Cabinet\Service\ProductPricingService;

/**
 * Карточка товара публичного каталога (cabinet_catalog). Вёрстка —
 * /html/product-detail.html.
 *
 * Варианты — товары одной группы (свойство VARIANT_GROUP_ID, группы
 * собирает партнёр в кабинете): в блоке «Цвет» — по одному товару на цвет
 * (того же размера, если есть), в блоке «Размер» — размеры текущего цвета.
 * Переход на вариант script.js делает без перезагрузки страницы.
 *
 * «Характеристики» — цвет, размер и доп. свойства товара (CUSTOM_PROPS_JSON).
 * Возвращает ID партнёра — для блока «Товары продавца» под карточкой.
 *
 * PREVIEW — товар в формате ProductRepository::toArray() прямо из формы
 * кабинета (страница /preview/): рисуется без базы и без кэша, картинки —
 * URL или data:-строки, как в форме.
 */
class FormaroCatalogElementComponent extends CBitrixComponent
{
    private const SHORT_PROPS = 5;
    private const GALLERY_HEIGHT = 500;

    public function onPrepareComponentParams($params)
    {
        $params['ELEMENT_ID'] = max(0, (int)($params['ELEMENT_ID'] ?? 0));
        $params['CACHE_TIME'] = isset($params['CACHE_TIME']) ? (int)$params['CACHE_TIME'] : 3600;

        return $params;
    }

    public function executeComponent()
    {
        global $APPLICATION;

        if (is_array($this->arParams['~PREVIEW'] ?? null)) {
            return $this->executePreview($this->arParams['~PREVIEW']);
        }

        // Скидки действуют по датам — день входит в ключ кэша.
        // Ссылки — от корня каталога (в каталоге партнёра — его).
        if ($this->startResultCache(false, [date('Y-m-d'), \Formaro\Cabinet\Catalog\CatalogUrl::root()])) {
            if (!Loader::includeModule('iblock') || !Loader::includeModule('formaro.cabinet')) {
                $this->abortResultCache();
                ShowError('formaro.cabinet module not found');
                return 0;
            }

            $repo = new ProductRepository();
            $product = $this->arParams['ELEMENT_ID'] ? $repo->get($this->arParams['ELEMENT_ID']) : null;
            if (!$product || $product['status'] !== 'active') {
                $this->abortResultCache();
                return 0;
            }

            $display = ProductPricingService::computeDisplay($product, (new DiscountRepository())->listActive());
            $props = $this->buildProps($product);

            $this->arResult = [
                'ID' => $product['id'],
                'NAME' => $product['name'],
                'URL' => $product['public_url'],
                'SKU' => $product['sku'],
                'PRICE' => $display['price'],
                'OLD_PRICE' => $display['old_price'],
                'BADGES' => $display['badges'],
                'STOCK' => $product['stock'],
                'IS_PREORDER' => $product['is_preorder'],
                'COLOR' => $product['color'],
                'SIZE' => $product['size'],
                'DESCRIPTION' => $product['full_desc'],
                'SHORT_DESCRIPTION' => $product['short_desc'],
                'GALLERY' => $this->buildGallery($product),
                'PROPS' => $props,
                'SHORT_PROPS' => array_slice($props, 0, self::SHORT_PROPS),
                'PARTNER_ID' => $product['partner_id'],
                'PARTNER' => $this->loadPartner($product['partner_id']),
            ];
            $this->arResult += $this->buildVariants($repo, $product);

            if (defined('BX_COMP_MANAGED_CACHE')) {
                global $CACHE_MANAGER;
                $CACHE_MANAGER->RegisterTag('iblock_id_' . CatalogFilterService::getCatalogIblockId());
                $CACHE_MANAGER->RegisterTag('iblock_id_8');
            }

            $this->setResultCacheKeys(['ID', 'NAME', 'PARTNER_ID', 'GALLERY', 'SHORT_DESCRIPTION']);
            $this->includeComponentTemplate();
        }

        if (!empty($this->arResult['ID'])) {
            $APPLICATION->SetTitle($this->arResult['NAME']);
            $APPLICATION->SetPageProperty('title', $this->arResult['NAME']);
            $APPLICATION->AddChainItem($this->arResult['NAME']);
            // Превью ссылки (Open Graph): первое фото и краткое описание.
            \Formaro\Cabinet\Seo\OpenGraph::set(
                (string)($this->arResult['GALLERY'][0]['SRC'] ?? ''),
                'product',
                (string)($this->arResult['SHORT_DESCRIPTION'] ?? '')
            );
        }

        return (int)($this->arResult['PARTNER_ID'] ?? 0);
    }

    /** Предпросмотр: тот же arResult, что у сохранённого товара. */
    private function executePreview(array $product): int
    {
        global $APPLICATION;
        if (!Loader::includeModule('iblock') || !Loader::includeModule('formaro.cabinet')) {
            ShowError('formaro.cabinet module not found');
            return 0;
        }

        $display = ProductPricingService::computeDisplay($product, (new DiscountRepository())->listActive());
        $props = $this->buildProps($product);
        $gallery = [];
        foreach (array_values(array_unique(array_filter(array_merge([$product['preview_image']], $product['gallery'])))) as $src) {
            [$width, $height] = $this->imageSize($src);
            $gallery[] = [
                'SRC' => $src,
                'WIDTH' => $height ? (int)round($width * self::GALLERY_HEIGHT / $height) : self::GALLERY_HEIGHT,
                'HEIGHT' => self::GALLERY_HEIGHT,
            ];
        }

        $this->arResult = [
            'ID' => $product['id'],
            'NAME' => $product['name'],
            'URL' => $product['public_url'],
            'SKU' => $product['sku'],
            'PRICE' => $display['price'],
            'OLD_PRICE' => $display['old_price'],
            'BADGES' => $display['badges'],
            'STOCK' => $product['stock'],
            'IS_PREORDER' => $product['is_preorder'],
            'COLOR' => $product['color'],
            'SIZE' => $product['size'],
            'DESCRIPTION' => $product['full_desc'],
            'SHORT_DESCRIPTION' => $product['short_desc'],
            'GALLERY' => $gallery,
            'PROPS' => $props,
            'SHORT_PROPS' => array_slice($props, 0, self::SHORT_PROPS),
            'PARTNER_ID' => $product['partner_id'],
            'PARTNER' => $this->loadPartner($product['partner_id']),
            'PREVIEW' => true,
        ];
        $this->arResult += $this->buildVariants(new ProductRepository(), $product);
        $this->includeComponentTemplate();

        $APPLICATION->SetTitle($product['name']);
        $APPLICATION->AddChainItem($product['name']);

        return (int)$product['partner_id'];
    }

    /** Размер картинки из формы: файл сайта (/upload/…) или data:-строка. */
    private function imageSize(string $src): array
    {
        $size = false;
        if (str_starts_with($src, 'data:image/')) {
            $data = base64_decode((string)substr($src, (int)strpos($src, ',') + 1), true);
            $size = $data !== false ? @getimagesizefromstring($data) : false;
        } elseif (str_starts_with($src, '/') && !str_contains($src, '..')) {
            $size = @getimagesize($_SERVER['DOCUMENT_ROOT'] . parse_url($src, PHP_URL_PATH));
        }

        return $size ? [(int)$size[0], (int)$size[1]] : [0, 0];
    }

    /** Превью + галерея; ширина ссылки — под высоту слайдера (как в вёрстке). */
    private function buildGallery(array $product): array
    {
        $fileIds = array_values(array_unique(array_filter(array_merge(
            [(int)$product['preview_image_file_id']],
            array_map('intval', $product['gallery_file_ids'])
        ))));

        $gallery = [];
        foreach ($fileIds as $fileId) {
            $file = CFile::GetFileArray($fileId);
            if (!$file) {
                continue;
            }
            $height = self::GALLERY_HEIGHT;
            $width = $file['HEIGHT'] ? (int)round($file['WIDTH'] * $height / $file['HEIGHT']) : $height;
            $gallery[] = ['SRC' => $file['SRC'], 'WIDTH' => $width, 'HEIGHT' => $height];
        }

        return $gallery;
    }

    /** Цвет, размер, затем доп. свойства партнёра. */
    private function buildProps(array $product): array
    {
        $props = [];
        if (trim($product['color']) !== '') {
            $props[] = ['NAME' => 'Цвет', 'VALUE' => $product['color']];
        }
        if (trim($product['size']) !== '') {
            $props[] = ['NAME' => 'Размер', 'VALUE' => $product['size']];
        }
        foreach ($product['custom_props'] as $prop) {
            $name = trim((string)($prop['name'] ?? ''));
            $value = trim((string)($prop['value'] ?? ''));
            if ($name !== '' && $value !== '') {
                $props[] = ['NAME' => $name, 'VALUE' => $value];
            }
        }

        return $props;
    }

    /**
     * COLORS: по товару на цвет — того же размера, что текущий, если есть;
     * SIZES: размеры товаров текущего цвета. Без группы — только сам товар.
     */
    private function buildVariants(ProductRepository $repo, array $product): array
    {
        $members = $product['variant_group_id']
            ? $repo->findPublic(['PROPERTY_VARIANT_GROUP_ID' => $product['variant_group_id']], ['SORT' => 'ASC', 'ID' => 'ASC'], 200)
            : [];
        // Текущий товар — в том виде, что передан (в предпросмотре — из
        // формы), на своём месте в группе.
        $found = false;
        foreach ($members as $i => $p) {
            if ($p['id'] === $product['id']) {
                $members[$i] = $product;
                $found = true;
            }
        }
        if (!$found) {
            $members[] = $product;
        }

        $item = static fn(array $p) => [
            'ID' => $p['id'],
            'URL' => $p['public_url'],
            'IMAGE' => $p['preview_image'],
            'ACTIVE' => $p['id'] === $product['id'],
        ];

        // На цвет — сам товар, иначе товар того же размера, иначе первый.
        $colors = [];
        $priority = [];
        foreach ($members as $p) {
            $color = trim($p['color']);
            if ($color === '') {
                continue;
            }
            $rank = $p['id'] === $product['id'] ? 2 : ($p['size'] === $product['size'] ? 1 : 0);
            if (!isset($colors[$color]) || $rank > $priority[$color]) {
                $colors[$color] = $item($p) + ['VALUE' => $color];
                $priority[$color] = $rank;
            }
        }

        $sizes = [];
        foreach ($members as $p) {
            $size = trim($p['size']);
            if ($size === '' || trim($p['color']) !== trim($product['color'])) {
                continue;
            }
            if (!isset($sizes[$size]) || $p['id'] === $product['id']) {
                $sizes[$size] = $item($p) + ['VALUE' => $size];
            }
        }
        $order = array_flip(CatalogFilterService::sortSizes(array_keys($sizes)));
        uksort($sizes, static fn($a, $b) => $order[$a] <=> $order[$b]);

        return ['COLORS' => array_values($colors), 'SIZES' => array_values($sizes)];
    }

    /** Карточка «О продавце» — элемент инфоблока партнёров. */
    private function loadPartner(int $partnerId): ?array
    {
        if (!$partnerId) {
            return null;
        }
        $row = CIBlockElement::GetList(
            [],
            ['ID' => $partnerId, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
            false,
            ['nTopCount' => 1],
            ['ID', 'IBLOCK_ID', 'NAME', 'PREVIEW_TEXT', 'PREVIEW_PICTURE', 'DETAIL_PAGE_URL']
        )->GetNext();
        if (!$row) {
            return null;
        }

        return [
            'ID' => (int)$row['ID'],
            'NAME' => $row['NAME'],
            'TEXT' => $row['PREVIEW_TEXT'],
            'LOGO' => $row['PREVIEW_PICTURE'] ? CFile::GetPath($row['PREVIEW_PICTURE']) : '',
            'URL' => $row['DETAIL_PAGE_URL'],
        ];
    }
}
