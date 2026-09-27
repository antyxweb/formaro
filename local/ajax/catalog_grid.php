<?php
/**
 * AJAX-эндпоинт для #catalog-grid на главной (см. index.php) — публичная
 * витрина товаров формато.cabinet_catalog. GET ?offset=N&limit=M, отдаёт
 * JSON {items:[...], hasMore:bool}. Метки/цена со скидкой считает
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
use Formaro\Cabinet\Repository\ProductRepository;
use Formaro\Cabinet\Repository\DiscountRepository;
use Formaro\Cabinet\Service\ProductPricingService;

header('Content-Type: application/json; charset=utf-8');

if (!Loader::includeModule('formaro.cabinet')) {
    http_response_code(500);
    echo json_encode(['error' => 'formaro.cabinet module not found']);
    die();
}

$limit = min(48, max(1, (int)($_GET['limit'] ?? 24)));
$offset = max(0, (int)($_GET['offset'] ?? 0));

$productRepo = new ProductRepository();
$discountRepo = new DiscountRepository();

$products = $productRepo->listPublic($limit, $offset);
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

$total = $productRepo->countPublic();

echo json_encode([
    'items' => $items,
    'hasMore' => ($offset + count($products)) < $total,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
