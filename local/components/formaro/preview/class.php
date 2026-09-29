<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Formaro\Cabinet\Repository\CategoryRepository;
use Formaro\Cabinet\Repository\NewsRepository;
use Formaro\Cabinet\Repository\ProductRepository;
use Formaro\Cabinet\Security\PartnerContext;
use Formaro\Cabinet\Service\CatalogFilterService;
use Formaro\Cabinet\Service\ProductCardService;

/**
 * Предпросмотр записи кабинета партнёра на витрине (/preview/).
 *
 * Форма товара, категории или новости в кабинете («Предпросмотр») шлёт
 * POST: type (product|category|news), data (JSON — данные формы в формате
 * сохранения, см. collectProduct()/collectCategory()/collectNews()),
 * sessid. Страница собирается шаблонами сайта из этих данных, без
 * сохранения — поэтому работает и для новой, скрытой, несохранённой
 * записи или записи на проверке.
 *
 * Только для партнёра, вошедшего в кабинет; партнёр записи — всегда
 * текущий (из данных формы не берётся). Картинки — URL файлов сайта или
 * data:-строки из формы.
 */
class FormaroPreviewComponent extends CBitrixComponent
{
    private const TYPES = ['product', 'category', 'news'];
    private const PRODUCTS_LIMIT = 24;

    private int $partnerId = 0;

    public function executeComponent()
    {
        global $APPLICATION;
        $APPLICATION->SetPageProperty('robots', 'noindex, nofollow');
        header('X-Robots-Tag: noindex, nofollow');

        if (!Loader::includeModule('iblock') || !Loader::includeModule('formaro.cabinet')) {
            ShowError('formaro.cabinet module not found');
            return;
        }

        $type = (string)$this->request->getPost('type');
        $data = json_decode((string)$this->request->getPost('data'), true);
        if (!PartnerContext::hasAccess()) {
            $this->showError('Предпросмотр доступен партнёру, вошедшему в кабинет.');
            return;
        }
        if (!$this->request->isPost() || !check_bitrix_sessid() || !in_array($type, self::TYPES, true) || !is_array($data)) {
            $this->showError('Откройте предпросмотр кнопкой «Предпросмотр» в форме товара, категории или новости в кабинете.');
            return;
        }
        $this->partnerId = PartnerContext::getPartnerId();

        $this->arResult = ['TYPE' => $type];
        switch ($type) {
            case 'product':
                $this->prepareProduct($data);
                break;
            case 'category':
                $this->prepareCategory($data);
                break;
            case 'news':
                $this->prepareNews($data);
                break;
        }
        $this->includeComponentTemplate($type);
    }

    private function showError(string $message): void
    {
        global $APPLICATION;
        $APPLICATION->SetTitle('Предпросмотр');
        $this->arResult = ['ERROR' => $message];
        $this->includeComponentTemplate('error');
    }

    /** Товар — в формате ProductRepository::toArray(), для formaro:catalog.element. */
    private function prepareProduct(array $data): void
    {
        global $APPLICATION;

        $id = (int)($data['id'] ?? 0);
        $repo = new ProductRepository();
        $saved = $id ? $repo->get($id) : null;
        if (!$saved || !$repo->canEdit($this->partnerId, $saved)) {
            $id = 0;
            $saved = null;
        }

        $props = [];
        foreach ((array)($data['custom_props'] ?? []) as $prop) {
            if (is_array($prop)) {
                $props[] = ['name' => $this->str($prop['name'] ?? ''), 'value' => $this->str($prop['value'] ?? '')];
            }
        }
        $categoryIds = array_values(array_filter(array_map('intval', (array)($data['category_ids'] ?? []))));

        $product = [
            'id' => $id,
            'name' => $this->str($data['name'] ?? '') ?: 'Без названия',
            'short_desc' => $this->str($data['short_desc'] ?? ''),
            'full_desc' => $this->str($data['full_desc'] ?? ''),
            'preview_image' => $this->image($data['preview_image'] ?? ''),
            'preview_image_file_id' => null,
            'gallery' => array_values(array_filter(array_map([$this, 'image'], (array)($data['gallery'] ?? [])))),
            'gallery_file_ids' => [],
            'sku' => $this->str($data['sku'] ?? ''),
            'color' => $this->str($data['color'] ?? ''),
            'size' => $this->str($data['size'] ?? ''),
            'price' => max(0, (float)($data['price'] ?? 0)),
            'stock' => max(0, (int)($data['stock'] ?? 0)),
            'custom_props' => $props,
            'is_preorder' => !empty($data['is_preorder']),
            'tags' => array_values(array_filter(array_map([$this, 'str'], (array)($data['tags'] ?? [])))),
            // Группа вариантов — сохранённая (форма её не меняет).
            'variant_group_id' => $saved ? $saved['variant_group_id'] : 0,
            'partner_id' => $this->partnerId,
            'category_ids' => $categoryIds,
            'status' => 'active',
            'public_url' => $saved ? $saved['public_url'] : '',
        ];

        $section = $categoryIds ? $this->sectionChain($categoryIds[0]) : [];
        foreach ($section as $item) {
            $APPLICATION->AddChainItem($item['NAME'], $item['URL']);
        }

        $this->arResult['PRODUCT'] = $product;
        $this->arResult['SECTION'] = $section ? end($section) : null;
    }

