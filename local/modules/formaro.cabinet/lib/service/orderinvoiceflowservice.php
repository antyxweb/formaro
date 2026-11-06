<?php

namespace Formaro\Cabinet\Service;

use Bitrix\Main\Type\DateTime;
use Formaro\Cabinet\Mail\Mailer;
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
 *
 * Заказ покупателя из нескольких частей (по поставщикам, OrderRepository::
 * groupOf()) — счёт один: выставляется, аннулируется и оплачивается сразу
 * у всех частей. Покупателю — одно уведомление на полную сумму, поставщику —
 * о его части и в его ценах. Подтверждает и правит состав — каждая часть отдельно.
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

        $group = $repo->groupOf($orderId);
        $waiting = array_filter($group, static fn($p) => !$p['supplier_confirmed_at'] && !in_array($p['status'], self::CLOSED, true));
        NotificationRepository::notifyAdmin(
            'order',
            'Поставщик подтвердил заказ ' . $order['order_number'],
            PartnerRepository::nameOf($partnerId) . ' — товар в наличии, срок отгрузки: ' . $leadTime . '. '
                . ($waiting
                    ? 'Ждём подтверждения остальных поставщиков заказа: ' . count($waiting) . '.'
                    : 'Можно выставлять счёт (' . self::money(OrderRepository::mergeForBuyer($group)['total']) . ' руб.).'),
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
        // Без сумм: историю видят и покупатель, и поставщик — у каждого свои цены.
        $history[] = self::entry('Площадка изменила состав заказа по согласованию с покупателем', 'Площадка');
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

    /** Менеджер: выставить общий счёт заказа покупателя (или выставить заново после аннулирования). */
    public static function issueInvoice(int $orderId): array
    {
        $repo = new OrderRepository();
        self::editableByManager($repo, $orderId);
        $parts = array_filter($repo->groupOf($orderId), static fn($p) => !in_array($p['status'], self::CLOSED, true));
        foreach ($parts as $part) {
            self::editableByManager($repo, $part['id']);
        }
        if (OrderRepository::mergeForBuyer($repo->groupOf($orderId))['total'] <= 0) {
            throw new \RuntimeException('Сумма заказа — 0, счёт выставить нельзя');
        }

        $now = new DateTime();
        $due = self::dueDate(time());
        $dueText = date('d.m.Y', $due->getTimestamp());
        foreach ($parts as $part) {
            $history = $part['history'];
            $history[] = self::entry('Площадка выставила счёт на оплату. Действителен до ' . $dueText, 'Площадка');
            $part = $repo->updateFields($part['id'], [
                'UF_INVOICE_STATUS' => 'issued',
                'UF_INVOICE_DATE' => $now,
                'UF_INVOICE_DUE' => $due,
                'UF_HISTORY' => $history,
            ]);
            (new NotificationRepository())->add(
                $part['partner_id'],
                'order',
                'Покупателю выставлен счёт по заказу ' . $part['order_number'],
                'Площадка выставила покупателю счёт, ваша часть — ' . self::money($part['supplier_total']) . ' руб., действителен до ' . $dueText . '. Отгрузка — после оплаты.',
                'orders/edit/' . $part['id'] . '/'
            );
        }

        $order = OrderRepository::mergeForBuyer($repo->groupOf($orderId));
        if ($order['user_id']) {
            NotificationRepository::forBuyer()->addForBuyer(
                $order['user_id'],
                'order',
                'Счёт на оплату заказа ' . $order['order_number'],
                'Заказ согласован с поставщиком. Счёт на ' . self::money($order['total']) . ' руб. действителен до ' . $dueText . ' — скачайте его в «Ваших заказах».',
                '/personal/orders/#order-' . $order['id'],
                false // своё письмо — FORMARO_ORDER_INVOICE
            );
        }
        self::mail('FORMARO_ORDER_INVOICE', $order, $dueText);

        return $repo->get($orderId);
    }

    /**
     * Менеджер: статус оплаты. Оплата и её снятие — на весь заказ покупателя
     * (счёт один), возврат — только на эту часть (например, поставщик не смог
     * отгрузить). Поставщику — зачисление или списание по его части
     * (PayoutService) и уведомление, покупателю — одно уведомление.
     */
    public static function setPayment(int $orderId, string $status): array
    {
        $repo = new OrderRepository();
        $parts = $status === 'refunded' ? array_filter([$repo->get($orderId)]) : $repo->groupOf($orderId);
        if (!$parts) {
            throw new \RuntimeException('Заказ не найден');
        }
        $amount = 0.0;
        foreach ($parts as $part) {
            if ($part['status'] === 'cancelled' && $part['id'] !== $orderId) {
                continue;
            }
            [$before, $after] = $repo->setPaymentStatus($part['id'], $status);
            if ($before['payment_status'] === $after['payment_status']) {
                continue;
            }
            PayoutService::onPaymentChanged($before, $after);
            $after = $repo->get($part['id']);
            $amount += $after['total'];
            (new NotificationRepository())->add(
                $after['partner_id'],
                'order',
                'Заказ ' . $after['order_number'] . ': ' . mb_strtolower(OrderRepository::PAYMENT_STATUSES[$status]),
                $after['payout_status'] === PayoutService::PAID
                    // Выплата проведена — баланс не меняется, остальное площадка решает вне системы.
                    ? ($status === 'paid' ? 'Площадка подтвердила оплату заказа.' : 'Площадка оформила возврат оплаты покупателю. Выплата по заказу уже проведена — баланс не меняется.')
                    : match ($status) {
                        'paid' => 'Площадка подтвердила оплату: ' . number_format($after['supplier_total'], 2, ',', ' ') . ' руб. зачислено на ваш текущий баланс. Соберите заказ, передайте в транспортную компанию и укажите трек-номер — выплатим в тот же день.',
                        'refunded' => 'Площадка оформила возврат оплаты покупателю — сумма заказа списана с вашего баланса.',
                        default => 'Площадка сняла отметку об оплате заказа — сумма заказа списана с вашего баланса.',
                    },
                'orders/edit/' . $after['id'] . '/'
            );
        }
        if ($amount > 0) {
            BuyerNotificationService::paymentChanged(OrderRepository::mergeForBuyer($repo->groupOf($orderId)), $status, $amount);
        }

        return $repo->get($orderId);
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
        $done = [];
        foreach ($repo->listOverdueInvoices($now ?? new DateTime()) as $overdue) {
            if (isset($done[$overdue['group_id']])) {
                continue; // все части заказа уже обработаны вместе с первой
            }
            $done[$overdue['group_id']] = true;

            foreach ($repo->groupOf($overdue['id']) as $part) {
                if ($part['invoice_status'] !== 'issued' || $part['payment_status'] === 'paid' || $part['status'] === 'cancelled') {
                    continue;
                }
                $history = $part['history'];
                $history[] = self::entry('Счёт аннулирован: не оплачен в течение ' . self::INVOICE_DAYS . '-х рабочих дней', 'Площадка');
                $repo->updateFields($part['id'], ['UF_INVOICE_STATUS' => 'expired', 'UF_HISTORY' => $history]);
                (new NotificationRepository())->add($part['partner_id'], 'order', 'Счёт по заказу ' . $part['order_number'] . ' аннулирован', 'В связи с неоплатой счёт покупателя не действителен.', 'orders/edit/' . $part['id'] . '/');
            }

            $order = OrderRepository::mergeForBuyer($repo->groupOf($overdue['id']));
            $text = 'В связи с неоплатой счёт № ' . $order['order_number'] . ' не действителен.';
            if ($order['user_id']) {
                NotificationRepository::forBuyer()->addForBuyer(
                    $order['user_id'],
                    'order',
                    'Счёт № ' . $order['order_number'] . ' не действителен',
                    $text . ' Если заказ актуален, напишите нам — менеджер выставит новый счёт.',
                    '/personal/orders/#order-' . $order['id'],
                    false // своё письмо — FORMARO_ORDER_INVOICE_EXPIRED
                );
            }
            self::mail('FORMARO_ORDER_INVOICE_EXPIRED', $order, '');
            NotificationRepository::notifyAdmin('order', 'Счёт аннулирован: ' . $order['order_number'], $text . ' Покупатель уведомлён.', 'orders/edit/' . $overdue['id'] . '/');
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
        Mailer::send($event, $email, [
            'NAME' => (string)($order['customer']['name'] ?? ''),
            'ORDER_NUMBER' => $order['order_number'],
            'TOTAL' => self::money($order['total']),
            'DUE_DATE' => $dueText,
            'ORDERS_URL' => Mailer::url('/personal/orders/#order-' . $order['id']),
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
