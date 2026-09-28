<?php
/**
 * История поиска по каталогу (компонент formaro:catalog.search).
 *
 * GET  → {authorized, sessid, items[]} — для гостя items пустой, историю
 *        он ведёт в localStorage.
 * POST action=add&q=...          — добавить запрос;
 *      action=merge&queries[]=... — перенести гостевую историю в аккаунт;
 *      action=clear               — очистить.
 *      Все POST — только для авторизованных и с sessid; ответ как у GET.
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Formaro\Cabinet\Repository\SearchHistoryRepository;

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
$repo = new SearchHistoryRepository();
$limit = 5;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$userId) {
        $respond(401, ['error' => 'Требуется авторизация']);
    }
    if (!check_bitrix_sessid()) {
        $respond(403, ['error' => 'Сессия устарела, обновите страницу']);
    }

    switch ($_POST['action'] ?? '') {
        case 'add':
            $repo->add($userId, (string)($_POST['q'] ?? ''));
            break;
        case 'merge':
            $repo->merge($userId, (array)($_POST['queries'] ?? []));
            break;
        case 'clear':
            $repo->clear($userId);
            break;
        default:
            $respond(400, ['error' => 'Неизвестное действие']);
    }
}

$respond(200, [
    'authorized' => (bool)$userId,
    'sessid' => bitrix_sessid(),
    'items' => $userId ? $repo->listForUser($userId, $limit) : [],
]);
