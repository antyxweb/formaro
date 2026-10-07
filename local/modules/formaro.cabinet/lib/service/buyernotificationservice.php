<?php

namespace Formaro\Cabinet\Service;

use Formaro\Cabinet\Mail\Mailer;
use Formaro\Cabinet\Repository\NotificationRepository;

/**
 * Уведомления покупателю (/personal/notify/, formaro:personal.notifications)
 * о его заказах с витрины: заказ оформлен (CartCheckoutService::checkout()),
 * продавец сменил статус заказа или маркетплейс подтвердил оплату
 * (orderUpdated() — из кабинета партнёра при сохранении заказа), покупатель
 * сам отменил заказ (orderCancelledByBuyer() — CartCheckoutService::cancel())
 * или сообщил об оплате (paymentReported() — CartCheckoutService::reportPayment()).
 * Заказы без покупателя (созданные продавцом вручную) — без уведомлений.
 *
 * Уведомления дублируются письмом (NotificationRepository); «Заказ
 * оформлен» — своё письмо с составом заказа (FORMARO_ORDER_CREATED).
 */
class BuyerNotificationService
{
    /** Названия статусов — как в «Ваших заказах» (formaro:personal.orders). */
    public const STATUSES = [
        'new' => 'Новый',
        'processing' => 'В обработке',
        'confirmed' => 'Подтверждён',
        'shipped' => 'Отправлен',
        'completed' => 'Выполнен',
        'cancelled' => 'Отменён',
    ];

    public static function orderCreated(array $order, string $partnerName): void
    {
        if (empty($order['user_id'])) {
            return;
        }
        NotificationRepository::forBuyer()->addForBuyer(
            (int)$order['user_id'],
            'order',
            'Заказ ' . $order['order_number'] . ' оформлен',
            'Продавец ' . $partnerName . ' получил заказ на сумму ' . self::money($order['total']) . ' руб. Мы согласуем наличие и срок отгрузки и выставим счёт на оплату.',
            self::orderLink($order),
            false
        );
        Mailer::sendToUser('FORMARO_ORDER_CREATED', (int)$order['user_id'], [
            'ORDER_NUMBER' => $order['order_number'],
            'PARTNER' => $partnerName,
            'TOTAL' => self::money($order['total']),
            'ITEMS' => self::itemsTable($order),
            'DELIVERY' => (string)$order['delivery_method'] ?: 'по согласованию',
            'ORDERS_URL' => Mailer::url(self::orderLink($order)),
        ]);
    }

    /** Состав заказа для письма — таблица с инлайн-стилями (почтовые клиенты). */
    private static function itemsTable(array $order): string
    {
        $cell = 'padding:10px 0;border-bottom:1px solid #e5e7eb;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:20px;vertical-align:top;';
        $rows = '';
        foreach ($order['items'] as $item) {
            $qty = max(1, (int)($item['qty'] ?? 1));
            $price = (float)($item['price'] ?? 0);
            $props = implode(', ', array_filter([
                ($item['sku'] ?? '') !== '' ? 'арт. ' . $item['sku'] : '',
                (string)($item['color'] ?? ''),
                ($item['size'] ?? '') !== '' ? 'размер ' . $item['size'] : '',
            ]));
            $rows .= '<tr><td style="' . $cell . 'color:#111111;">' . htmlspecialcharsbx((string)($item['name'] ?? ''))
                . ($props !== '' ? '<br><span style="font-size:12px;color:#6b7280;">' . htmlspecialcharsbx($props) . '</span>' : '')
                . '</td><td style="' . $cell . 'padding-left:12px;white-space:nowrap;color:#6b7280;" align="right">' . $qty . ' × ' . self::money($price) . ' ₽</td>'
                . '<td style="' . $cell . 'padding-left:12px;white-space:nowrap;color:#111111;" align="right">' . self::money($price * $qty) . ' ₽</td></tr>';
        }
        $total = 'padding:12px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:20px;';
        if ((float)$order['discount_amount'] > 0) {
            $rows .= '<tr><td colspan="2" style="' . $total . 'color:#6b7280;">Скидка' . ((string)$order['coupon_code'] !== '' ? ' по купону ' . htmlspecialcharsbx((string)$order['coupon_code']) : '') . '</td>'
                . '<td style="' . $total . 'white-space:nowrap;color:#6b7280;" align="right">−' . self::money($order['discount_amount']) . ' ₽</td></tr>';
        }
        $rows .= '<tr><td colspan="2" style="' . $total . 'font-weight:bold;color:#111111;">Итого</td>'
            . '<td style="' . $total . 'white-space:nowrap;font-weight:bold;color:#111111;" align="right">' . self::money($order['total']) . ' ₽</td></tr>';

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">' . $rows . '</table>';
    }

