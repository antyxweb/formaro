<?php

namespace Formaro\Cabinet\Service;

use Formaro\Cabinet\Repository\NotificationRepository;

/**
 * Уведомления покупателю (/personal/notify/, formaro:personal.notifications)
 * о его заказах с витрины: заказ оформлен (CartCheckoutService::checkout()),
 * продавец сменил статус заказа или маркетплейс подтвердил оплату
 * (orderUpdated() — из кабинета партнёра при сохранении заказа). Свои
 * действия покупателя (отмена, «Сообщить об оплате») — без уведомления.
 * Заказы без покупателя (созданные продавцом вручную) — тоже.
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
