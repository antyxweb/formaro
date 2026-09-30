<?php

namespace Formaro\Cabinet\Service;

use CIBlockElement;
use CUser;
use Formaro\Cabinet\Repository\CartRepository;
use Formaro\Cabinet\Repository\CouponRepository;
use Formaro\Cabinet\Repository\DiscountRepository;
use Formaro\Cabinet\Repository\NotificationRepository;
use Formaro\Cabinet\Repository\OrderRepository;
use Formaro\Cabinet\Repository\ProductRepository;

/**
 * Корзина витрины: расчёт (страница /personal/cart/ через
 * /local/ajax/cart_list.php) и оформление заказа (/local/ajax/checkout.php).
 *
 * Расчёт — товары по поставщикам с ценой для своего количества (скидки
 * «от N штук»/«от суммы» — ProductPricingService) и промокоды: купон
 * партнёра даёт скидку на его товары, по одному купону на поставщика.
 *
 * Оформление — по заказу (HL-блок CabinetOrders) на каждого поставщика
 * отмеченных товаров; в заказе — цены со скидкой партнёра, промокод
 * поставщика, способ доставки/оплаты, данные покупателя из формы (как в
 * блоке «Данные покупателя» карточки заказа в кабинете). Оформленные
 * товары убираются из корзины, партнёру — уведомление в кабинете.
 */
class CartCheckoutService
{
    /** Код с витрины → название в заказе (как в списке карточки заказа в кабинете). */
    public const DELIVERIES = [
        'cdek' => 'Транспортной компанией (СДЭК)',
        'dellin' => 'Деловые линии',
        'pek' => 'ПЭК',
        'other' => 'Другая транспортная компания',
    ];
    public const PAYMENTS = [
        'invoice' => 'По счёту (безнал)',
    ];

    /**
     * @param array $items [{id, qty}] — уже нормализованные (CartRepository::normalize)
     * @param string[] $codes коды промокодов
     * @return array{groups: array, coupons: array}
     *   groups: [{partner: {id, name, url}, items: [...]}] в порядке первого товара;
     *   coupons: [{code, valid, message, partner_id, partner_name, discount_type, value, id}]
     */
    public static function calculate(array $items, array $codes): array
    {
        $products = [];
        if ($items) {
            foreach ((new ProductRepository())->findPublic(['ID' => array_column($items, 'id')], ['ID' => 'ASC'], count($items)) as $p) {
                $products[$p['id']] = $p;
            }
        }
        $activeDiscounts = $items ? (new DiscountRepository())->listActive() : [];

        $groups = [];
        foreach ($items as $item) {
            if (!isset($products[$item['id']])) {
                continue;
            }
            $p = $products[$item['id']];
            $display = ProductPricingService::computeDisplay($p, $activeDiscounts, $item['qty']);
            $groups[(int)$p['partner_id']][] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'sku' => $p['sku'],
                'color' => $p['color'] ?? '',
                'size' => $p['size'] ?? '',
                'url' => $p['public_url'],
                'image' => $p['preview_image'],
                'stock' => $p['stock'],
                'is_preorder' => $p['is_preorder'],
                'price' => $display['price'],
                'old_price' => $display['old_price'],
                'badges' => $display['badges'],
                'qty' => $item['qty'],
                'max' => $p['is_preorder'] ? 0 : $p['stock'],
                'sum' => $display['price'] * $item['qty'],
                'old_sum' => ($display['old_price'] ?? $display['price']) * $item['qty'],
            ];
        }

        $coupons = [];
        $couponRepo = new CouponRepository();
        $codes = array_unique(array_filter(array_map(static fn($c) => strtoupper(trim((string)$c)), $codes), 'strlen'));
        foreach (array_slice($codes, 0, 20) as $code) {
            $found = $couponRepo->findByCode($code);
            $error = $found ? CouponRepository::checkUsable($found) : 'Промокод не найден';
            $coupons[] = [
                'id' => $found ? $found['id'] : 0,
                'code' => $code,
                'valid' => $error === null,
                'message' => $error ?? '',
                'partner_id' => $found ? (int)$found['partner_id'] : 0,
                'partner_name' => '',
                'discount_type' => $found ? $found['discount_type'] : '',
                'value' => $found ? $found['value'] : 0,
            ];
        }

        $partners = self::partners(array_merge(array_keys($groups), array_column($coupons, 'partner_id')));

        $result = [];
        foreach ($groups as $partnerId => $groupItems) {
            $result[] = [
                'partner' => $partners[$partnerId] ?? ['id' => $partnerId, 'name' => 'Другие продавцы', 'url' => ''],
                'items' => $groupItems,
            ];
        }