    /** Покупатель отменил заказ в «Ваших заказах» — запись в ленту, что отмена прошла. */
    public static function orderCancelledByBuyer(array $order): void
    {
        if (empty($order['user_id'])) {
            return;
        }
        NotificationRepository::forBuyer()->addForBuyer(
            (int)$order['user_id'],
            'order',
            'Заказ ' . $order['order_number'] . ' отменён',
            'Вы отменили заказ на сумму ' . self::money($order['total']) . ' руб. Продавец получил уведомление об отмене.',
            self::orderLink($order)
        );
    }

    /** Покупатель нажал «Сообщить об оплате» — запись в ленту, что сообщение принято. */
    public static function paymentReported(array $order): void
    {
        if (empty($order['user_id'])) {
            return;
        }
        NotificationRepository::forBuyer()->addForBuyer(
            (int)$order['user_id'],
            'order',
            'Вы сообщили об оплате заказа ' . $order['order_number'],
            'Маркетплейс проверит поступление ' . self::money($order['total']) . ' руб. и подтвердит оплату — об этом придёт уведомление.',
            self::orderLink($order)
        );
    }

    /** Продавец оформил заказ в кабинете, и заказ привязан к покупателю сайта (по e-mail/телефону). */
    public static function orderCreatedByPartner(array $order): void
    {
        if (empty($order['user_id'])) {
            return;
        }
        $partner = CartCheckoutService::partners([$order['partner_id']])[$order['partner_id']] ?? ['name' => 'Продавец'];
        NotificationRepository::forBuyer()->addForBuyer(
            (int)$order['user_id'],
            'order',
            'Новый заказ ' . $order['order_number'],
            'Продавец ' . $partner['name'] . ' оформил для вас заказ на сумму ' . self::money($order['total']) . ' руб.',
            self::orderLink($order)
        );
    }

    /** Продавец повторил заказ покупателя («Повторить заказ» в кабинете) — новый заказ. */
    public static function orderRepeatedByPartner(array $order, array $source): void
    {
        if (empty($order['user_id'])) {
            return;
        }
        $partner = CartCheckoutService::partners([$order['partner_id']])[$order['partner_id']] ?? ['name' => 'Продавец'];
        NotificationRepository::forBuyer()->addForBuyer(
            (int)$order['user_id'],
            'order',
            'Новый заказ ' . $order['order_number'],
            'Продавец ' . $partner['name'] . ' повторил ваш заказ ' . $source['order_number'] . ' — новый заказ на сумму ' . self::money($order['total']) . ' руб. ждёт подтверждения и оплаты.',
            self::orderLink($order)
        );
    }

    /** $before — заказ до сохранения (null — новый), $after — после. */
    public static function orderUpdated(?array $before, array $after): void
    {
        if (!$before || empty($after['user_id'])) {
            return;
        }
        $repo = NotificationRepository::forBuyer();
        $userId = (int)$after['user_id'];

        if ($before['status'] !== $after['status']) {
            $status = self::STATUSES[$after['status']] ?? $after['status'];
            $message = match ($after['status']) {
                'cancelled' => 'Продавец отменил заказ.',
                'shipped' => 'Заказ передан в доставку' . ($after['delivery_method'] ? ': ' . $after['delivery_method'] : '') . '.'
                    . (!empty($after['track_number']) ? ' Трек-номер' . (!empty($after['track_carrier']) ? ' ' . $after['track_carrier'] : '') . ': ' . $after['track_number'] . '.' : ''),
                'completed' => 'Заказ выполнен. Спасибо за покупку!',
                default => 'Статус заказа изменён на «' . $status . '».',
            };
            $repo->addForBuyer($userId, 'order', 'Заказ ' . $after['order_number'] . ': ' . mb_strtolower($status), $message, self::orderLink($after));
        }

        if ($before['payment_status'] !== 'paid' && $after['payment_status'] === 'paid') {
            $repo->addForBuyer(
                $userId,
                'order',
                'Оплата заказа ' . $after['order_number'] . ' подтверждена',
                'Маркетплейс получил оплату ' . self::money($after['total']) . ' руб.',
                self::orderLink($after)
            );
        }
        if ($before['payment_status'] !== 'refunded' && $after['payment_status'] === 'refunded') {
            $repo->addForBuyer(
                $userId,
                'order',
                'Возврат оплаты по заказу ' . $after['order_number'],
                'Маркетплейс оформил возврат ' . self::money($after['total']) . ' руб.',
                self::orderLink($after)
            );
        }
    }

    private static function orderLink(array $order): string
    {
        return '/personal/orders/#order-' . (int)$order['id'];
    }

    private static function money($value): string
    {
        return number_format((float)$value, 0, ',', ' ');
    }
}