    /**
     * Категория: плитка в корне каталога (у категории первого уровня —
     * своя, второго — в плитке родителя, как на /catalog/) и страница
     * категории — заголовок, крошки и товары (у сохранённой — из базы).
     */
    private function prepareCategory(array $data): void
    {
        global $APPLICATION;

        $id = (int)($data['id'] ?? 0);
        $repo = new CategoryRepository();
        $saved = $id ? $repo->get($id) : null;
        if (!$saved || !($saved['is_system'] || $saved['partner_id'] === $this->partnerId)) {
            $id = 0;
        }
        $name = $this->str($data['name'] ?? '') ?: 'Без названия';
        $picture = $this->image($data['preview_image'] ?? '');
        $parentChain = $this->sectionChain((int)($data['parent_id'] ?? 0));

        foreach ($parentChain as $item) {
            $APPLICATION->AddChainItem($item['NAME'], $item['URL']);
        }
        $APPLICATION->AddChainItem($name);
        $APPLICATION->SetTitle($name);

        $products = [];
        $count = 0;
        if ($id) {
            $filter = ['SECTION_ID' => $id, 'INCLUDE_SUBSECTIONS' => 'Y'];
            $productRepo = new ProductRepository();
            $products = ProductCardService::build($productRepo->findPublic($filter, ['SORT' => 'ASC', 'ID' => 'DESC'], self::PRODUCTS_LIMIT));
            $count = $productRepo->countPublic(ProductRepository::buildPublicFilter(['sections' => [$id]]));
        }

        // Плитка корня каталога: DEPTH_LEVEL 1 — своя, 2 — родителя.
        $tile = null;
        $self = ['ID' => $id, 'NAME' => $name, 'SECTION_PAGE_URL' => '#', 'ELEMENT_CNT' => $count];
        if (!$parentChain) {
            $tile = $self + ['PICTURE' => ['SRC' => $picture], 'SUB' => $this->children($id)];
        } elseif (count($parentChain) === 1) {
            $parent = $parentChain[0];
            $sub = $this->children($parent['ID']);
            $replaced = false;
            foreach ($sub as $i => $child) {
                if ($id && $child['ID'] === $id) {
                    $sub[$i] = $self;
                    $replaced = true;
                }
            }
            if (!$replaced) {
                $sub[] = $self;
            }
            $tile = [
                'ID' => $parent['ID'],
                'NAME' => $parent['NAME'],
                'SECTION_PAGE_URL' => $parent['URL'],
                'PICTURE' => ['SRC' => $parent['PICTURE']],
                'SUB' => $sub,
                'ELEMENT_CNT' => $parent['ELEMENT_CNT'],
            ];
        }

        $this->arResult['CATEGORY'] = ['ID' => $id, 'NAME' => $name];
        $this->arResult['TILE'] = $tile;
        $this->arResult['CATALOG_ITEMS'] = $products;
        $this->arResult['CATALOG_COUNT'] = $count;
    }

    /** Новость — arResult шаблона bitrix:news.detail сайта (news/news). */
    private function prepareNews(array $data): void
    {
        global $APPLICATION;

        $id = (int)($data['id'] ?? 0);
        $repo = new NewsRepository();
        $saved = $id ? $repo->get($id) : null;
        if ($saved && $saved['partner_id'] !== $this->partnerId) {
            $saved = null;
        }
        $title = $this->str($data['title'] ?? '') ?: 'Без заголовка';
        $ts = strtotime((string)($data['created_at'] ?? '')) ?: time();

        // Вкладка «Каталог» могла не загрузиться — тогда привязки сохранённые.
        $sectionIds = array_key_exists('catalog_section_ids', $data) ? (array)$data['catalog_section_ids'] : ($saved['catalog_section_ids'] ?? []);
        $productIds = array_key_exists('catalog_product_ids', $data) ? (array)$data['catalog_product_ids'] : ($saved['catalog_product_ids'] ?? []);

        $iblockId = (int)(CIBlock::GetList([], ['CODE' => 'cabinet_news', 'CHECK_PERMISSIONS' => 'N'])->Fetch()['ID'] ?? 0);
        $section = CIBlockSection::GetList([], ['IBLOCK_ID' => $iblockId, '=CODE' => 'partners', 'CHECK_PERMISSIONS' => 'N'], false, ['ID', 'NAME'])->Fetch();
        $sectionId = (int)($section['ID'] ?? 0);

        $sanitizer = new CBXSanitizer();
        $sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_MIDDLE);
        $fullImage = $this->image($data['full_image'] ?? '');

