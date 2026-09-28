<?php
/**
 * Избранное на витрине (favorites.js).
 *
 * GET  → {authorized, sessid, ids[]} — для гостя ids пустой, избранное он
 *        ведёт в localStorage.
 * POST action=add&id=N | action=remove&id=N | action=merge&ids[]=...
 *      — только для авторизованных и с sessid; ответ как у GET.
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Formaro\Cabinet\Repository\FavoriteRepository;

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
$repo = new FavoriteRepository();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$userId) {
        $respond(401, ['error' => 'Требуется авторизация']);
    }
    if (!check_bitrix_sessid()) {
        $respond(403, ['error' => 'Сессия устарела, обновите страницу']);
    }

    switch ($_POST['action'] ?? '') {
        case 'add':
            $repo->add($userId, (int)($_POST['id'] ?? 0));
            break;
        case 'remove':
            $repo->remove($userId, (int)($_POST['id'] ?? 0));
            break;
        case 'merge':
            $repo->merge($userId, (array)($_POST['ids'] ?? []));
            break;
        default:
            $respond(400, ['error' => 'Неизвестное действие']);
    }
}

$respond(200, [
    'authorized' => (bool)$userId,
    'sessid' => bitrix_sessid(),
    'ids' => $userId ? $repo->listIds($userId) : [],
]);
