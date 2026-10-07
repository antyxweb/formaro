<?php

namespace Formaro\Cabinet\Repository;

use Bitrix\Main\Type\DateTime;

/**
 * Финансовые операции поставщика — HL-блок CabinetTransactions. Баланс
 * считается на лету суммой операций (getSummary()), не хранится отдельно.
 *
 * Типы (ТЗ «Финансы», этап 4 — PayoutService): income — оплата заказа
 * клиентом (сумма поставщика, UF_ORDER_ID), refund — оплата снята или
 * возвращена покупателю, payout — выплата площадкой по трек-номеру.
 * commission и withdrawal — из прежней схемы (комиссия списанием, заявки
 * на вывод): остаются в истории и в расчёте баланса.
 */
class TransactionRepository
{
    private const HLBLOCK_NAME = 'CabinetTransactions';

    /** @return array {balance, total_earned, total_paid_out, transactions:[]} */
    public function getSummary(int $partnerId): array
    {
        $rows = $this->listOwn($partnerId);

        return self::totals($rows) + ['transactions' => $rows];
    }

    /** Итоги по операциям (только проведённые). */
    public static function totals(array $rows): array
    {
        $earned = $minus = $paidOut = 0.0;
        foreach ($rows as $t) {
            if ($t['status'] !== 'completed') {
                continue;
            }
            match ($t['type']) {
                'income' => $earned += $t['amount'],
                'commission', 'refund' => $minus += $t['amount'],
                'payout', 'withdrawal' => $paidOut += $t['amount'],
                default => null,
            };
        }

        return [
            'balance' => round($earned - $minus - $paidOut, 2),
            'total_earned' => round($earned, 2),
            'total_paid_out' => round($paidOut, 2),
        ];
    }

    public function add(int $partnerId, string $type, float $amount, string $description, int $orderId = 0): array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $result = $dataClass::add([
            'UF_TYPE' => $type,
            'UF_AMOUNT' => round($amount, 2),
            'UF_STATUS' => 'completed',
            'UF_DESCRIPTION' => $description,
            'UF_PARTNER_ID' => $partnerId,
            'UF_ORDER_ID' => $orderId,
            'UF_CREATED_AT' => new DateTime(),
        ]);
        if (!$result->isSuccess()) {
            throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
        }

        return $this->toArray($dataClass::getById($result->getId())->fetch());
    }

    /** Сколько по заказу зачислено поставщику сейчас: поступления − возвраты. */
    public function orderCredit(int $orderId): float
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $sum = 0.0;
        $res = $dataClass::getList(['filter' => ['=UF_ORDER_ID' => $orderId, '=UF_STATUS' => 'completed', '@UF_TYPE' => ['income', 'refund']]]);
        while ($row = $res->fetch()) {
            $sum += ($row['UF_TYPE'] === 'income' ? 1 : -1) * (float)$row['UF_AMOUNT'];
        }

        return round($sum, 2);
    }

    public function listOwn(int $partnerId): array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $rows = $dataClass::getList([
            'filter' => ['=UF_PARTNER_ID' => $partnerId],
            'order' => ['ID' => 'DESC'],
        ])->fetchAll();

        return array_map([$this, 'toArray'], $rows);
    }

    /** Операции всех партнёров — кабинет администратора. */
    public function listAll(): array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $rows = $dataClass::getList(['order' => ['ID' => 'DESC']])->fetchAll();

        return array_map([$this, 'toArray'], $rows);
    }

    private function toArray(array $row): array
    {
        return [
            'id' => (int)$row['ID'],
            'type' => $row['UF_TYPE'],
            'amount' => (float)$row['UF_AMOUNT'],
            'status' => $row['UF_STATUS'] ?: 'completed',
            'description' => $row['UF_DESCRIPTION'],
            'partner_id' => (int)$row['UF_PARTNER_ID'],
            'order_id' => (int)($row['UF_ORDER_ID'] ?? 0),
            'date' => $row['UF_CREATED_AT'] instanceof DateTime ? $row['UF_CREATED_AT']->format('c') : null,
        ];
    }
}
