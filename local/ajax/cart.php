<?php
/**
 * Корзина витрины (js/cart.js).
 *
 * GET  → {authorized, sessid, items: [{id, qty}]} — для гостя items пустой,
 *        корзину он ведёт в localStorage.
 * POST action=set&id=N&qty=M (M <= 0 — убрать) | action=remove&ids[]=…
 *      | action=merge&items[i][id]=…&items[i][qty]=… — только для
 *      авторизованных и с sessid; ответ как у GET.
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Formaro\Cabinet\Repository\CartRepository;

header('Content-Type: application/json; charset=utf-8');

$respond = static function (int $status, array $body): void {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    die();
};

if (!Loader::includeModule('formaro.cabinet')) {
    $respond(500, ['error' => 'formaro.cabinet module not found']);
}

global $USER;
$userId = (is_object($USER) && $USER->IsAuthorized()) ? (int)$USER->GetID() : 0;
$repo = new CartRepository();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$userId) {
        $respond(401, ['error' => 'Требуется авторизация']);
    }
    if (!check_bitrix_sessid()) {
        $respond(403, ['error' => 'Сессия устарела, обновите страницу']);
    }

    switch ($_POST['action'] ?? '') {
        case 'set':
            $repo->set($userId, (int)($_POST['id'] ?? 0), (int)($_POST['qty'] ?? 0));
            break;
        case 'remove':
            $repo->remove($userId, (array)($_POST['ids'] ?? []));
            break;
        case 'merge':
            $repo->merge($userId, array_values((array)($_POST['items'] ?? [])));
            break;
        default:
            $respond(400, ['error' => 'Неизвестное действие']);
    }
}

$respond(200, [
    'authorized' => (bool)$userId,
    'sessid' => bitrix_sessid(),
    'items' => $userId ? $repo->listItems($userId) : [],
]);
