<?php

namespace Formaro\Cabinet\Service;

use Bitrix\Main\Type\DateTime;
use Formaro\Cabinet\Repository\NotificationRepository;
use Formaro\Cabinet\Repository\OrderRepository;
use Formaro\Cabinet\Repository\PartnerRepository;
use Formaro\Cabinet\Repository\TransactionRepository;

/**
 * Выплаты поставщикам (ТЗ «Финансы», этап 4). Заявок на вывод нет —
 * выплаты инициирует площадка:
 *
 *  1. Площадка подтвердила оплату заказа — сумма поставщика (supplier_total,
 *     без наценки) зачисляется на его «Текущий баланс» (onPaymentChanged,
 *     операция income). Оплату сняли или вернули — сумма списывается
 *     (refund).
 *  2. Поставщик собрал заказ, передал его в транспортную компанию и указал
 *     трек-номер (setTrack) — заказ «к выплате сегодня», администраторам
 *     уведомление.
 *  3. Администратор перевёл деньги и нажал «Выплачено» (markPaid) —
 *     операция payout, поставщику уведомление.
 */
final class PayoutService
{
    public const DUE = 'due';
    public const PAID = 'paid';

    /** Смена статуса оплаты заказа (кабинет администратора) — зачисление или списание. */
    public static function onPaymentChanged(array $before, array $after): void
    {
        $wasPaid = $before['payment_status'] === 'paid';
        $isPaid = $after['payment_status'] === 'paid';
        if ($wasPaid === $isPaid) {
            return;
        }
        $tx = new TransactionRepository();
        $credit = $tx->orderCredit($after['id']);
        $number = $after['order_number'];

        if ($isPaid) {
            $amount = round($after['supplier_total'] - $credit, 2);
            if ($amount > 0) {
                $tx->add($after['partner_id'], 'income', $amount, 'Оплата заказа ' . $number, $after['id']);
            }

            return;
        }

        if ($credit > 0) {
            $tx->add(
                $after['partner_id'],
                'refund',
                $credit,
                ($after['payment_status'] === 'refunded' ? 'Возврат оплаты покупателю по заказу ' : 'Снята отметка об оплате заказа ') . $number,
                $after['id']
            );
        }
        // Ещё не выплачено — снимаем с очереди «к выплате» (трек-номер остаётся).
        if ($after['payout_status'] === self::DUE) {
            (new OrderRepository())->updateFields($after['id'], ['UF_PAYOUT_STATUS' => '']);
        }
    }

    /** Поставщик: заказ передан в транспортную компанию — трек-номер. */
    public static function setTrack(int $partnerId, int $orderId, string $track, string $carrier): array
    {
        $repo = new OrderRepository();
        $order = $repo->get($orderId);
        if (!$order || !$repo->canEdit($partnerId, $order)) {
            throw new \RuntimeException('Заказ не найден');
        }
        if ($order['payment_status'] !== 'paid') {
            throw new \RuntimeException('Трек-номер указывается после оплаты заказа');
        }
        if ($order['status'] === 'cancelled') {
            throw new \RuntimeException('Заказ отменён');
        }
        if ($order['payout_status'] === self::PAID) {
            throw new \RuntimeException('Выплата по заказу уже проведена — трек-номер не меняется. Напишите в техподдержку, если нужно исправить.');
        }
        $track = trim(preg_replace('/\s+/u', ' ', $track));
        $carrier = trim(preg_replace('/\s+/u', ' ', $carrier));
        if (mb_strlen($track) < 4 || mb_strlen($track) > 60 || !preg_match('/^[\p{L}\d\- \/]+$/u', $track)) {
            throw new \RuntimeException('Проверьте трек-номер: буквы, цифры и дефис, от 4 символов');
        }
        if ($track === $order['track_number'] && $carrier === $order['track_carrier'] && $order['payout_status'] === self::DUE) {
            return $order;
        }

        $first = $order['payout_status'] !== self::DUE;
        $shipment = ($carrier !== '' ? $carrier . ', ' : '') . 'трек-номер ' . $track;
        $fields = [
            'UF_TRACK_NUMBER' => $track,
            'UF_TRACK_CARRIER' => $carrier,
            'UF_PAYOUT_STATUS' => self::DUE,
        ];
        if ($first) {
            $fields['UF_SHIPPED_AT'] = new DateTime();
        }
        // Записи видит и покупатель («Статус изменён…», «Трек-номер…» — personal.orders).
        if (in_array($order['status'], ['new', 'processing', 'confirmed'], true)) {
            $fields['UF_STATUS'] = 'shipped';
            $text = 'Статус изменён на «Отправлен». Заказ передан в транспортную компанию: ' . $shipment;
        } else {
            $text = ($first ? 'Трек-номер отправления: ' : 'Трек-номер исправлен: ') . $shipment;
        }
        $fields['UF_HISTORY'] = array_merge($order['history'], [['date' => date('c'), 'text' => $text, 'author' => 'Менеджер']]);
        $after = $repo->updateFields($orderId, $fields);
        BuyerNotificationService::orderUpdated($order, $after);

        if ($first) {
            NotificationRepository::notifyAdmin(
                'finance',
                'К выплате сегодня: ' . self::money($after['supplier_total']) . ' руб.',
                'Поставщик «' . PartnerRepository::nameOf($partnerId) . '» отгрузил заказ ' . $after['order_number']
                    . ($carrier !== '' ? ' (' . $carrier . ')' : '') . ', трек-номер ' . $track
                    . '. Выплатите ' . self::money($after['supplier_total']) . ' руб. сегодня.',
                'finance/?order=' . $orderId
            );
        }

        return $after;
    }

