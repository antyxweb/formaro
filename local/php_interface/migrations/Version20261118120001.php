<?php

namespace Sprint\Migration;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;

/**
 * Почта площадки order@reppaopt.ru → orders@reppaopt.ru.
 *
 * reppaopt.ru — алиас организации Яндекс 360 с основным доменом formaro.ru, поэтому
 * order@reppaopt.ru попадал в общий ящик order@formaro.ru. orders@ — отдельный ящик Reppa.
 */
class Version20261118120001 extends Version
{
    protected $description = 'Почта площадки: order@reppaopt.ru → orders@reppaopt.ru (контакты, страницы, вакансии, настройки)';

    private const OPTIONS = [
        ['main', 'email_from'],
        ['sale', 'order_email'],
        ['formaro.cabinet', 'mail_admin_emails'],
        ['formaro.cabinet', 'requisites_email'],
    ];

    public function up()
    {
        $n = $this->swap('order@reppaopt.ru', 'orders@reppaopt.ru');
        BXClearCache(true);
        $this->outSuccess('Замен: %d', $n);
    }

    public function down()
    {
        $n = $this->swap('orders@reppaopt.ru', 'order@reppaopt.ru');
        BXClearCache(true);
        $this->outSuccess('Откачено замен: %d', $n);
    }

    /** Точное совпадение адреса: «order@» не задевает «orders@» и «xorder@». */
    public static function replace(string $text, string $from, string $to): string
    {
        return preg_replace('/(?<![a-z0-9._-])' . preg_quote($from, '/') . '(?![a-z0-9-])/iu', $to, $text);
    }

    private function swap(string $from, string $to): int
    {
        $n = 0;
        $db = Application::getConnection();
        $like = $db->getSqlHelper()->forSql($from);

        // Названия и тексты элементов (контакты, контентные страницы); Update обновит и поиск
        $el = new \CIBlockElement();
        $rows = $db->query(
            "SELECT ID, NAME, PREVIEW_TEXT, PREVIEW_TEXT_TYPE, DETAIL_TEXT, DETAIL_TEXT_TYPE FROM b_iblock_element"
            . " WHERE NAME LIKE '%{$like}%' OR PREVIEW_TEXT LIKE '%{$like}%' OR DETAIL_TEXT LIKE '%{$like}%'"
        );
        while ($row = $rows->fetch()) {
            $fields = [];
            foreach (['NAME', 'PREVIEW_TEXT', 'DETAIL_TEXT'] as $key) {
                $value = self::replace((string)$row[$key], $from, $to);
                if ($value !== (string)$row[$key]) {
                    $fields[$key] = $value;
                    if ($key !== 'NAME') {
                        $fields[$key . '_TYPE'] = $row[$key . '_TYPE'];
                    }
                }
            }
            if ($fields && $el->Update((int)$row['ID'], $fields)) {
                $n++;
            }
        }

        // Строковые свойства элементов (mailto: у контактов, почта вакансий) — в общей и в отдельной таблице
        $props = [];
        $rows = $db->query(
            "SELECT p.IBLOCK_ELEMENT_ID EL, ip.IBLOCK_ID, ip.CODE FROM b_iblock_element_property p"
            . " JOIN b_iblock_property ip ON ip.ID = p.IBLOCK_PROPERTY_ID WHERE p.VALUE LIKE '%{$like}%'"
        );
        while ($row = $rows->fetch()) {
            $props[$row['IBLOCK_ID'] . ':' . $row['EL'] . ':' . $row['CODE']] = $row;
        }
        $iblocks = $db->query("SELECT ID FROM b_iblock WHERE VERSION = 2");
        while ($ib = $iblocks->fetch()) {
            $table = 'b_iblock_element_prop_s' . (int)$ib['ID'];
            $codes = [];
            $res = $db->query("SELECT ID, CODE FROM b_iblock_property WHERE IBLOCK_ID = " . (int)$ib['ID'] . " AND PROPERTY_TYPE = 'S' AND MULTIPLE = 'N'");
            while ($p = $res->fetch()) {
                $codes['PROPERTY_' . $p['ID']] = $p['CODE'];
            }
            foreach ($codes as $column => $code) {
                $res = $db->query("SELECT IBLOCK_ELEMENT_ID EL FROM {$table} WHERE {$column} LIKE '%{$like}%'");
                while ($row = $res->fetch()) {
                    $props[$ib['ID'] . ':' . $row['EL'] . ':' . $code] = ['IBLOCK_ID' => $ib['ID'], 'EL' => $row['EL'], 'CODE' => $code];
                }
            }
        }
        foreach ($props as $p) {
            $cur = \CIBlockElement::GetProperty((int)$p['IBLOCK_ID'], (int)$p['EL'], [], ['CODE' => $p['CODE']])->Fetch();
            $value = self::replace((string)($cur['VALUE'] ?? ''), $from, $to);
            if ($cur && $value !== (string)$cur['VALUE']) {
                \CIBlockElement::SetPropertyValuesEx((int)$p['EL'], (int)$p['IBLOCK_ID'], [$p['CODE'] => $value]);
                $n++;
            }
        }

        // Значения свойств по умолчанию (почта вакансий)
        $res = $db->query("SELECT ID, DEFAULT_VALUE FROM b_iblock_property WHERE DEFAULT_VALUE LIKE '%{$like}%'");
        while ($row = $res->fetch()) {
            (new \CIBlockProperty())->Update((int)$row['ID'], ['DEFAULT_VALUE' => self::replace((string)$row['DEFAULT_VALUE'], $from, $to)]);
            $n++;
        }

        // Настройки: отправитель, почта магазина, уведомления админа, реквизиты
        foreach (self::OPTIONS as [$module, $name]) {
            $cur = (string)Option::get($module, $name, '');
            $value = self::replace($cur, $from, $to);
            if ($value !== $cur) {
                Option::set($module, $name, $value);
                $n++;
            }
        }

        // Адрес сайта s1
        $site = \CSite::GetByID('s1')->Fetch();
        if ($site && ($value = self::replace((string)$site['EMAIL'], $from, $to)) !== (string)$site['EMAIL']) {
            (new \CSite())->Update('s1', ['EMAIL' => $value]);
            $n++;
        }

        return $n;
    }
}
