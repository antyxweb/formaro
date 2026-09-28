<?php
/**
 * Список избранных товаров для /personal/favorites/ (formaro:favorites.list).
 *
 * GET ids (через запятую, порядок — последние добавленные первыми),
 *     sort (added|new|cheap|expensive), offset, limit,
 *     фильтр как у catalog_grid.php: sections, price_min, price_max.
 * → {items, total, hasMore, facets: {sections[], price_min, price_max}}
 *
 * ids приходят от клиента (у гостя избранное только в localStorage);
 * отдаются лишь публичные данные активных товаров, поэтому без
 * авторизации. facets считаются по всему избранному без фильтра — какие
 * категории и диапазон цен показывать в фильтре.
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Formaro\Cabinet\Repository\DiscountRepository;
use Formaro\Cabinet\Repository\ProductRepository;
use Formaro\Cabinet\Service\ProductPricingService;

header('Content-Type: application/json; charset=utf-8');

if (!Loader::includeModule('formaro.cabinet') || !Loader::includeModule('iblock')) {
    http_response_code(500);
    echo json_encode(['error' => 'formaro.cabinet module not found']);
    die();
}

const FAVORITES_MAX = 500;

$ids = array_slice(array_values(array_unique(array_filter(array_map('intval', explode(',', (string)($_GET['ids'] ?? '')))))), 0, FAVORITES_MAX);
$limit = min(48, max(1, (int)($_GET['limit'] ?? 24)));
$offset = max(0, (int)($_GET['offset'] ?? 0));
$sort = in_array($_GET['sort'] ?? '', ['added', 'new', 'cheap', 'expensive'], true) ? $_GET['sort'] : 'added';

$respond = static function (array $items, int $total, array $facets) use ($offset): void {
    echo json_encode([
        'items' => $items,
        'total' => $total,
        'hasMore' => ($offset + count($items)) < $total,
        'facets' => $facets,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    die();
};

$emptyFacets = ['sections' => [], 'price_min' => 0, 'price_max' => 0];
if (!$ids) {
    $respond([], 0, $emptyFacets);
}

$productRepo = new ProductRepository();
$activeDiscounts = (new DiscountRepository())->listActive();

// Фасеты — по всему избранному.
$all = $productRepo->findPublic(['ID' => $ids], ['ID' => 'ASC'], FAVORITES_MAX);
if (!$all) {
    $respond([], 0, $emptyFacets);
}
$sectionIds = [];
$prices = [];
foreach ($all as $p) {
    $sectionIds = array_merge($sectionIds, $p['category_ids']);
    $prices[] = $p['price'];
}
$sectionIds = array_values(array_unique($sectionIds));
// Корневые категории тоже нужны — в фильтре товар из подкатегории
// показывается внутри своей корневой.
if ($sectionIds) {
    $res = CIBlockSection::GetList([], ['ID' => $sectionIds, 'CHECK_PERMISSIONS' => 'N'], false, ['ID', 'IBLOCK_SECTION_ID']);
    while ($row = $res->Fetch()) {
        if ((int)$row['IBLOCK_SECTION_ID']) {
            $sectionIds[] = (int)$row['IBLOCK_SECTION_ID'];
        }
    }
}
$facets = [
    'sections' => array_values(array_unique($sectionIds)),
    'price_min' => (int)floor(min($prices)),
    'price_max' => (int)ceil(max($prices)),
];

// Выборка с фильтром.
$filter = ProductRepository::buildPublicFilter([
    'sections' => array_filter(explode(',', (string)($_GET['sections'] ?? '')), 'strlen'),
    'price_min' => $_GET['price_min'] ?? '',
    'price_max' => $_GET['price_max'] ?? '',
]);
$filter['ID'] = $ids;
$products = $productRepo->findPublic($filter, ['ID' => 'ASC'], FAVORITES_MAX);

$position = array_flip($ids);
$rows = array_map(static function (array $p) use ($activeDiscounts, $position) {
    $display = ProductPricingService::computeDisplay($p, $activeDiscounts);

    return [
        'item' => [
            'id' => $p['id'],
            'name' => $p['name'],
            'sku' => $p['sku'],
            'url' => $p['public_url'],
            'image' => $p['preview_image'],
            'stock' => $p['stock'],
            'is_preorder' => $p['is_preorder'],
            'price' => $display['price'],
            'old_price' => $display['old_price'],
            'badges' => $display['badges'],
        ],
        'position' => $position[$p['id']] ?? PHP_INT_MAX,
        'created' => (int)MakeTimeStamp((string)$p['created_at']),
    ];
}, $products);

// Цена для сортировки — та, что видит покупатель (со скидкой).
usort($rows, static function (array $a, array $b) use ($sort) {
    switch ($sort) {
        case 'new':
            return $b['created'] <=> $a['created'] ?: $b['item']['id'] <=> $a['item']['id'];
        case 'cheap':
            return $a['item']['price'] <=> $b['item']['price'] ?: $a['position'] <=> $b['position'];
        case 'expensive':
            return $b['item']['price'] <=> $a['item']['price'] ?: $a['position'] <=> $b['position'];
        default:
            return $a['position'] <=> $b['position'];
    }
});

$page = array_slice(array_column($rows, 'item'), $offset, $limit);
$respond($page, count($rows), $facets);
