<?php
/**
 * «Ваш профиль» (formaro:personal.profile): сохранение.
 *
 * POST sessid, action:
 *  - contacts — last_name, name, second_name, email, phone → {contacts};
 *  - legal — реквизиты покупателя (BuyerProfileRepository::LEGAL_FIELDS) → {legal};
 *  - password — current, new, repeat → {ok}.
 * Ошибка → {error}. Только авторизованный, только свой профиль.
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Formaro\Cabinet\Repository\BuyerProfileRepository;

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
    $respond(401, ['error' => 'Войдите, чтобы изменить профиль']);
}
if (!check_bitrix_sessid()) {
    $respond(403, ['error' => 'Сессия устарела — обновите страницу']);
}

$repo = new BuyerProfileRepository();
try {
    switch ((string)($_POST['action'] ?? '')) {
        case 'contacts':
            $respond(200, ['contacts' => $repo->saveContacts($userId, $_POST)]);
            // no break — respond() завершает запрос
        case 'legal':
            $respond(200, ['legal' => $repo->saveLegal($userId, $_POST)]);
            // no break
        case 'password':
            $repo->changePassword($userId, (string)($_POST['current'] ?? ''), (string)($_POST['new'] ?? ''), (string)($_POST['repeat'] ?? ''));
            $respond(200, ['ok' => true]);
            // no break
        default:
            $respond(400, ['error' => 'Неизвестное действие']);
    }
} catch (\RuntimeException $e) {
    $respond(400, ['error' => $e->getMessage()]);
}
