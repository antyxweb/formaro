<?php
/**
 * Подписка на рассылку — формы «Будьте в курсе» (form=news) и «Будь всегда
 * в форме!» в подвале (form=footer). См. SubscriptionService.
 *
 * POST sessid, form, email, topic (id раздела каталога, необязательно),
 * partner (раздел партнёра — подписка на его обновления, без темы),
 * consent, page, website (ловушка для ботов) → {message} или {error}.
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
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
if (trim((string)($_POST['website'] ?? '')) !== '') {
    $respond(400, ['error' => 'Не удалось оформить подписку']);
}

try {
    $isNew = SubscriptionService::subscribe(
        (string)($_POST['form'] ?? ''),
        (string)($_POST['email'] ?? ''),
        (int)($_POST['topic'] ?? 0),
        !empty($_POST['consent']),
        (string)($_POST['page'] ?? ''),
        (int)($_POST['partner'] ?? 0)
    );
    $respond(200, ['message' => $isNew ? 'Спасибо! Вы подписаны на рассылку.' : 'Вы уже подписаны — спасибо!']);
} catch (\RuntimeException $e) {
    $respond(400, ['error' => $e->getMessage()]);
} catch (\Throwable $e) {
    $respond(500, ['error' => 'Не удалось оформить подписку, попробуйте ещё раз']);
}
