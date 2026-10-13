<?php
/**
 * «Управление рассылками» на сайте (formaro:mailing.settings, MailingService).
 *
 * POST sessid, u + t (ссылка из письма; без них — e-mail вошедшего), action:
 *  - save — audience (buyer|partner), channels[] — включённые категории;
 *  - unsubscribe — id подписки на новости;
 *  - unsubscribe_all — все категории и все подписки.
 * → {state} (MailingService::state) или {error}.
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Formaro\Cabinet\Service\MailingService;
use Formaro\Cabinet\Service\SubscriptionService;

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
if (!check_bitrix_sessid()) {
    $respond(403, ['error' => 'Сессия устарела — обновите страницу']);
}

global $USER;
$email = null;
if (isset($_POST['u']) || isset($_POST['t'])) {
    $email = MailingService::emailFromLink((string)($_POST['u'] ?? ''), (string)($_POST['t'] ?? ''));
    if ($email === null) {
        $respond(403, ['error' => 'Ссылка устарела — откройте её из последнего письма']);
    }
} elseif (is_object($USER) && $USER->IsAuthorized()) {
    $email = MailingService::normalize((string)$USER->GetEmail());
}
if (!$email) {
    $respond(401, ['error' => 'Войдите, чтобы настроить рассылки']);
}

$audiences = MailingService::audiencesFor($email);
try {
    switch ((string)($_POST['action'] ?? '')) {
        case 'save':
            $audience = (string)($_POST['audience'] ?? '');
            if (!in_array($audience, $audiences, true)) {
                $respond(400, ['error' => 'Не удалось сохранить настройки рассылок']);
            }
            MailingService::setChannels($email, $audience, (array)($_POST['channels'] ?? []));
            break;
        case 'unsubscribe':
            SubscriptionService::unsubscribe($email, (int)($_POST['id'] ?? 0));
            break;
        case 'unsubscribe_all':
            MailingService::unsubscribeAll($email);
            break;
        default:
            $respond(400, ['error' => 'Неизвестное действие']);
    }
    $respond(200, ['state' => MailingService::state($email, $audiences)]);
} catch (\RuntimeException $e) {
    $respond(400, ['error' => $e->getMessage()]);
} catch (\Throwable $e) {
    $respond(500, ['error' => 'Не удалось сохранить, попробуйте ещё раз']);
}