        foreach ($coupons as &$coupon) {
            $coupon['partner_name'] = $partners[$coupon['partner_id']]['name'] ?? '';
            if ($coupon['valid'] && !isset($groups[$coupon['partner_id']])) {
                $coupon['message'] = 'Промокод действует только на товары продавца «' . $coupon['partner_name'] . '»';
            }
        }
        unset($coupon);

        return ['groups' => $result, 'coupons' => $coupons];
    }

    /** Скидка купона на сумму товаров его поставщика — как на странице корзины. */
    public static function couponDiscount(array $coupon, float $base): float
    {
        if ($base <= 0) {
            return 0;
        }

        return $coupon['discount_type'] === 'percent'
            ? round($base * $coupon['value'] / 100)
            : min((float)$coupon['value'], $base);
    }

    /**
     * Оформить отмеченные товары корзины пользователя.
     *
     * @param int[] $productIds отмеченные товары (берутся из корзины на сервере с её количеством)
     * @param array $buyer name/phone/email/address/comment
     * @return array [{id, order_number, partner_name, total}]
     * @throws \RuntimeException понятная покупателю ошибка
     */
    public static function checkout(int $userId, array $productIds, array $codes, string $delivery, string $payment, array $buyer): array
    {
        if (!isset(self::DELIVERIES[$delivery])) {
            throw new \RuntimeException('Выберите способ доставки');
        }
        if (!isset(self::PAYMENTS[$payment])) {
            throw new \RuntimeException('Выберите способ оплаты');
        }
        $field = static fn(string $key, int $max) => mb_substr(trim((string)($buyer[$key] ?? '')), 0, $max);
        $customer = [
            'name' => $field('name', 255),
            'phone' => $field('phone', 50),
            'email' => $field('email', 255),
            'address' => $field('address', 255),
        ];
        $comment = $field('comment', 2000);
        if ($customer['name'] === '') {
            throw new \RuntimeException('Укажите имя или название компании');
        }
        if (!preg_match('/^\+?[\d\s\-()]{7,20}$/', $customer['phone'])) {
            throw new \RuntimeException('Укажите телефон');
        }
        if ($customer['email'] !== '' && !filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Проверьте e-mail');
        }
        if ($customer['address'] === '') {
            throw new \RuntimeException('Укажите адрес доставки');
        }

        $productIds = array_map('intval', $productIds);
        $cart = new CartRepository();
        $items = array_values(array_filter(
            $cart->listItems($userId),
            static fn(array $item) => in_array($item['id'], $productIds, true)
        ));
        if (!$items) {
            throw new \RuntimeException('Выбранных товаров уже нет в корзине — обновите страницу');
        }

        $calc = self::calculate($items, $codes);
        // По одному действующему купону на поставщика (последний введённый).
        $couponByPartner = [];
        foreach ($calc['coupons'] as $coupon) {
            if ($coupon['valid']) {
                $couponByPartner[$coupon['partner_id']] = $coupon;
            }
        }

        $now = date('c');

        $orderRepo = new OrderRepository();
        $couponRepo = new CouponRepository();
        $notifications = new NotificationRepository();
        $created = [];
        foreach ($calc['groups'] as $group) {
            $partnerId = (int)$group['partner']['id'];
            $orderItems = array_map(static fn(array $item) => [
                'product_id' => $item['id'],
                'name' => $item['name'],
                'sku' => $item['sku'],
                'color' => $item['color'],
                'size' => $item['size'],
                'qty' => $item['qty'],
                'price' => $item['price'],
            ], $group['items']);
            $subtotal = array_sum(array_column($group['items'], 'sum'));
            $coupon = $couponByPartner[$partnerId] ?? null;
            $discount = $coupon ? self::couponDiscount($coupon, $subtotal) : 0;

            $order = $orderRepo->save($partnerId, [
                'status' => 'new',
                'user_id' => $userId,
                'customer' => $customer,
                'items' => $orderItems,
                'subtotal' => $subtotal,
                'total' => max(0, $subtotal - $discount),
                'delivery_method' => self::DELIVERIES[$delivery],
                'payment_method' => self::PAYMENTS[$payment],
                'payment_status' => 'awaiting',
                'customer_comment' => $comment,
                'discount_name' => $coupon ? 'Купон ' . $coupon['code'] : '',
                'coupon_code' => $coupon ? $coupon['code'] : '',
                'discount_amount' => $discount,
                'history' => [['date' => $now, 'text' => 'Заказ оформлен покупателем на сайте', 'author' => 'Покупатель']],
            ]);
            if ($coupon) {
                $couponRepo->incrementUsage($coupon['id']);
            }
            $notifications->add(
                $partnerId,
                'order',
                'Новый заказ ' . $order['order_number'],
                'Покупатель ' . $customer['name'] . ' оформил заказ на сумму ' . number_format($order['total'], 0, ',', ' ') . ' руб.'
            );
            $created[] = [
                'id' => $order['id'],
                'order_number' => $order['order_number'],
                'partner_name' => $group['partner']['name'],
                'total' => $order['total'],
            ];
        }

        $cart->remove($userId, array_column($items, 'id'));

        return $created;
    }

    /**
     * Отмена заказа покупателем («Ваши заказы», /local/ajax/order_cancel.php):
     * только своего и только в статусе «Новый» (дальше заказ уже в работе
     * у продавца). Статус — «Отменён», в историю — запись, купон заказа
     * снова доступен, продавцу — уведомление.
     *
     * @return array заказ после отмены (OrderRepository::toArray())
     * @throws \RuntimeException понятная покупателю ошибка
     */
    public static function cancel(int $userId, int $orderId): array
    {
        $repo = new OrderRepository();
        $order = $orderId ? $repo->get($orderId) : null;
        if (!$order || $order['user_id'] !== $userId) {
            throw new \RuntimeException('Заказ не найден');
        }
        if ($order['status'] === 'cancelled') {
            throw new \RuntimeException('Заказ уже отменён');
        }
        if ($order['status'] !== 'new') {
            throw new \RuntimeException('Заказ уже в работе у продавца — отменить его можно, только связавшись с продавцом');
        }

        $order['status'] = 'cancelled';
        $order['history'][] = ['date' => date('c'), 'text' => 'Заказ отменён покупателем', 'author' => 'Покупатель'];
        $saved = $repo->save($order['partner_id'], $order);

        if ($order['coupon_code']) {
            $couponRepo = new CouponRepository();
            $coupon = $couponRepo->findByCode($order['coupon_code']);
            if ($coupon && $coupon['partner_id'] === $order['partner_id']) {
                $couponRepo->decrementUsage($coupon['id']);
            }
        }
        (new NotificationRepository())->add(
            $order['partner_id'],
            'order',
            'Заказ ' . $order['order_number'] . ' отменён',
            'Покупатель ' . ($order['customer']['name'] ?? '') . ' отменил заказ на сумму ' . number_format($order['total'], 0, ',', ' ') . ' руб.'
        );

        return $saved;
    }

    /**
     * Предзаполнение «Данных покупателя»: каждое поле — из самого свежего
     * заказа пользователя с витрины, где оно заполнено; иначе — из профиля.
     *
     * @return array{name: string, phone: string, email: string, address: string}
     */
    public static function buyerDefaults(int $userId): array
    {
        $data = self::profile($userId);
        $fromOrders = [];
        foreach ((new OrderRepository())->listRecentByUser($userId, 10) as $order) {
            foreach (['name', 'phone', 'email', 'address'] as $key) {
                $value = trim((string)($order['customer'][$key] ?? ''));
                if ($value !== '' && !isset($fromOrders[$key])) {
                    $fromOrders[$key] = $value;
                }
            }
        }

        return array_merge($data, $fromOrders);
    }

    /** Покупатель из профиля пользователя. */
    private static function profile(int $userId): array
    {
        $user = CUser::GetByID($userId)->Fetch() ?: [];
        $name = trim(($user['NAME'] ?? '') . ' ' . ($user['LAST_NAME'] ?? ''));
        $phone = '';
        foreach (['PERSONAL_MOBILE', 'PERSONAL_PHONE', 'WORK_PHONE'] as $field) {
            if (!empty($user[$field])) {
                $phone = $user[$field];
                break;
            }
        }

        return [
            'name' => $name !== '' ? $name : (string)($user['LOGIN'] ?? ''),
            'phone' => $phone,
            'email' => (string)($user['EMAIL'] ?? ''),
            'address' => '',
        ];
    }

    /** Партнёры по id: короткое название (NAME_SHORT), если заполнено;
     *  url — страница партнёра, пусто у неактивного. Также для «Ваших
     *  заказов» (formaro:personal.orders). */
    public static function partners(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) {
            return [];
        }
        $partners = [];
        $res = CIBlockElement::GetList([], ['ID' => $ids, 'CHECK_PERMISSIONS' => 'N'], false, false, ['ID', 'NAME', 'DETAIL_PAGE_URL', 'ACTIVE', 'PROPERTY_NAME_SHORT']);
        while ($row = $res->GetNext()) {
            $shortName = trim((string)($row['~PROPERTY_NAME_SHORT_VALUE'] ?? ''));
            $partners[(int)$row['ID']] = [
                'id' => (int)$row['ID'],
                'name' => $shortName !== '' ? $shortName : $row['~NAME'],
                'url' => $row['ACTIVE'] === 'Y' ? $row['DETAIL_PAGE_URL'] : '',
            ];
        }

        return $partners;
    }
}
