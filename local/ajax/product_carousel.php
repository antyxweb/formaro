<?php
/**
 * AJAX-подгрузка каруселей formaro:product.carousel по слайду «+».
 * GET mode (DISCOUNT|NEW|VIEWED), offset, limit, partner (только товары
 * партнёра; root — корень его каталога для ссылок, CatalogUrl), section (категория с подкатегориями), exclude (id товара),
 * ids (для VIEWED, через запятую) → JSON {items:[...], hasMore:bool};
 * строки items — в формате catalog_grid.php (для FormaroProductCard.html).
 *
 * Только чтение — без check_bitrix_sessid(), как catalog_grid.php.
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Formaro\Cabinet\Catalog\CatalogUrl;
use Formaro\Cabinet\Service\ProductCarouselService;

header('Content-Type: application/json; charset=utf-8');

if (!Loader::includeModule('formaro.cabinet')) {
    http_response_code(500);
    echo json_encode(['error' => 'formaro.cabinet module not found']);
    die();
}

// Каталог партнёра (/partners/<код>/catalog/): ссылки на товары — от его корня.
CatalogUrl::applyRequestRoot((string)($_GET['root'] ?? ''), (int)($_GET['partner'] ?? 0));

$page = ProductCarouselService::load(
    (string)($_GET['mode'] ?? ''),
    [
        'partner_id' => (int)($_GET['partner'] ?? 0),
        'section_id' => (int)($_GET['section'] ?? 0),
        'exclude_ids' => [(int)($_GET['exclude'] ?? 0)],
        'ids' => explode(',', (string)($_GET['ids'] ?? '')),
    ],
    max(0, (int)($_GET['offset'] ?? 0)),
    min(48, max(1, (int)($_GET['limit'] ?? 12)))
);

$items = array_map(static fn(array $item) => [
    'id' => $item['ID'],
    'name' => $item['NAME'],
    'sku' => $item['SKU'],
    'url' => $item['URL'],
    'image' => $item['IMAGE'],
    'stock' => $item['STOCK'],
    'is_preorder' => $item['IS_PREORDER'],
    'price' => $item['PRICE'],
    'old_price' => $item['OLD_PRICE'],
    'badges' => $item['BADGES'],
], $page['items']);

echo json_encode(['items' => $items, 'hasMore' => $page['hasMore']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