        $APPLICATION->AddChainItem('Новости', '/news/');
        $APPLICATION->AddChainItem($title);
        $APPLICATION->SetTitle($title);

        $this->arResult['NEWS'] = [
            'NAME' => htmlspecialcharsbx($title),
            'DETAIL_PICTURE' => $fullImage !== '' ? ['SRC' => $fullImage] : null,
            'IBLOCK_SECTION_ID' => $sectionId,
            'SECTIONS' => [$sectionId => ['NAME' => htmlspecialcharsbx((string)($section['NAME'] ?? '')), 'CLASS' => 'bg-success']],
            'DISPLAY_ACTIVE_FROM' => FormatDate('j F Y', $ts),
            'DETAIL_TEXT' => $sanitizer->SanitizeHtml($this->str($data['full_desc'] ?? '')),
            'PREVIEW_TEXT' => htmlspecialcharsbx($this->str($data['short_desc'] ?? '')),
            'PROPERTIES' => [
                'PARTNER_ID' => ['VALUE' => $this->partnerId],
                'CATALOG_SECTIONS' => ['VALUE' => array_values(array_filter(array_map('intval', $sectionIds)))],
                'CATALOG_PRODUCTS' => ['VALUE' => array_values(array_filter(array_map('intval', $productIds)))],
            ],
        ];
        $this->arResult['NEWS_PARAMS'] = ['DISPLAY_PICTURE' => 'Y', 'IBLOCK_ID' => $iblockId];
    }

    /** Цепочка разделов каталога до $sectionId включительно (для крошек). */
    private function sectionChain(int $sectionId): array
    {
        if (!$sectionId) {
            return [];
        }
        $iblockId = CatalogFilterService::getCatalogIblockId();
        $chain = [];
        $res = CIBlockSection::GetNavChain($iblockId, $sectionId, ['ID', 'IBLOCK_ID', 'CODE', 'NAME', 'SECTION_PAGE_URL', 'PICTURE'], true);
        foreach ($res as $row) {
            $chain[] = [
                'ID' => (int)$row['ID'],
                'NAME' => $row['NAME'],
                'URL' => CIBlock::ReplaceDetailUrl($row['SECTION_PAGE_URL'], $row, false, 'S'),
                'PICTURE' => $row['PICTURE'] ? (string)CFile::GetPath($row['PICTURE']) : '',
                'ELEMENT_CNT' => (int)CIBlockSection::GetSectionElementsCount($row['ID'], ['CNT_ACTIVE' => 'Y']),
            ];
        }

        return $chain;
    }

    /** Активные подкатегории — для плитки корня каталога. */
    private function children(int $sectionId): array
    {
        if (!$sectionId) {
            return [];
        }
        $children = [];
        $res = CIBlockSection::GetList(
            ['SORT' => 'ASC', 'NAME' => 'ASC'],
            ['IBLOCK_ID' => CatalogFilterService::getCatalogIblockId(), 'SECTION_ID' => $sectionId, 'ACTIVE' => 'Y', 'CNT_ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
            true,
            ['ID', 'NAME', 'SECTION_PAGE_URL']
        );
        while ($row = $res->GetNext()) {
            $children[] = ['ID' => (int)$row['ID'], 'NAME' => $row['NAME'], 'SECTION_PAGE_URL' => $row['SECTION_PAGE_URL'], 'ELEMENT_CNT' => (int)$row['ELEMENT_CNT']];
        }

        return $children;
    }

    private function str($value): string
    {
        return is_scalar($value) ? trim((string)$value) : '';
    }

    /** Картинка из формы: файл сайта или data:image — иначе пусто. */
    private function image($value): string
    {
        $value = $this->str($value);
        if (preg_match('#^data:image/(png|jpe?g|gif|webp);base64,[A-Za-z0-9+/=]+$#', $value)) {
            return $value;
        }
        if (preg_match('#^/upload/[^"\'<>\s]+$#', $value) && !str_contains($value, '..')) {
            return $value;
        }

        return '';
    }
}
