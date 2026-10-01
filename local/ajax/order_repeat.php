<?php
/**
 * «Повторить заказ» в «Ваших заказах» (formaro:personal.orders).
 *
 * POST sessid, id, action:
 *   check → {available: N, missing: [названия], reduced: [«товар — X шт. вместо Y»]};
 *   add   → {ids: [...], redirect: '/personal/cart/'} — доступное добавлено в корзину.
 * Ошибки — {error}. См. OrderRepeatService.
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Formaro\Cabinet\Service\OrderRepeatService;

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
    $respond(401, ['error' => 'Войдите, чтобы повторить заказ']);
}
if (!check_bitrix_sessid()) {
    $respond(403, ['error' => 'Сессия устарела — обновите страницу']);
}

$orderId = (int)($_POST['id'] ?? 0);
try {
    if (($_POST['action'] ?? '') === 'add') {
        $respond(200, ['ids' => OrderRepeatService::repeat($userId, $orderId), 'redirect' => '/personal/cart/']);
    }
    $check = OrderRepeatService::check($userId, $orderId);
    $respond(200, ['available' => count($check['items']), 'missing' => $check['missing'], 'reduced' => $check['reduced']]);
} catch (\RuntimeException $e) {
    $respond(400, ['error' => $e->getMessage()]);
} catch (\Throwable $e) {
    $respond(500, ['error' => 'Не удалось повторить заказ, попробуйте ещё раз']);
}
