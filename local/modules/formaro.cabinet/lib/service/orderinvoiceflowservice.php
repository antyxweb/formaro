<?php

namespace Formaro\Cabinet\Service;

use Bitrix\Main\Type\DateTime;
use Formaro\Cabinet\Repository\NotificationRepository;
use Formaro\Cabinet\Repository\OrderRepository;
use Formaro\Cabinet\Repository\PartnerRepository;

/**
 * Согласование заказа и счёт (ТЗ «Финансы», этап 1):
 *
 *  1. Поставщик подтверждает наличие и срок отгрузки в своём кабинете
 *     (confirmBySupplier) — администраторам уведомление.
 *  2. Менеджер площадки, согласовав с клиентом изменения, при
 *     необходимости правит количество и цены (updateItems), затем
 *     выставляет счёт (issueInvoice): с этого момента клиент может скачать
 *     счёт, ему — уведомление и письмо (FORMARO_ORDER_INVOICE).
 *  3. Счёт действителен INVOICE_DAYS рабочих дней (выходные не считаются,
 *     праздники — пока тоже рабочие). Неоплаченный в срок счёт агент
 *     аннулирует (expireOverdue): клиенту — «В связи с неоплатой счёт № …
 *     не действителен» (уведомление и письмо), поставщику и администраторам
 *     — к сведению. Менеджер может выставить счёт заново.
 *
 * Счёт выставлен (invoice_status = issued) — состав, цены и покупателя
 * заказа не меняет ни поставщик, ни менеджер.
 */
final class OrderInvoiceFlowService
{
    public const INVOICE_DAYS = 3;
    private const CLOSED = ['cancelled', 'completed'];

    /** Поставщик: наличие подтверждено, срок отгрузки — свободный текст («2 рабочих дня», «до 15.10»). */
    public static function confirmBySupplier(int $partnerId, int $orderId, string $leadTime): array
    {
        $repo = new OrderRepository();
        $order = $repo->get($orderId);
        if (!$order || !$repo->canEdit($partnerId, $order)) {
            throw new \RuntimeException('Заказ не найден');
        }
        if (in_array($order['status'], self::CLOSED, true) || $order['payment_status'] === 'paid') {
            throw new \RuntimeException('Заказ уже закрыт или оплачен');
        }
        if ($order['invoice_status'] === 'issued') {
            throw new \RuntimeException('Счёт по заказу уже выставлен');
        }
        $leadTime = trim($leadTime);
        if ($leadTime === '') {
            throw new \RuntimeException('Укажите срок отгрузки');
        }

        $history = $order['history'];
        $history[] = self::entry('Поставщик подтвердил наличие товара. Срок отгрузки: ' . $leadTime, 'Менеджер');
        $fields = [
            'UF_SUPPLIER_CONFIRMED_AT' => new DateTime(),
            'UF_SUPPLIER_LEAD_TIME' => $leadTime,
            'UF_HISTORY' => $history,
        ];
        if ($order['status'] === 'new') {
            $fields['UF_STATUS'] = 'processing';
        }
        $order = $repo->updateFields($orderId, $fields);

        NotificationRepository::notifyAdmin(
            'order',
            'Поставщик подтвердил заказ ' . $order['order_number'],
            PartnerRepository::nameOf($partnerId) . ' — товар в наличии, срок отгрузки: ' . $leadTime
                . '. Можно выставлять счёт (' . self::money($order['total']) . ' руб.).',
            'orders/edit/' . $orderId . '/'
        );

        return $order;
    }

