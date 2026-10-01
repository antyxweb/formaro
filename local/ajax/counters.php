<?php
/**
 * Счётчики непрочитанного для вошедшего покупателя — живое обновление
 * (js/live-counters.js, раз в 20 с): уведомления и ответы продавцов в чатах.
 *
 * GET → {notify, messages}; гостю — {notify: 0, messages: 0}.
 */
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('STOP_STATISTICS', true);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Formaro\Cabinet\Repository\NotificationRepository;
use Formaro\Cabinet\Service\BuyerChatService;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

global $USER;
$userId = (is_object($USER) && $USER->IsAuthorized()) ? (int)$USER->GetID() : 0;
$result = ['notify' => 0, 'messages' => 0];
if ($userId && Loader::includeModule('formaro.cabinet')) {
    $result['notify'] = NotificationRepository::forBuyer()->countUnread($userId);
    $result['messages'] = BuyerChatService::countUnread($userId);
}
echo json_encode($result);
