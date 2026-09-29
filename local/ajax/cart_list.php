<?php
/**
 * Содержимое корзины для /personal/cart/ (formaro:cart).
 *
 * GET items=id:qty,id:qty,… (порядок — порядок добавления) →
 * {groups: [{partner: {id, name, url}, items: [...]}], items: [{id, qty}]}
 * items групп — как у catalog_grid.php плюс qty, max (0 — без
 * ограничения), sum и old_sum (за всё количество). Цена со скидкой
 * считается для этого количества (скидки «от N штук» и «от суммы»).
 * Верхний items — корзина после проверки (недоступные товары убраны,
 * количество ограничено остатком): js/cart.js сверяет с ней свою.
 *
 * Корзину гостя присылает сам браузер, поэтому без авторизации: отдаются
 * только публичные данные активных товаров.
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Formaro\Cabinet\Repository\CartRepository;
use Formaro\Cabinet\Repository\DiscountRepository;
use Formaro\Cabinet\Repository\ProductRepository;
use Formaro\Cabinet\Service\ProductPricingService;

header('Content-Type: application/json; charset=utf-8');

if (!Loader::includeModule('formaro.cabinet') || !Loader::includeModule('iblock')) {
    http_response_code(500);
    echo json_encode(['error' => 'formaro.cabinet module not found']);
    die();
}

$items = [];
foreach (array_slice(explode(',', (string)($_GET['items'] ?? '')), 0, CartRepository::MAX_ITEMS) as $pair) {
    [$id, $qty] = array_pad(explode(':', $pair, 2), 2, 0);
    $items[] = ['id' => (int)$id, 'qty' => (int)$qty];
}
$items = CartRepository::normalize($items);

$products = [];
if ($items) {
    foreach ((new ProductRepository())->findPublic(['ID' => array_column($items, 'id')], ['ID' => 'ASC'], count($items)) as $p) {
        $products[$p['id']] = $p;
    }
}
$activeDiscounts = $items ? (new DiscountRepository())->listActive() : [];

// Группы по партнёрам — в порядке первого добавленного товара.
$groups = [];
foreach ($items as $item) {
    $p = $products[$item['id']];
    $display = ProductPricingService::computeDisplay($p, $activeDiscounts, $item['qty']);
    $partnerId = (int)$p['partner_id'];
    $groups[$partnerId]['items'][] = [
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
        'qty' => $item['qty'],
        'max' => $p['is_preorder'] ? 0 : $p['stock'],
        'sum' => $display['price'] * $item['qty'],
        'old_sum' => ($display['old_price'] ?? $display['price']) * $item['qty'],
    ];
}

$partners = [];
$partnerIds = array_filter(array_keys($groups));
if ($partnerIds) {
    // Название — короткое (NAME_SHORT, «ИП Антух Д. А.»), если заполнено.
    $res = CIBlockElement::GetList([], ['ID' => $partnerIds, 'CHECK_PERMISSIONS' => 'N'], false, false, ['ID', 'NAME', 'DETAIL_PAGE_URL', 'ACTIVE', 'PROPERTY_NAME_SHORT']);
    while ($row = $res->GetNext()) {
        $shortName = trim((string)($row['~PROPERTY_NAME_SHORT_VALUE'] ?? ''));
        $partners[(int)$row['ID']] = [
            'id' => (int)$row['ID'],
            'name' => $shortName !== '' ? $shortName : $row['~NAME'],
            'url' => $row['ACTIVE'] === 'Y' ? $row['DETAIL_PAGE_URL'] : '',
        ];
    }
}

$result = [];
foreach ($groups as $partnerId => $group) {
    $result[] = [
        'partner' => $partners[$partnerId] ?? ['id' => $partnerId, 'name' => 'Другие продавцы', 'url' => ''],
        'items' => $group['items'],
    ];
}

echo json_encode(['groups' => $result, 'items' => $items], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
