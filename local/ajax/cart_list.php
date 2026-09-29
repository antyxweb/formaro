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
 * coupons=КОД,КОД (необязательно) → coupons: [{code, valid, message,
 * partner_id, partner_name, discount_type, value}] в том же порядке.
 * Купон партнёра даёт скидку на его товары (по одному купону на
 * поставщика — выбирает страница корзины); сумму скидки по отмеченным
 * товарам считает она же.
 *
 * Расчёт — CartCheckoutService::calculate() (им же пользуется оформление,
 * /local/ajax/checkout.php).
 *
 * Корзину гостя присылает сам браузер, поэтому без авторизации: отдаются
 * только публичные данные активных товаров.
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Formaro\Cabinet\Repository\CartRepository;
use Formaro\Cabinet\Service\CartCheckoutService;

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

$result = CartCheckoutService::calculate($items, explode(',', (string)($_GET['coupons'] ?? '')));
// id купона наружу не нужен.
$coupons = array_map(static function (array $c) {
    unset($c['id']);
    return $c;
}, $result['coupons']);

echo json_encode(['groups' => $result['groups'], 'items' => $items, 'coupons' => $coupons], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
