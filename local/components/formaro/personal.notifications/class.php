<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Formaro\Cabinet\Repository\NotificationRepository;

/**
 * «Уведомления» покупателя (/personal/notify/): о его заказах — оформлен,
 * продавец сменил статус, оплата подтверждена (BuyerNotificationService).
 * Список свежими сверху; клик — отметить прочитанным и перейти к заказу;
 * выбор флажками — прочитать/удалить выбранные, «Прочитать все»
 * (script.js → /local/ajax/notifications.php).
 *
 * Данные личные — без кэша компонента.
 */
class FormaroPersonalNotificationsComponent extends CBitrixComponent
{
    /** Тип уведомления => иконка спрайта. */
    private const ICONS = [
        'order' => 'icon-order',
    ];

    public function executeComponent()
    {
        global $USER;
        $userId = (is_object($USER) && $USER->IsAuthorized()) ? (int)$USER->GetID() : 0;
        $this->arResult = ['AUTHORIZED' => $userId > 0, 'ITEMS' => [], 'UNREAD' => 0];

        if ($userId && Loader::includeModule('formaro.cabinet')) {
            foreach (NotificationRepository::forBuyer()->listOwn($userId) as $row) {
                $time = $row['created_at'] ? (strtotime($row['created_at']) ?: 0) : 0;
                $this->arResult['ITEMS'][] = [
                    'ID' => $row['id'],
                    'TITLE' => (string)$row['title'],
                    'MESSAGE' => (string)$row['message'],
                    'LINK' => (string)$row['link'],
                    'IS_READ' => $row['is_read'],
                    'ICON' => self::ICONS[$row['type']] ?? 'icon-bell',
                    'DATE' => $time ? FormatDate('j F Y, H:i', $time) : '',
                ];
                $this->arResult['UNREAD'] += $row['is_read'] ? 0 : 1;
            }
        }

        $this->includeComponentTemplate();
    }
}
