<?php
/**
 * AJAX-эндпоинт для #catalog-grid на главной (см. index.php) — публичная
 * витрина товаров формато.cabinet_catalog. GET ?offset=N&limit=M, отдаёт
 * JSON {items:[...], hasMore:bool, total:int}. Необязательные параметры
 * поиска/фильтра (компонент formaro:catalog.search): q, sections (id через
 * запятую), price_min, price_max, colors, sizes (через "|"), partner
 * (страница партнёра — только его товары), root (корень каталога
 * партнёра — ссылки в его каталог, CatalogUrl), sort (popular|new|cheap|expensive
 * — каталог, ProductRepository::publicOrder()). Метки/цена со скидкой считает
 * ProductPricingService (общая логика с order-detail.js:
 * autoApplyBestDiscount).
 *
 * Не компонент — просто читает данные, ничего не меняет, поэтому без
 * check_bitrix_sessid()/CSRF (как обычная страница каталога).
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Formaro\Cabinet\Catalog\CatalogUrl;
use Formaro\Cabinet\Repository\ProductRepository;
use Formaro\Cabinet\Repository\DiscountRepository;
use Formaro\Cabinet\Service\ProductPricingService;

header('Content-Type: application/json; charset=utf-8');

if (!Loader::includeModule('formaro.cabinet')) {
    http_response_code(500);
    echo json_encode(['error' => 'formaro.cabinet module not found']);
    die();
}

// Каталог партнёра (/partners/<код>/catalog/): ссылки на товары — от его корня.
CatalogUrl::applyRequestRoot((string)($_GET['root'] ?? ''), (int)($_GET['partner'] ?? 0));

$limit = min(48, max(1, (int)($_GET['limit'] ?? 24)));
$offset = max(0, (int)($_GET['offset'] ?? 0));

$productRepo = new ProductRepository();
$discountRepo = new DiscountRepository();

$csv = static fn(string $key, string $sep) => array_filter(explode($sep, (string)($_GET[$key] ?? '')), 'strlen');
$filter = ProductRepository::buildPublicFilter([
    'q' => $_GET['q'] ?? '',
    'sections' => $csv('sections', ','),
    'price_min' => $_GET['price_min'] ?? '',
    'price_max' => $_GET['price_max'] ?? '',
    'colors' => $csv('colors', '|'),
    'sizes' => $csv('sizes', '|'),
    'partner_id' => (int)($_GET['partner'] ?? 0),
]);

$sort = in_array($_GET['sort'] ?? '', ProductRepository::PUBLIC_SORTS, true) ? $_GET['sort'] : '';
$products = $productRepo->listPublic($limit, $offset, $filter, $sort);
$activeDiscounts = $discountRepo->listActive();

$items = array_map(static function (array $p) use ($activeDiscounts) {
    $display = ProductPricingService::computeDisplay($p, $activeDiscounts);

    return [
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
    ];
}, $products);

$total = $productRepo->countPublic($filter);

echo json_encode([
    'items' => $items,
    'hasMore' => ($offset + count($products)) < $total,
    'total' => $total,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
