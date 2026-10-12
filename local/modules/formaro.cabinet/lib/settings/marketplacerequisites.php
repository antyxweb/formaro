<?php

namespace Formaro\Cabinet\Settings;

use Bitrix\Main\Config\Option;

/**
 * Реквизиты площадки (юрлица маркетплейса) — настройки модуля
 * formaro.cabinet (Настройки → Настройки модулей → «Reppa: кабинеты»,
 * вкладка «Реквизиты площадки», options.php модуля).
 *
 * Где используются:
 *   - счёт на оплату заказа (OrderInvoiceService) — получатель и поставщик;
 *   - контентные страницы: метки в тексте страницы заменяются таблицами
 *     при выводе (formaro:content.page, replaceTokens()):
 *       #REQUISITES#       — полные реквизиты (страница «Реквизиты»),
 *       #REQUISITES_BANK#  — банковские реквизиты для оплаты («Способ оплаты»);
 *   - реквизиты площадки в техподдержке кабинета партнёра.
 */
final class MarketplaceRequisites
{
    public const MODULE_ID = 'formaro.cabinet';
    private const PREFIX = 'requisites_';

    /** Поле => подпись в настройках (порядок — порядок на форме). */
    public const FIELDS = [
        'name_full' => 'Полное наименование',
        'name_short' => 'Сокращённое наименование',
        'inn' => 'ИНН',
        'kpp' => 'КПП (для ООО)',
        'ogrn' => 'ОГРН / ОГРНИП',
        'legal_address' => 'Юридический адрес',
        'actual_address' => 'Фактический адрес',
        'bank_name' => 'Банк',
        'bik' => 'БИК',
        'account' => 'Расчётный счёт',
        'corr_account' => 'Корреспондентский счёт',
        'ceo_position' => 'Должность руководителя',
        'ceo_name' => 'Руководитель (ФИО)',
        'accountant_name' => 'Бухгалтер (ФИО), если есть',
        'phone' => 'Телефон',
        'email' => 'E-mail',
    ];

    /** @return array<string, string> все поля, пустые — '' */
    public static function get(): array
    {
        $values = [];
        foreach (array_keys(self::FIELDS) as $code) {
            $values[$code] = trim((string)Option::get(self::MODULE_ID, self::PREFIX . $code, ''));
        }

        return $values;
    }

    public static function set(array $values): void
    {
        foreach (array_keys(self::FIELDS) as $code) {
            if (array_key_exists($code, $values)) {
                Option::set(self::MODULE_ID, self::PREFIX . $code, trim((string)$values[$code]));
            }
        }
    }

    /** «ОГРНИП» для 15 цифр (ИП), иначе «ОГРН». */
    public static function ogrnLabel(string $ogrn): string
    {
        return strlen(preg_replace('/\D/', '', $ogrn)) === 15 ? 'ОГРНИП' : 'ОГРН';
    }

    /** Метки #REQUISITES# / #REQUISITES_BANK# в HTML страницы → таблицы реквизитов. */
    public static function replaceTokens(string $html): string
    {
        if (strpos($html, '#REQUISITES') === false) {
            return $html;
        }
        $r = self::get();

        return strtr($html, [
            '#REQUISITES_BANK#' => self::table(self::bankRows($r)),
            '#REQUISITES#' => self::table(self::fullRows($r)),
        ]);
    }

    /** Полные реквизиты — страница «Реквизиты». */
    private static function fullRows(array $r): array
    {
        return [
            'Полное наименование' => $r['name_full'],
            'Сокращённое наименование' => $r['name_short'],
            self::ogrnLabel($r['ogrn']) => $r['ogrn'],
            'ИНН' => $r['inn'],
            'КПП' => $r['kpp'],
            'Юридический адрес' => $r['legal_address'],
            'Фактический адрес' => $r['actual_address'],
            'Банк' => $r['bank_name'],
            'Расчётный счёт' => $r['account'],
            'Корреспондентский счёт' => $r['corr_account'],
            'БИК' => $r['bik'],
            ($r['ceo_position'] ?: 'Руководитель') => $r['ceo_name'],
            'Телефон' => $r['phone'],
            'E-mail' => $r['email'],
        ];
    }

    /** Банковские реквизиты для оплаты — страница «Способ оплаты». */
    private static function bankRows(array $r): array
    {
        return [
            'Получатель' => $r['name_short'] ?: $r['name_full'],
            'ИНН' => $r['inn'],
            'КПП' => $r['kpp'],
            self::ogrnLabel($r['ogrn']) => $r['ogrn'],
            'Р/С' => trim($r['account'] . ($r['bank_name'] !== '' ? ' в ' . $r['bank_name'] : '')),
            'БИК' => $r['bik'],
            'К/С' => $r['corr_account'],
            ($r['ceo_position'] ?: 'Руководитель') => $r['ceo_name'],
            'Фактический адрес' => $r['actual_address'],
        ];
    }

    /** Таблица «подпись — значение» в стиле текстовых страниц; пустые строки пропускаются. */
    private static function table(array $rows): string
    {
        $html = '';
        foreach ($rows as $label => $value) {
            if ($value !== '') {
                $html .= '<tr><th scope="row">' . htmlspecialcharsbx($label) . '</th><td>' . htmlspecialcharsbx($value) . '</td></tr>';
            }
        }

        return $html !== '' ? '<table class="text-page__props"><tbody>' . $html . '</tbody></table>' : '';
    }
}