    /**
     * Менеджер: количество и цены позиций (согласовано с клиентом) до
     * выставления счёта. $items — [{index, qty, supplier_price}] по позициям
     * заказа: меняется цена поставщика, цена для покупателя — она же с
     * наценкой (MarkupService); позиция с qty = 0 удаляется. Скидка заказа
     * не больше нового подытога поставщика.
     */
    public static function updateItems(int $orderId, array $changes): array
    {
        $repo = new OrderRepository();
        $order = self::editableByManager($repo, $orderId);

        $items = $order['items'];
        foreach ($changes as $change) {
            $i = (int)($change['index'] ?? -1);
            if (!isset($items[$i])) {
                continue;
            }
            $qty = max(0, (int)($change['qty'] ?? $items[$i]['qty']));
            $oldSupplier = (float)($items[$i]['supplier_price'] ?? $items[$i]['price']);
            $supplier = round(max(0, (float)($change['supplier_price'] ?? $oldSupplier)), 2);
            if ($qty === 0) {
                unset($items[$i]);
                continue;
            }
            $items[$i]['qty'] = $qty;
            if (abs($supplier - $oldSupplier) > 0.001 || !isset($items[$i]['supplier_price'])) {
                $items[$i]['supplier_price'] = $supplier;
                $items[$i]['price'] = MarkupService::retail($supplier);
            }
        }
        $items = array_values($items);
        if (!$items) {
            throw new \RuntimeException('В заказе должен остаться хотя бы один товар');
        }

        $subtotal = round(array_sum(array_map(static fn($it) => $it['qty'] * $it['price'], $items)), 2);
        $discount = min((float)$order['discount_amount'], OrderRepository::supplierSubtotal(['items' => $items]));
        $total = round($subtotal - $discount, 2);
        if ($items == $order['items']) {
            return $order;
        }

        $history = $order['history'];
        $history[] = self::entry(
            'Площадка изменила состав заказа по согласованию с покупателем. Сумма: ' . self::money($order['total']) . ' → ' . self::money($total) . ' руб.',
            'Площадка'
        );
        $order = $repo->updateFields($orderId, [
            'UF_ITEMS' => $items,
            'UF_SUBTOTAL' => $subtotal,
            'UF_DISCOUNT_AMOUNT' => $discount,
            'UF_TOTAL' => $total,
            'UF_HISTORY' => $history,
        ]);
        (new NotificationRepository())->add(
            $order['partner_id'],
            'order',
            'Заказ ' . $order['order_number'] . ' изменён площадкой',
            'Состав заказа изменён по согласованию с покупателем. Новая сумма: ' . self::money($order['supplier_total']) . ' руб.',
            'orders/edit/' . $orderId . '/'
        );

        return $order;
    }

    /** Менеджер: выставить счёт (или выставить заново после аннулирования). */
    public static function issueInvoice(int $orderId): array
    {
        $repo = new OrderRepository();
        $order = self::editableByManager($repo, $orderId);
        if ($order['total'] <= 0) {
            throw new \RuntimeException('Сумма заказа — 0, счёт выставить нельзя');
        }

        $now = new DateTime();
        $due = self::dueDate(time());
        $history = $order['history'];
        $history[] = self::entry('Площадка выставила счёт на оплату. Действителен до ' . date('d.m.Y', $due->getTimestamp()), 'Площадка');
        $order = $repo->updateFields($orderId, [
            'UF_INVOICE_STATUS' => 'issued',
            'UF_INVOICE_DATE' => $now,
            'UF_INVOICE_DUE' => $due,
            'UF_HISTORY' => $history,
        ]);

        $dueText = date('d.m.Y', $due->getTimestamp());
        if ($order['user_id']) {
            NotificationRepository::forBuyer()->addForBuyer(
                $order['user_id'],
                'order',
                'Счёт на оплату заказа ' . $order['order_number'],
                'Заказ согласован с поставщиком. Счёт на ' . self::money($order['total']) . ' руб. действителен до ' . $dueText . ' — скачайте его в «Ваших заказах».',
                '/personal/orders/#order-' . $orderId
            );
        }
        self::mail('FORMARO_ORDER_INVOICE', $order, $dueText);
        (new NotificationRepository())->add(
            $order['partner_id'],
            'order',
            'Покупателю выставлен счёт по заказу ' . $order['order_number'],
            'Площадка выставила покупателю счёт по заказу на ' . self::money($order['supplier_total']) . ' руб., действителен до ' . $dueText . '. Отгрузка — после оплаты.',
            'orders/edit/' . $orderId . '/'
        );

        return $order;
    }

    /** Агент: раз в час аннулирует неоплаченные в срок счета. */
    public static function agent(): string
    {
        try {
            self::expireOverdue();
        } catch (\Throwable $e) {
        }

        return '\\' . self::class . '::agent();';
    }

