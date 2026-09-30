<?php
/**
 * Отмена заказа покупателем со страницы «Ваши заказы» (formaro:personal.orders).
 *
 * POST sessid, id → {order: {id, status}} или {error}. Только свой заказ
 * в статусе «Новый» — см. CartCheckoutService::cancel().
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
    $respond(401, ['error' => 'Войдите, чтобы отменить заказ']);
}
if (!check_bitrix_sessid()) {
    $respond(403, ['error' => 'Сессия устарела — обновите страницу']);
}

try {
    $order = CartCheckoutService::cancel($userId, (int)($_POST['id'] ?? 0));
} catch (\RuntimeException $e) {
    $respond(400, ['error' => $e->getMessage()]);
}

$respond(200, ['order' => ['id' => $order['id'], 'status' => $order['status']]]);
