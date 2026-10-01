<?php
/**
 * Чаты покупателя с продавцами («Чаты и сообщения», formaro:personal.chat).
 *
 * POST sessid, action:
 *   list                                   — {threads, unread};
 *   send + partner_id, text, attachments (JSON [{name, type, data}]),
 *          order, product                  — {thread, unread};
 *   read + thread_id                       — {unread}.
 * Ошибки — {error}. См. BuyerChatService.
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Formaro\Cabinet\Service\BuyerChatService;

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
    $respond(401, ['error' => 'Войдите, чтобы писать продавцам']);
}
if (!check_bitrix_sessid()) {
    $respond(403, ['error' => 'Сессия устарела — обновите страницу']);
}

try {
    switch ((string)($_POST['action'] ?? '')) {
        case 'list':
            $respond(200, ['threads' => BuyerChatService::threads($userId), 'unread' => BuyerChatService::countUnread($userId)]);
        case 'send':
            $thread = BuyerChatService::send(
                $userId,
                (int)($_POST['partner_id'] ?? 0),
                (string)($_POST['text'] ?? ''),
                json_decode((string)($_POST['attachments'] ?? '[]'), true) ?: [],
                ['order' => (string)($_POST['order'] ?? ''), 'product' => (int)($_POST['product'] ?? 0)]
            );
            $respond(200, ['thread' => $thread, 'unread' => BuyerChatService::countUnread($userId)]);
        case 'read':
            BuyerChatService::markRead($userId, (int)($_POST['thread_id'] ?? 0));
            $respond(200, ['unread' => BuyerChatService::countUnread($userId)]);
        default:
            $respond(400, ['error' => 'Неизвестное действие']);
    }
} catch (\RuntimeException $e) {
    $respond(400, ['error' => $e->getMessage()]);
} catch (\Throwable $e) {
    $respond(500, ['error' => 'Не удалось выполнить действие, попробуйте ещё раз']);
}
