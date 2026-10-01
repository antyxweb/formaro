<?php

namespace Formaro\Cabinet\Service;

use Formaro\Cabinet\Repository\NotificationRepository;

/**
 * Уведомления покупателю (/personal/notify/, formaro:personal.notifications)
 * о его заказах с витрины: заказ оформлен (CartCheckoutService::checkout()),
 * продавец сменил статус заказа или маркетплейс подтвердил оплату
 * (orderUpdated() — из кабинета партнёра при сохранении заказа), покупатель
 * сам отменил заказ (orderCancelledByBuyer() — CartCheckoutService::cancel())
 * или сообщил об оплате (paymentReported() — CartCheckoutService::reportPayment()).
 * Заказы без покупателя (созданные продавцом вручную) — без уведомлений.
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
            'Продавец ' . $partnerName . ' получил заказ на сумму ' . self::money($order['total']) . ' руб. и свяжется с вами для подтверждения.',
            self::orderLink($order)
        );
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
                'shipped' => 'Заказ передан в доставку' . ($after['delivery_method'] ? ': ' . $after['delivery_method'] : '') . '.',
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
