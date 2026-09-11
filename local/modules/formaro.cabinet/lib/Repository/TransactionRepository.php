<?php

namespace Formaro\Cabinet\Repository;

use Bitrix\Main\Type\DateTime;

/**
 * Финансовые операции — HL-блок CabinetTransactions. Баланс считается на
 * лету суммой транзакций (см. getSummary()), не хранится отдельно — у
 * демо-данных прототипа (data/finance.json) верхнеуровневые числа и список
 * транзакций арифметически не сходились (обычная для ручного демо-датасета
 * история), с реальной БД такого расхождения быть не должно.
 */
class TransactionRepository
{
    private const HLBLOCK_NAME = 'CabinetTransactions';

    /** @return array {available_balance, pending_balance, total_earned, transactions:[]} */
    public function getSummary(int $partnerId): array
    {
        $rows = $this->listOwn($partnerId);

        $totalEarned = 0.0;
        $totalCommission = 0.0;
        $totalRefunded = 0.0;
        $totalWithdrawnCompleted = 0.0;
        $totalWithdrawalPending = 0.0;

        foreach ($rows as $t) {
            if ($t['status'] !== 'completed' && !($t['type'] === 'withdrawal' && $t['status'] === 'pending')) {
                continue;
            }
            switch ($t['type']) {
                case 'income':
                    $totalEarned += $t['amount'];
                    break;
                case 'commission':
                    $totalCommission += $t['amount'];
                    break;
                case 'refund':
                    $totalRefunded += $t['amount'];
                    break;
                case 'withdrawal':
                    if ($t['status'] === 'pending') {
                        $totalWithdrawalPending += $t['amount'];
                    } else {
                        $totalWithdrawnCompleted += $t['amount'];
                    }
                    break;
            }
        }

        $available = $totalEarned - $totalCommission - $totalRefunded - $totalWithdrawnCompleted - $totalWithdrawalPending;

        return [
            'available_balance' => round($available, 2),
            'pending_balance' => round($totalWithdrawalPending, 2),
            'total_earned' => round($totalEarned, 2),
            'transactions' => $rows,
        ];
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

    /** Заявка на вывод средств — партнёр не может выбирать статус/сумму задним
     *  числом, только создать pending-заявку в пределах доступного баланса. */
    public function requestWithdrawal(int $partnerId, float $amount): array
    {
        if ($amount <= 0) {
            throw new \RuntimeException('Укажите сумму вывода');
        }
        $summary = $this->getSummary($partnerId);
        if ($amount > $summary['available_balance']) {
            throw new \RuntimeException('Сумма превышает доступный к выводу баланс');
        }

        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $result = $dataClass::add([
            'UF_TYPE' => 'withdrawal',
            'UF_AMOUNT' => $amount,
            'UF_STATUS' => 'pending',
            'UF_DESCRIPTION' => 'Заявка на вывод средств (в обработке)',
            'UF_PARTNER_ID' => $partnerId,
            'UF_CREATED_AT' => new DateTime(),
        ]);

        if (!$result->isSuccess()) {
            throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
        }

        return $this->getSummary($partnerId);
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
            'date' => $row['UF_CREATED_AT'] instanceof DateTime ? $row['UF_CREATED_AT']->format('c') : null,
        ];
    }
}