    /** @return int сколько счетов аннулировано */
    public static function expireOverdue(?DateTime $now = null): int
    {
        $repo = new OrderRepository();
        $count = 0;
        foreach ($repo->listOverdueInvoices($now ?? new DateTime()) as $order) {
            $history = $order['history'];
            $history[] = self::entry('Счёт № ' . $order['order_number'] . ' аннулирован: не оплачен в течение ' . self::INVOICE_DAYS . '-х рабочих дней', 'Площадка');
            $order = $repo->updateFields($order['id'], ['UF_INVOICE_STATUS' => 'expired', 'UF_HISTORY' => $history]);

            $text = 'В связи с неоплатой счёт № ' . $order['order_number'] . ' не действителен.';
            if ($order['user_id']) {
                NotificationRepository::forBuyer()->addForBuyer(
                    $order['user_id'],
                    'order',
                    'Счёт № ' . $order['order_number'] . ' не действителен',
                    $text . ' Если заказ актуален, напишите нам — менеджер выставит новый счёт.',
                    '/personal/orders/#order-' . $order['id']
                );
            }
            self::mail('FORMARO_ORDER_INVOICE_EXPIRED', $order, '');
            (new NotificationRepository())->add($order['partner_id'], 'order', 'Счёт по заказу ' . $order['order_number'] . ' аннулирован', $text, 'orders/edit/' . $order['id'] . '/');
            NotificationRepository::notifyAdmin('order', 'Счёт аннулирован: ' . $order['order_number'], $text . ' Покупатель уведомлён.', 'orders/edit/' . $order['id'] . '/');
            $count++;
        }

        return $count;
    }

    /** Конец INVOICE_DAYS-го рабочего дня после $from (суббота и воскресенье не считаются). */
    public static function dueDate(int $from): DateTime
    {
        $ts = $from;
        $left = self::INVOICE_DAYS;
        while ($left > 0) {
            $ts = strtotime('+1 day', $ts);
            if ((int)date('N', $ts) < 6) {
                $left--;
            }
        }

        return DateTime::createFromTimestamp(strtotime(date('Y-m-d 23:59:59', $ts)));
    }

    /** Заказ, который менеджер может менять и по которому может выставить счёт. */
    private static function editableByManager(OrderRepository $repo, int $orderId): array
    {
        $order = $repo->get($orderId);
        if (!$order) {
            throw new \RuntimeException('Заказ не найден');
        }
        if (in_array($order['status'], self::CLOSED, true)) {
            throw new \RuntimeException('Заказ закрыт — счёт и состав не меняются');
        }
        if ($order['payment_status'] === 'paid') {
            throw new \RuntimeException('Заказ уже оплачен');
        }
        if ($order['invoice_status'] === 'issued') {
            throw new \RuntimeException('Счёт уже выставлен — заказ не меняется, пока счёт действует');
        }

        return $order;
    }

    private static function mail(string $event, array $order, string $dueText): void
    {
        $email = trim((string)($order['customer']['email'] ?? ''));
        if ($email === '' && $order['user_id']) {
            $user = \CUser::GetByID($order['user_id'])->Fetch();
            $email = (string)($user['EMAIL'] ?? '');
        }
        if ($email === '') {
            return;
        }
        $server = \Bitrix\Main\Context::getCurrent()->getServer();
        $host = (string)($server->getHttpHost() ?: \COption::GetOptionString('main', 'server_name', ''));
        \CEvent::Send($event, 's1', [
            'EMAIL' => $email,
            'NAME' => (string)($order['customer']['name'] ?? ''),
            'ORDER_NUMBER' => $order['order_number'],
            'TOTAL' => self::money($order['total']),
            'DUE_DATE' => $dueText,
            'PARTNER' => PartnerRepository::nameOf($order['partner_id']),
            'ORDERS_URL' => ($host !== '' ? 'https://' . $host : '') . '/personal/orders/',
        ]);
    }

    private static function entry(string $text, string $author): array
    {
        return ['date' => date('c'), 'text' => $text, 'author' => $author];
    }

    private static function money($value): string
    {
        return number_format((float)$value, 2, ',', ' ');
    }
}
