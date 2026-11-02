<?php

namespace Sprint\Migration;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;

class Version20261102110001 extends Version
{
    protected $description = 'Контакты: ВКонтакте скрыт везде, все почтовые адреса на сайте — order@reppaopt.ru';

    private const EMAIL = 'order@reppaopt.ru';
    private const CONTACTS_IBLOCK_ID = 6;
    private const VACANCIES_IBLOCK_ID = 14;

    /** Любой ящик @formaro.ru (info, kadr, partner) → единый адрес площадки. */
    public static function replaceEmails(string $text): string
    {
        return preg_replace('/[a-z0-9._-]+@formaro\.ru/iu', self::EMAIL, $text);
    }

    public function up()
    {
        $el = new \CIBlockElement();

        // ВКонтакте — в подвале, бургер-меню и блоке поддержки
        $hidden = 0;
        $rows = \CIBlockElement::GetList([], ['IBLOCK_ID' => self::CONTACTS_IBLOCK_ID, 'CODE' => 'icon-vk'], false, false, ['ID']);
        while ($row = $rows->Fetch()) {
            if ($el->Update((int)$row['ID'], ['ACTIVE' => 'N'])) {
                $hidden++;
            }
        }

        // Названия и тексты элементов (контакты, контентные страницы)
        $updated = 0;
        $rows = Application::getConnection()->query(
            "SELECT ID, NAME, PREVIEW_TEXT, PREVIEW_TEXT_TYPE, DETAIL_TEXT, DETAIL_TEXT_TYPE FROM b_iblock_element"
            . " WHERE NAME LIKE '%@formaro.ru%' OR PREVIEW_TEXT LIKE '%@formaro.ru%' OR DETAIL_TEXT LIKE '%@formaro.ru%'"
        );
        while ($row = $rows->fetch()) {
            $fields = [];
            foreach (['NAME', 'PREVIEW_TEXT', 'DETAIL_TEXT'] as $key) {
                $value = self::replaceEmails((string)$row[$key]);
                if ($value !== (string)$row[$key]) {
                    $fields[$key] = $value;
                    if ($key !== 'NAME') {
                        $fields[$key . '_TYPE'] = $row[$key . '_TYPE'];
                    }
                }
            }
            if ($fields && $el->Update((int)$row['ID'], $fields)) {
                $updated++;
            }
        }

        // Ссылки mailto: у контактов (свойство LINK)
        $rows = \CIBlockElement::GetList([], ['IBLOCK_ID' => self::CONTACTS_IBLOCK_ID], false, false, ['ID', 'PROPERTY_LINK']);
        while ($row = $rows->Fetch()) {
            $link = (string)$row['PROPERTY_LINK_VALUE'];
            $value = str_replace('mail-to:', 'mailto:', self::replaceEmails($link));
            if ($value !== $link) {
                \CIBlockElement::SetPropertyValuesEx((int)$row['ID'], self::CONTACTS_IBLOCK_ID, ['LINK' => $value]);
                $updated++;
            }
        }

        // Вакансии: почта для откликов и значение по умолчанию
        $rows = \CIBlockElement::GetList([], ['IBLOCK_ID' => self::VACANCIES_IBLOCK_ID, '%PROPERTY_EMAIL' => '@formaro.ru'], false, false, ['ID']);
        while ($row = $rows->Fetch()) {
            \CIBlockElement::SetPropertyValuesEx((int)$row['ID'], self::VACANCIES_IBLOCK_ID, ['EMAIL' => self::EMAIL]);
            $updated++;
        }
        $prop = \CIBlockProperty::GetList([], ['IBLOCK_ID' => self::VACANCIES_IBLOCK_ID, 'CODE' => 'EMAIL'])->Fetch();
        if ($prop) {
            (new \CIBlockProperty())->Update((int)$prop['ID'], ['DEFAULT_VALUE' => self::EMAIL]);
        }

        // Реквизиты в подвале писем
        Option::set('formaro.cabinet', 'requisites_email', self::EMAIL);

        BXClearCache(true);
        $this->outSuccess('ВКонтакте скрыт: %d, почта заменена: %d', $hidden, $updated);
    }

    public function down()
    {
        $rows = \CIBlockElement::GetList([], ['IBLOCK_ID' => self::CONTACTS_IBLOCK_ID, 'CODE' => 'icon-vk'], false, false, ['ID']);
        $el = new \CIBlockElement();
        while ($row = $rows->Fetch()) {
            $el->Update((int)$row['ID'], ['ACTIVE' => 'Y']);
        }
        $this->outInfo('Почтовые адреса не откатываются');
    }
}
