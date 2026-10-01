<?php

namespace Formaro\Cabinet\Service;

use Bitrix\Main\UserTable;

/**
 * Поиск покупателя сайта по данным заказа — для привязки заказа, созданного
 * продавцом в кабинете (тогда он появляется у покупателя в «Ваших заказах»).
 *
 * Сначала — e-mail (точное совпадение с e-mail или логином, без учёта
 * регистра); не нашёлся — телефон (по последним 10 цифрам, без учёта
 * формата), но только если такой номер у одного пользователя: при
 * совпадении у нескольких — не угадываем.
 */
class BuyerLookupService
{
    /** @return int ID пользователя, 0 — не найден */
    public static function find(string $email, string $phone): int
    {
        $email = trim($email);
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $row = UserTable::getList([
                'select' => ['ID'],
                'filter' => ['=ACTIVE' => 'Y', ['LOGIC' => 'OR', '=EMAIL' => $email, '=LOGIN' => $email]],
                'limit' => 1,
            ])->fetch();
            if ($row) {
                return (int)$row['ID'];
            }
        }

        $digits = self::phoneKey($phone);
        if ($digits === '') {
            return 0;
        }
        // Кандидаты — LIKE по последним 7 цифрам с любыми символами между
        // ними («555-01-01», «555 01 01», «5550101»), точное сравнение — по
        // нормализованным 10 цифрам.
        $pattern = '%' . implode('%', str_split(substr($digits, -7))) . '%';
        $rows = UserTable::getList([
            'select' => ['ID', 'PERSONAL_PHONE', 'PERSONAL_MOBILE', 'WORK_PHONE'],
            'filter' => ['=ACTIVE' => 'Y', ['LOGIC' => 'OR', '%=PERSONAL_PHONE' => $pattern, '%=PERSONAL_MOBILE' => $pattern, '%=WORK_PHONE' => $pattern]],
            'limit' => 50,
        ])->fetchAll();
        $found = [];
        foreach ($rows as $row) {
            foreach (['PERSONAL_PHONE', 'PERSONAL_MOBILE', 'WORK_PHONE'] as $field) {
                if (self::phoneKey((string)$row[$field]) === $digits) {
                    $found[(int)$row['ID']] = true;
                }
            }
        }

        return count($found) === 1 ? (int)array_key_first($found) : 0;
    }

    /** Последние 10 цифр номера («+7 (999) 123-45-67», «8 999 1234567» — одно и то же). */
    private static function phoneKey(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        return strlen($digits) >= 10 ? substr($digits, -10) : '';
    }
}
