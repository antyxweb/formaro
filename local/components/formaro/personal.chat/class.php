<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Formaro\Cabinet\Service\BuyerChatService;

/**
 * «Чаты и сообщения» покупателя (/personal/messages/): диалоги с продавцами
 * (BuyerChatService). Адрес:
 *   ?thread=ID                    — открыть диалог (из уведомления);
 *   ?partner=ID[&order=F-…|&product=ID] — написать продавцу по теме
 *                                   (заказ, товар или общий вопрос): на каждую
 *                                   тему свой диалог; есть — открыть, нет —
 *                                   черновик (создастся первым сообщением).
 * Сама переписка рисуется script.js из CHAT_DATA; обновление — опросом
 * /local/ajax/chat.php.
 *
 * Данные личные — без кэша компонента.
 */
class FormaroPersonalChatComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        global $USER;
        $userId = (is_object($USER) && $USER->IsAuthorized()) ? (int)$USER->GetID() : 0;
        $this->arResult = ['AUTHORIZED' => $userId > 0, 'DATA' => null];

        if ($userId && Loader::includeModule('iblock') && Loader::includeModule('formaro.cabinet')) {
            $this->arResult['DATA'] = $this->data($userId);
        }

        $this->includeComponentTemplate();
    }

    private function data(int $userId): array
    {
        $threads = BuyerChatService::threads($userId);
        $threadId = (int)($_GET['thread'] ?? 0);
        $partnerId = (int)($_GET['partner'] ?? 0);
        $active = null;
        $draft = null;

        if ($threadId && in_array($threadId, array_column($threads, 'id'), true)) {
            $active = $threadId;
        } elseif ($partnerId && ($partner = BuyerChatService::partner($partnerId))) {
            // Тема из адреса (заказ/товар/общий вопрос): диалог по ней уже
            // есть — открываем, нет — черновик нового (создастся первым сообщением).
            $subject = BuyerChatService::subject($userId, $partnerId, [
                'order' => (string)($_GET['order'] ?? ''),
                'product' => (int)($_GET['product'] ?? 0),
            ]);
            $active = BuyerChatService::findThread($userId, $partnerId, $subject) ?: null;
            if (!$active) {
                $draft = [
                    'partner' => ['id' => $partner['id'], 'name' => $partner['name'], 'url' => $partner['url'], 'logo' => $partner['logo']],
                    'subject' => $subject['subject'],
                    'order' => $subject['order'],
                    'product' => $subject['product_id'],
                ];
            }
        }

        return [
            'threads' => $threads,
            'active' => $active,
            'draft' => $draft,
            'maxFiles' => BuyerChatService::MAX_FILES,
            'maxFileSize' => BuyerChatService::MAX_FILE_SIZE,
        ];
    }
}
