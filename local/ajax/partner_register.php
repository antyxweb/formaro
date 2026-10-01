<?php
/**
 * Регистрация партнёра («Стать партнером», formaro:partner.register).
 *
 * POST sessid и
 *   гость: company, inn, last_name, name, phone, email, password,
 *          password_repeat, consent, website (ловушка для ботов — пусто);
 *   вошедший покупатель: action=from_profile — кабинет из данных профиля
 *          (кнопка «Создать кабинет партнёра»).
 * → {partner_id, redirect} или {error}. См. PartnerRegistrationService.
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Formaro\Cabinet\Service\PartnerRegistrationService;

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
if (!check_bitrix_sessid()) {
    $respond(403, ['error' => 'Сессия устарела — обновите страницу']);
}
// Ловушка для ботов: скрытое поле заполняют только они.
if (trim((string)($_POST['website'] ?? '')) !== '') {
    $respond(400, ['error' => 'Не удалось отправить заявку']);
}

try {
    $partnerId = ($_POST['action'] ?? '') === 'from_profile'
        ? PartnerRegistrationService::createFromBuyerProfile()
        : PartnerRegistrationService::register($_POST);
    $respond(200, ['partner_id' => $partnerId, 'redirect' => '/cabinet/profile/']);
} catch (\RuntimeException $e) {
    $respond(400, ['error' => $e->getMessage()]);
} catch (\Throwable $e) {
    $respond(500, ['error' => 'Не удалось отправить заявку, попробуйте ещё раз']);
}
