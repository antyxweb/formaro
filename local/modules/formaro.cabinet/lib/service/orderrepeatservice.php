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
            if (!$product || (!$product['is_preorder'] && $product['stock'] <= 0)) {
                $missing[] = $name;
                continue;
            }
            if (!$product['is_preorder'] && $qty > $product['stock']) {
                $reduced[] = $name . ' — ' . $product['stock'] . ' шт. вместо ' . $qty;
                $qty = $product['stock'];
            }
            $items[] = ['id' => (int)$product['id'], 'qty' => $qty];
        }

        return ['items' => $items, 'missing' => $missing, 'reduced' => $reduced];
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
}
