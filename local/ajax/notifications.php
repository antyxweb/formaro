<?php
/**
 * Уведомления покупателя (/personal/notify/, formaro:personal.notifications).
 *
 * POST sessid, action:
 *   read     + ids[] — отметить прочитанными (в т.ч. при клике на уведомление);
 *   read_all         — отметить прочитанными все;
 *   delete   + ids[] — удалить.
 * → {ids: [...], unread: N} или {error}. Только свои — чужие id пропускаются
 * (NotificationRepository::forBuyer()).
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Formaro\Cabinet\Repository\NotificationRepository;

header('Content-Type: application/json; charset=utf-8');

$respond = static function (int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    die();
};

if (!Loader::includeModule('formaro.cabinet')) {
    $respond(500, ['error' => 'formaro.cabinet module not found']);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $respond(405, ['error' => 'Method not allowed']);
}

global $USER;
$userId = (is_object($USER) && $USER->IsAuthorized()) ? (int)$USER->GetID() : 0;
if (!$userId) {
    $respond(401, ['error' => 'Войдите, чтобы работать с уведомлениями']);
}
if (!check_bitrix_sessid()) {
    $respond(403, ['error' => 'Сессия устарела — обновите страницу']);
}

$repo = NotificationRepository::forBuyer();
$ids = array_values(array_filter(array_map('intval', (array)($_POST['ids'] ?? []))));

try {
    switch ((string)($_POST['action'] ?? '')) {
        case 'read':
            $repo->markReadMany($userId, $ids);
            break;
        case 'read_all':
            $ids = array_column($repo->listOwn($userId), 'id');
            $repo->markAllRead($userId);
            break;
        case 'delete':
            $ids = $repo->deleteMany($userId, $ids);
            break;
        default:
            $respond(400, ['error' => 'Неизвестное действие']);
    }
} catch (\Throwable $e) {
    $respond(500, ['error' => 'Не удалось выполнить действие, попробуйте ещё раз']);
}

$respond(200, ['ids' => $ids, 'unread' => $repo->countUnread($userId)]);
