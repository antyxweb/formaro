<?php

namespace Formaro\Cabinet\Service;

use Formaro\Cabinet\Mail\Mailer;
use Formaro\Cabinet\Repository\NotificationRepository;
use Formaro\Cabinet\Repository\OrderRepository;

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

    public static function orderCreated(array $order): void
    {
        if (empty($order['user_id'])) {
            return;
        }
        NotificationRepository::forBuyer()->addForBuyer(
            (int)$order['user_id'],
            'order',
            'Заказ ' . $order['order_number'] . ' оформлен',
            'Заказ на сумму ' . self::money($order['total']) . ' руб. принят. Мы согласуем наличие и срок отгрузки и выставим счёт на оплату.',
            self::orderLink($order),
            false
        );
        Mailer::sendToUser('FORMARO_ORDER_CREATED', (int)$order['user_id'], [
            'ORDER_NUMBER' => $order['order_number'],
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
            'Вы отменили заказ на сумму ' . self::money($order['total']) . ' руб.',
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

    /**
     * Смена статуса части заказа. $before — до сохранения (null — новая),
     * $after — после. Покупатель знает заказ под общим номером; если частей
     * несколько — сообщаем только то, что ему важно (отгрузка, отмена,
     * выполнение части), внутренние этапы поставщика не шлём.
     */
    public static function orderUpdated(?array $before, array $after): void
    {
        if (!$before || empty($after['user_id']) || $before['status'] === $after['status']) {
            return;
        }
        $parts = (new OrderRepository())->groupOf($after['id']);
        $order = OrderRepository::mergeForBuyer($parts);
        $single = count($parts) === 1;
        $subject = $single ? 'Заказ' : 'Часть заказа';
        $status = self::STATUSES[$after['status']] ?? $after['status'];
        $message = match ($after['status']) {
            'cancelled' => $single ? 'Заказ отменён.' : 'Часть заказа отменена. Новая сумма заказа: ' . self::money($order['total']) . ' руб.',
            'shipped' => $subject . ' передан' . ($single ? '' : 'а') . ' в доставку' . ($after['delivery_method'] ? ': ' . $after['delivery_method'] : '') . '.'
                . (!empty($after['track_number']) ? ' Трек-номер' . (!empty($after['track_carrier']) ? ' ' . $after['track_carrier'] : '') . ': ' . $after['track_number'] . '.' : ''),
            'completed' => $single ? 'Заказ выполнен. Спасибо за покупку!' : 'Часть заказа выполнена.',
            default => $single ? 'Статус заказа изменён на «' . $status . '».' : '',
        };
        if ($message === '') {
            return;
        }
        NotificationRepository::forBuyer()->addForBuyer(
            (int)$after['user_id'],
            'order',
            'Заказ ' . $order['order_number'] . ': ' . ($single ? '' : 'часть заказа — ') . mb_strtolower($status),
            $message,
            self::orderLink($order)
        );
    }

    /** Площадка подтвердила оплату или оформила возврат $amount — одно уведомление на заказ покупателя. */
    public static function paymentChanged(array $order, string $status, float $amount): void
    {
        if (empty($order['user_id']) || !in_array($status, ['paid', 'refunded'], true)) {
            return;
        }
        NotificationRepository::forBuyer()->addForBuyer(
            (int)$order['user_id'],
            'order',
            ($status === 'paid' ? 'Оплата заказа ' . $order['order_number'] . ' подтверждена' : 'Возврат оплаты по заказу ' . $order['order_number']),
            ($status === 'paid' ? 'Маркетплейс получил оплату ' : 'Маркетплейс оформил возврат ') . self::money($amount) . ' руб.',
            self::orderLink($order)
        );
    }

    private static function orderLink(array $order): string
    {
        return '/personal/orders/#order-' . (int)($order['group_id'] ?? $order['id']);
    }

    private static function money($value): string
    {
        return number_format((float)$value, 0, ',', ' ');
    }
}
