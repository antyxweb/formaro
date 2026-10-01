<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Formaro\Cabinet\Repository\OrderRepository;
use Formaro\Cabinet\Repository\ProductRepository;
use Formaro\Cabinet\Service\CartCheckoutService;

/**
 * «Ваши заказы» покупателя (/personal/orders/). Вёрстка — /html/orders.html:
 * заказы (HL-блок CabinetOrders, UF_USER_ID — оформленные с витрины) с
 * номером, датой, статусом, продавцом, доставкой, оплатой и суммой, товары
 * заказа — каруселью карточек. Цены и количество — из заказа; картинка и
 * ссылка — у товара, если он ещё есть на сайте.
 *
 * Данные личные — без кэша компонента.
 */
class FormaroPersonalOrdersComponent extends CBitrixComponent
{
    private const STATUSES = [
        'new' => ['Новый', 'bg-primary'],
        'processing' => ['В обработке', 'bg-warning'],
        'confirmed' => ['Подтверждён', 'bg-info'],
        'shipped' => ['Отправлен', 'bg-info'],
        'completed' => ['Выполнен', 'bg-success'],
        'cancelled' => ['Отменён', 'bg-secondary'],
    ];
    private const PAYMENT_STATUSES = [
        'awaiting' => ['Ожидает оплаты', 'text-warning'],
        'paid' => ['Оплачен', 'text-success'],
    ];

    public function onPrepareComponentParams($params)
    {
        $params['LIMIT'] = max(1, (int)($params['LIMIT'] ?? 50));
        $params['CATALOG_URL'] = (string)($params['CATALOG_URL'] ?? '/catalog/');

        return $params;
    }

    public function executeComponent()
    {
        global $USER;
        $userId = (is_object($USER) && $USER->IsAuthorized()) ? (int)$USER->GetID() : 0;
        $this->arResult = ['AUTHORIZED' => $userId > 0, 'ORDERS' => []];

        if ($userId && Loader::includeModule('iblock') && Loader::includeModule('formaro.cabinet')) {
            $orders = (new OrderRepository())->listRecentByUser($userId, $this->arParams['LIMIT']);
            $this->arResult['ORDERS'] = $this->build($orders);
        }

        $this->includeComponentTemplate();
    }

    private function build(array $orders): array
    {
        if (!$orders) {
            return [];
        }

        // Товары заказов одним запросом: картинка и ссылка (у тех, что ещё на сайте).
        $productIds = [];
        foreach ($orders as $order) {
            foreach ($order['items'] as $item) {
                $productIds[] = (int)($item['product_id'] ?? 0);
            }
        }
        $productIds = array_values(array_unique(array_filter($productIds)));
        $products = [];
        if ($productIds) {
            foreach ((new ProductRepository())->findPublic(['ID' => $productIds], ['ID' => 'ASC'], count($productIds)) as $p) {
                $products[$p['id']] = $p;
            }
        }
        $partners = CartCheckoutService::partners(array_column($orders, 'partner_id'));

        $result = [];
        foreach ($orders as $order) {
            $items = [];
            foreach ($order['items'] as $item) {
                $product = $products[(int)($item['product_id'] ?? 0)] ?? null;
                $qty = max(1, (int)($item['qty'] ?? 1));
                $price = (float)($item['price'] ?? 0);
                $items[] = [
                    'NAME' => (string)($item['name'] ?? ''),
                    'SKU' => (string)($item['sku'] ?? ''),
                    'COLOR' => (string)($item['color'] ?? ''),
                    'SIZE' => (string)($item['size'] ?? ''),
                    'URL' => $product['public_url'] ?? '',
                    'IMAGE' => $product['preview_image'] ?? '',
                    'PRICE' => $price,
                    'QTY' => $qty,
                    'SUM' => $price * $qty,
                ];
            }

            $status = self::STATUSES[$order['status']] ?? [$order['status'], 'bg-secondary'];
            $payment = self::PAYMENT_STATUSES[$order['payment_status']] ?? [$order['payment_status'], 'text-secondary'];
            $created = strtotime((string)$order['created_at']) ?: 0;
            $result[] = [
                'ID' => $order['id'],
                'NUMBER' => $order['order_number'],
                'DATE' => $created ? FormatDate('j F Y, H:i', $created) : '',
                'STATUS' => $status[0],
                // Отменить может сам покупатель, пока продавец не взял заказ в работу.
                'CAN_CANCEL' => $order['status'] === 'new',
                // Счёт — пока заказ не оплачен (и не отменён).
                'INVOICE_URL' => $order['status'] !== 'cancelled' && $order['payment_status'] !== 'paid'
                    ? '/local/ajax/order_invoice.php?id=' . $order['id']
                    : '',
                // «Сообщить об оплате» — рядом со счётом; если уже сообщал — когда.
                'PAYMENT_NOTICE_DATE' => ($noticeDate = CartCheckoutService::paymentNoticeDate($order)) !== null
                    ? FormatDate('j F, H:i', strtotime($noticeDate) ?: time())
                    : '',
                'STATUS_CLASS' => $status[1],
                'PAYMENT_STATUS' => $payment[0],
                'PAYMENT_STATUS_CLASS' => $payment[1],
                'PARTNER' => $partners[$order['partner_id']] ?? ['name' => 'Продавец', 'url' => ''],
                'DELIVERY' => $order['delivery_method'],
                'PAYMENT' => $order['payment_method'],
                'ADDRESS' => (string)($order['customer']['address'] ?? ''),
                'COMMENT' => (string)$order['customer_comment'],
                'SUBTOTAL' => $order['subtotal'],
                'DISCOUNT' => $order['discount_amount'],
                'COUPON' => (string)$order['coupon_code'],
                'TOTAL' => $order['total'],
                'HISTORY' => $this->history($order['history']),
                'QTY' => array_sum(array_column($items, 'QTY')),
                'ITEMS' => $items,
            ];
        }

        return $result;
    }

    /**
     * «История заказа» для покупателя: записи от покупателя («Вы») и смены
     * статуса продавцом («Продавец»). Свободные комментарии менеджера из
     * кабинета партнёра («Комментарий менеджера») — его внутренние заметки,
     * покупателю не показываются.
     */
    private function history(array $history): array
    {
        $result = [];
        foreach ($history as $entry) {
            $text = (string)($entry['text'] ?? '');
            $author = (string)($entry['author'] ?? '');
            if ($author === 'Менеджер' && strpos($text, 'Статус изменён') !== 0 && strpos($text, 'Заказ создан') !== 0) {
                continue;
            }
            if ($text === CartCheckoutService::PAYMENT_NOTICE_TEXT) {
                $text = 'Вы сообщили об оплате';
            }
            $time = strtotime((string)($entry['date'] ?? '')) ?: 0;
            $result[] = [
                'DATE' => $time ? FormatDate('j F Y, H:i', $time) : '',
                'TEXT' => $text,
                'AUTHOR' => $author === 'Покупатель' ? 'Вы' : 'Продавец',
            ];
        }

        return array_reverse($result); // свежие — сверху
    }
}
