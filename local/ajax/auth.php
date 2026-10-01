<?php
/**
 * Вход, регистрация покупателя, восстановление и смена пароля
 * (formaro:auth.form — на страницах кабинета покупателя вместо «Войти»).
 *
 * POST sessid, action:
 *   login            + email, password, remember          → {ok}
 *   register         + last_name, name, email, phone,
 *                      password, password_repeat, consent  → {ok}
 *   forgot           + email                              → {message}
 *   change_password  + login, checkword, password,
 *                      password_repeat                     → {message}
 * Ошибки — {error}. Логин — e-mail (см. обработчик в init.php).
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Formaro\Cabinet\Service\BuyerAuthService;

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
// Ловушка для ботов (скрытое поле формы регистрации).
if (trim((string)($_POST['website'] ?? '')) !== '') {
    $respond(400, ['error' => 'Не удалось отправить форму']);
}

try {
    switch ((string)($_POST['action'] ?? '')) {
        case 'login':
            BuyerAuthService::login((string)($_POST['email'] ?? ''), (string)($_POST['password'] ?? ''), !empty($_POST['remember']));
            $respond(200, ['ok' => true]);
        case 'register':
            BuyerAuthService::register($_POST);
            $respond(200, ['ok' => true]);
        case 'forgot':
            BuyerAuthService::forgot((string)($_POST['email'] ?? ''));
            $respond(200, ['message' => 'Если такой e-mail зарегистрирован, на него отправлено письмо со ссылкой для смены пароля.']);
        case 'change_password':
            BuyerAuthService::changePassword(
                (string)($_POST['login'] ?? ''),
                (string)($_POST['checkword'] ?? ''),
                (string)($_POST['password'] ?? ''),
                (string)($_POST['password_repeat'] ?? '')
            );
            $respond(200, ['message' => 'Пароль изменён — войдите с новым паролем.']);
        default:
            $respond(400, ['error' => 'Неизвестное действие']);
    }
} catch (\RuntimeException $e) {
    $respond(400, ['error' => $e->getMessage()]);
} catch (\Throwable $e) {
    $respond(500, ['error' => 'Не удалось выполнить действие, попробуйте ещё раз']);
}