    /** Администратор: деньги поставщику переведены. $comment — например, номер п/п. */
    public static function markPaid(int $orderId, string $comment): array
    {
        $repo = new OrderRepository();
        $order = $repo->get($orderId);
        if (!$order) {
            throw new \RuntimeException('Заказ не найден');
        }
        if ($order['payout_status'] === self::PAID) {
            throw new \RuntimeException('Выплата уже отмечена');
        }
        if ($order['payout_status'] !== self::DUE || $order['payment_status'] !== 'paid') {
            throw new \RuntimeException('Заказ не ждёт выплаты: нужна оплата и трек-номер от поставщика');
        }
        $comment = trim($comment);
        $amount = $order['supplier_total'];
        $number = $order['order_number'];

        (new TransactionRepository())->add(
            $order['partner_id'],
            'payout',
            $amount,
            'Выплата по заказу ' . $number . ($comment !== '' ? ': ' . $comment : ''),
            $orderId
        );
        $history = $order['history'];
        // internal — покупателю в «Истории заказа» не показывается (personal.orders).
        $history[] = ['date' => date('c'), 'text' => 'Площадка выплатила поставщику ' . self::money($amount) . ' руб.', 'author' => 'Площадка', 'internal' => true];
        $after = $repo->updateFields($orderId, [
            'UF_PAYOUT_STATUS' => self::PAID,
            'UF_PAYOUT_AT' => new DateTime(),
            'UF_PAYOUT_AMOUNT' => $amount,
            'UF_PAYOUT_COMMENT' => $comment,
            'UF_HISTORY' => $history,
        ]);

        (new NotificationRepository())->add(
            $order['partner_id'],
            'finance',
            'Выплачено ' . self::money($amount) . ' руб. по заказу ' . $number,
            'Площадка перечислила ' . self::money($amount) . ' руб. на ваш расчётный счёт' . ($comment !== '' ? ' (' . $comment . ')' : '') . '.',
            'finance/'
        );

        return $after;
    }

    /** Состояние выплаты по заказу: '' — не оплачен, awaiting_track, due, paid. */
    public static function state(array $order): string
    {
        if ($order['payout_status'] === self::PAID) {
            return 'paid';
        }
        if ($order['payment_status'] !== 'paid' || $order['status'] === 'cancelled') {
            return '';
        }

        return $order['payout_status'] === self::DUE ? 'due' : 'awaiting_track';
    }

    /** Заказы для выплат: оплаченные и уже выплаченные (кабинеты партнёра и администратора). */
    public static function rows(array $orders, bool $forPartner): array
    {
        $rows = [];
        foreach ($orders as $o) {
            $state = self::state($o);
            if ($state === '') {
                continue;
            }
            $rows[] = [
                'order_id' => $o['id'],
                'order_number' => $o['order_number'],
                'partner_id' => $o['partner_id'],
                'state' => $state,
                'amount' => $state === 'paid' ? $o['payout_amount'] : $o['supplier_total'],
                'buyer_total' => $forPartner ? null : $o['total'],
                'status' => $o['status'],
                'track_number' => $o['track_number'],
                'track_carrier' => $o['track_carrier'],
                'shipped_at' => $o['shipped_at'],
                'payout_at' => $o['payout_at'],
                'payout_comment' => $o['payout_comment'],
                'created_at' => $o['created_at'],
            ];
        }

        return $rows;
    }

    /** Финансы поставщика: баланс, к выплате, выплачено, заказы, операции. */
    public static function partnerSummary(int $partnerId): array
    {
        $summary = (new TransactionRepository())->getSummary($partnerId);
        $payouts = self::rows((new OrderRepository())->listOwn($partnerId), true);
        $sum = static fn(string $state) => round(array_sum(array_map(
            static fn($r) => $r['state'] === $state ? $r['amount'] : 0,
            $payouts
        )), 2);

        return $summary + [
            'due_total' => $sum('due'),
            'awaiting_total' => $sum('awaiting_track'),
            'payouts' => $payouts,
        ];
    }

    private static function money($value): string
    {
        return number_format((float)$value, 2, ',', ' ');
    }
}
