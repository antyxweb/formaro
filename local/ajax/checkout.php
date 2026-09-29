<?php
/**
 * Оформление заказа со страницы корзины (/personal/cart/, formaro:cart).
 *
 * POST sessid, ids[] (отмеченные товары — количество берётся из корзины
 * на сервере), coupons (КОД,КОД), delivery (address|pickup), payment
 * (card|sbp|invoice), address → {orders: [{id, order_number,
 * partner_name, total}]} — по заказу на поставщика, оформленные товары
 * убраны из корзины; ошибка → {error}.
 *
 * Только для авторизованных: покупатель — из профиля, корзина — в
 * аккаунте. См. CartCheckoutService::checkout().
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Formaro\Cabinet\Service\CartCheckoutService;

header('Content-Type: application/json; charset=utf-8');

$respond = static function (int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    die();
};

if (!Loader::includeModule('formaro.cabinet') || !Loader::includeModule('iblock')) {
    $respond(500, ['error' => 'formaro.cabinet module not found']);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $respond(405, ['error' => 'Method not allowed']);
}

global $USER;
$userId = (is_object($USER) && $USER->IsAuthorized()) ? (int)$USER->GetID() : 0;
if (!$userId) {
    $respond(401, ['error' => 'Войдите, чтобы оформить заказ']);
}
if (!check_bitrix_sessid()) {
    $respond(403, ['error' => 'Сессия устарела — обновите страницу']);
}

try {
    $orders = CartCheckoutService::checkout(
        $userId,
        (array)($_POST['ids'] ?? []),
        explode(',', (string)($_POST['coupons'] ?? '')),
        (string)($_POST['delivery'] ?? ''),
        (string)($_POST['payment'] ?? ''),
        (string)($_POST['address'] ?? '')
    );
} catch (\RuntimeException $e) {
    $respond(400, ['error' => $e->getMessage()]);
}

$respond(200, ['orders' => $orders]);
