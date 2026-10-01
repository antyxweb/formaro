<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Formaro\Cabinet\Repository\ProductRepository;
use Formaro\Cabinet\Service\BuyerChatService;

/**
 * «Чаты и сообщения» покупателя (/personal/messages/): диалоги с продавцами
 * (BuyerChatService). Адрес:
 *   ?thread=ID                    — открыть диалог (из уведомления);
 *   ?partner=ID[&order=F-…|&product=ID] — написать продавцу: есть диалог —
 *                                   открыть его, нет — новый (создастся
 *                                   первым сообщением); заказ/товар —
 *                                   о чём вопрос (подсказка над полем ввода).
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
        $newPartner = null;
        $context = ['order' => '', 'product' => 0, 'label' => ''];

        if ($threadId && in_array($threadId, array_column($threads, 'id'), true)) {
            $active = $threadId;
        } elseif ($partnerId) {
            foreach ($threads as $thread) {
                if ($thread['partner']['id'] === $partnerId) {
                    $active = $thread['id'];
                }
            }
            if (!$active) {
                $newPartner = BuyerChatService::partnerForNewThread($userId, $partnerId);
            }
            if ($active || $newPartner) {
                $context = $this->context($partnerId);
            }
        }

        return [
            'threads' => $threads,
            'active' => $active,
            'newPartner' => $newPartner,
            'context' => $context,
            'unread' => array_sum(array_column($threads, 'unread')),
            'maxFiles' => BuyerChatService::MAX_FILES,
            'maxFileSize' => BuyerChatService::MAX_FILE_SIZE,
        ];
    }

    /** Подсказка «Вопрос по заказу F-… / по товару …»; проверяет её сервер при отправке. */
    private function context(int $partnerId): array
    {
        $order = preg_match('/^F-\d+$/', (string)($_GET['order'] ?? '')) ? (string)$_GET['order'] : '';
        $productId = (int)($_GET['product'] ?? 0);
        $label = $order !== '' ? 'Вопрос по заказу ' . $order : '';
        if (!$label && $productId) {
            $product = (new ProductRepository())->findPublic(['ID' => $productId], ['ID' => 'ASC'], 1)[0] ?? null;
            if ($product && (int)$product['partner_id'] === $partnerId) {
                $label = 'Вопрос по товару «' . $product['name'] . '»';
            } else {
                $productId = 0;
            }
        }

        return ['order' => $order, 'product' => $productId, 'label' => $label];
    }
}
