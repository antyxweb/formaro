<?php

namespace Formaro\Cabinet\Service;

use Formaro\Cabinet\Repository\CartRepository;
use Formaro\Cabinet\Repository\OrderRepository;
use Formaro\Cabinet\Repository\ProductRepository;

/**
 * «Повторить заказ» в «Ваших заказах» (выполненный или отменённый заказ,
 * /local/ajax/order_repeat.php): товары заказа — снова в корзину.
 *
 * Товар доступен, если он есть на витрине (активен) и в наличии или
 * продаётся под заказ; количество — как в заказе, но не больше остатка
 * (кроме «под заказ»). check() — что добавится, чего нет, где меньше;
 * repeat() — добавляет доступное (в корзине уже есть — берётся большее
 * количество, как при переносе гостевой корзины).
 *
 * В кабинете партнёра то же самое — checkForPartner()/repeatForPartner():
 * вместо корзины — новый заказ продавца с доступными товарами.
 */
class OrderRepeatService
{
    public const STATUSES = ['completed', 'cancelled'];

    /**
     * @return array{items: array<int, array{id: int, qty: int}>, missing: string[], reduced: string[]}
     * @throws \RuntimeException
     */
    public static function check(int $userId, int $orderId): array
    {
        $order = $orderId ? (new OrderRepository())->get($orderId) : null;
        if (!$order || $order['user_id'] !== $userId) {
            throw new \RuntimeException('Заказ не найден');
        }
        if (!in_array($order['status'], self::STATUSES, true)) {
            throw new \RuntimeException('Повторить можно выполненный или отменённый заказ');
        }

        $result = self::resolve($order);
        $result['items'] = array_map(static fn($i) => ['id' => (int)$i['product']['id'], 'qty' => $i['qty']], $result['items']);

        return $result;
    }

    /**
     * Кабинет партнёра: что войдёт в новый заказ — товары этого продавца.
     *
     * @return array{items: array, missing: string[], reduced: string[], has_buyer: bool}
     * @throws \RuntimeException
     */
    public static function checkForPartner(int $partnerId, int $orderId): array
    {
        $order = self::partnerOrder($partnerId, $orderId);

        // has_buyer — заказ с витрины: новый привяжется к покупателю, ему — уведомление.
        return self::resolve($order, $partnerId) + ['has_buyer' => $order['user_id'] > 0];
    }

    /**
     * Кабинет партнёра: новый заказ («Новый», ожидает оплаты) с доступными
     * товарами по текущим ценам, тем же покупателем, доставкой и оплатой.
     * Исходный заказ был с витрины — новый привязан к тому же покупателю
     * (появится в его «Ваших заказах») и ему уходит уведомление.
     *
     * @return array новый заказ (OrderRepository::get())
     * @throws \RuntimeException
     */
    public static function repeatForPartner(int $partnerId, int $orderId): array
    {
        $order = self::partnerOrder($partnerId, $orderId);
        $items = self::resolve($order, $partnerId)['items'];
        if (!$items) {
            throw new \RuntimeException('Товаров из этого заказа сейчас нет в продаже');
        }

        $orderItems = array_map(static fn(array $i) => [
            'product_id' => (int)$i['product']['id'],
            'name' => (string)$i['product']['name'],
            'sku' => (string)($i['product']['sku'] ?? ''),
            'color' => (string)($i['product']['color'] ?? ''),
            'size' => (string)($i['product']['size'] ?? ''),
            'qty' => $i['qty'],
            'price' => (float)$i['product']['price'],
        ], $items);
        $subtotal = array_sum(array_map(static fn($i) => $i['price'] * $i['qty'], $orderItems));

        $new = (new OrderRepository())->save($partnerId, [
            'status' => 'new',
            'user_id' => (int)$order['user_id'],
            'payment_status' => 'awaiting',
            'customer' => $order['customer'],
            'items' => $orderItems,
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'delivery_method' => $order['delivery_method'],
            'payment_method' => $order['payment_method'],
            'customer_comment' => '',
            'history' => [[
                'date' => date('c'),
                'text' => 'Заказ создан повтором заказа ' . $order['order_number'],
                'author' => 'Менеджер',
            ]],
        ]);
        BuyerNotificationService::orderRepeatedByPartner($new, $order);

        return $new;
    }

    /** @return int[] id добавленных товаров */
    public static function repeat(int $userId, int $orderId): array
    {
        $items = self::check($userId, $orderId)['items'];
        if (!$items) {
            throw new \RuntimeException('Товаров из этого заказа сейчас нет в продаже');
        }
        (new CartRepository())->merge($userId, $items);

        return array_column($items, 'id');
    }

    /** @throws \RuntimeException */
    private static function partnerOrder(int $partnerId, int $orderId): array
    {
        $repo = new OrderRepository();
        $order = $orderId ? $repo->get($orderId) : null;
        if (!$order || !$repo->canEdit($partnerId, $order)) {
            throw new \RuntimeException('Заказ не найден');
        }
        if (!in_array($order['status'], self::STATUSES, true)) {
            throw new \RuntimeException('Повторить можно выполненный или отменённый заказ');
        }

        return $order;
    }

    /**
     * Доступные товары заказа (на витрине и в наличии или под заказ;
     * $partnerId — только товары этого продавца), количество — не больше
     * остатка.
     *
     * @return array{items: array<int, array{product: array, qty: int}>, missing: string[], reduced: string[]}
     */
    private static function resolve(array $order, int $partnerId = 0): array
    {
        $ids = array_values(array_unique(array_filter(array_map(static fn($i) => (int)($i['product_id'] ?? 0), $order['items']))));
        $products = [];
        if ($ids) {
            foreach ((new ProductRepository())->findPublic(['ID' => $ids], ['ID' => 'ASC'], count($ids)) as $p) {
                $products[(int)$p['id']] = $p;
            }
        }

        $items = [];
        $missing = [];
        $reduced = [];
        foreach ($order['items'] as $item) {
            $name = (string)($item['name'] ?? 'Товар');
            $product = $products[(int)($item['product_id'] ?? 0)] ?? null;
            $qty = max(1, (int)($item['qty'] ?? 1));
            if (!$product
                || ($partnerId && (int)$product['partner_id'] !== $partnerId)
                || (!$product['is_preorder'] && $product['stock'] <= 0)) {
                $missing[] = $name;
                continue;
            }
            if (!$product['is_preorder'] && $qty > $product['stock']) {
                $reduced[] = $name . ' — ' . $product['stock'] . ' шт. вместо ' . $qty;
                $qty = $product['stock'];
            }
            $items[] = ['product' => $product, 'qty' => $qty];
        }

        return ['items' => $items, 'missing' => $missing, 'reduced' => $reduced];
    }
}
