<?php

namespace Sprint\Migration;

use Bitrix\Main\Application;

class Version20261012140001 extends Version
{
    protected $description = 'Домен площадки на проде: formaro.ru → reppaopt.ru в текстах страниц (политика конфиденциальности, cookie)';

    /** Адрес сайта меняется, почтовые ящики (info@formaro.ru, kadr@formaro.ru) — нет. */
    public static function redomain(string $text): string
    {
        return preg_replace('/(?<![@\w-])formaro\.ru/u', 'reppaopt.ru', $text);
    }

    public function up()
    {
        $rows = Application::getConnection()->query(
            "SELECT ID, PREVIEW_TEXT, PREVIEW_TEXT_TYPE, DETAIL_TEXT, DETAIL_TEXT_TYPE FROM b_iblock_element WHERE PREVIEW_TEXT LIKE '%formaro.ru%' OR DETAIL_TEXT LIKE '%formaro.ru%'"
        );
        $updated = 0;
        while ($row = $rows->fetch()) {
            $fields = [];
            foreach (['PREVIEW_TEXT', 'DETAIL_TEXT'] as $key) {
                $value = self::redomain((string)$row[$key]);
                if ($value !== (string)$row[$key]) {
                    $fields[$key] = $value;
                    $fields[$key . '_TYPE'] = $row[$key . '_TYPE'];
                }
            }
            if ($fields && (new \CIBlockElement())->Update((int)$row['ID'], $fields)) {
                $updated++;
            }
        }
        BXClearCache(true);
        $this->outSuccess('reppaopt.ru: обновлено элементов %d', $updated);
    }

    public function down()
    {
        $this->outInfo('Откат домена не выполняется');
    }
}
