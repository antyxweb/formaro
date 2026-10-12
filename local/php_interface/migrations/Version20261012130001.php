<?php

namespace Sprint\Migration;

use Bitrix\Main\Application;

class Version20261012130001 extends Version
{
    protected $description = 'Новое название площадки: Formaro → Reppa (Ресурс Портала Поставщиков) в текстах страниц, разделов, писем и в названии сайта';

    private const SITE_NAME = 'Reppa — Ресурс Портала Поставщиков';

    /**
     * Меняется только название в тексте. Не трогаем: адреса почты и домен
     * (info@formaro.ru, kadr@formaro.ru, https://formaro.ru — это реальные
     * контакты, их меняет владелец), коды почтовых событий FORMARO_*, ID
     * модуля formaro.cabinet и имена классов/справочников (FormaroSubscriptions).
     */
    public static function rebrand(string $text): string
    {
        $text = str_replace(['Formaro.ru', 'на formaro.ru', 'Маркетплейс «Формаро»', 'ООО «Формаро»', 'Формаро'], ['Reppa', 'на Reppa', 'Маркетплейс Reppa', 'Reppa', 'Reppa'], $text);

        return preg_replace('/(?<![\\\\\w])Formaro(?![\\\\\w])/u', 'Reppa', $text);
    }

    public function up()
    {
        $connection = Application::getConnection();
        $where = static fn(string ...$columns) => implode(' OR ', array_map(
            static fn($c) => "($c LIKE BINARY '%Formaro%' OR $c LIKE '%Формаро%' OR $c LIKE BINARY '%на formaro.ru%')",
            $columns
        ));

        // Элементы инфоблоков: страницы, гайд, FAQ, пункты меню.
        $elements = 0;
        $rows = $connection->query('SELECT ID, NAME, PREVIEW_TEXT, PREVIEW_TEXT_TYPE, DETAIL_TEXT, DETAIL_TEXT_TYPE FROM b_iblock_element WHERE ' . $where('NAME', 'PREVIEW_TEXT', 'DETAIL_TEXT'));
        while ($row = $rows->fetch()) {
            $fields = [];
            foreach (['NAME', 'PREVIEW_TEXT', 'DETAIL_TEXT'] as $key) {
                $value = self::rebrand((string)$row[$key]);
                if ($value !== (string)$row[$key]) {
                    $fields[$key] = $value;
                    if ($key !== 'NAME') {
                        $fields[$key . '_TYPE'] = $row[$key . '_TYPE'];
                    }
                }
            }
            if ($fields && (new \CIBlockElement())->Update((int)$row['ID'], $fields)) {
                $elements++;
            }
        }

        // Разделы инфоблоков: описание и полное описание (UF_FULL_DESC у каталога).
        $sections = 0;
        $rows = $connection->query('SELECT ID, IBLOCK_ID, DESCRIPTION, DESCRIPTION_TYPE FROM b_iblock_section WHERE ' . $where('DESCRIPTION'));
        $byId = [];
        while ($row = $rows->fetch()) {
            $byId[(int)$row['ID']] = ['IBLOCK_ID' => (int)$row['IBLOCK_ID'], 'DESCRIPTION' => self::rebrand((string)$row['DESCRIPTION']), 'DESCRIPTION_TYPE' => $row['DESCRIPTION_TYPE']];
        }
        $ufTables = $connection->query("SHOW TABLES LIKE 'b_uts_iblock_%_section'");
        while ($table = $ufTables->fetch()) {
            $table = reset($table);
            $iblockId = (int)preg_replace('/\D/', '', $table);
            $hasDesc = $connection->query("SHOW COLUMNS FROM `$table` LIKE 'UF_FULL_DESC'")->fetch();
            if (!$hasDesc) {
                continue;
            }
            $rows = $connection->query("SELECT VALUE_ID, UF_FULL_DESC FROM `$table` WHERE " . $where('UF_FULL_DESC'));
            while ($row = $rows->fetch()) {
                $byId[(int)$row['VALUE_ID']]['IBLOCK_ID'] = $iblockId;
                $byId[(int)$row['VALUE_ID']]['UF_FULL_DESC'] = self::rebrand((string)$row['UF_FULL_DESC']);
            }
        }
        foreach ($byId as $id => $fields) {
            if ((new \CIBlockSection())->Update($id, $fields, false, false)) {
                $sections++;
            }
        }

        // Почтовые шаблоны (MESSAGE_PHP CEventMessage пересобирает сам) и названия типов событий.
        $messages = 0;
        $rows = $connection->query('SELECT ID, SUBJECT, MESSAGE FROM b_event_message WHERE ' . $where('SUBJECT', 'MESSAGE'));
        while ($row = $rows->fetch()) {
            if ((new \CEventMessage())->Update((int)$row['ID'], ['SUBJECT' => self::rebrand((string)$row['SUBJECT']), 'MESSAGE' => self::rebrand((string)$row['MESSAGE'])])) {
                $messages++;
            }
        }
        $types = 0;
        $rows = $connection->query('SELECT ID, NAME FROM b_event_type WHERE ' . $where('NAME'));
        while ($row = $rows->fetch()) {
            if (\CEventType::Update(['ID' => (int)$row['ID']], ['NAME' => self::rebrand((string)$row['NAME'])])) {
                $types++;
            }
        }

        // Название сайта (подставляется в письма как #SITE_NAME#, в заголовки).
        $sites = 0;
        $rows = \CSite::GetList('sort', 'asc', ['ACTIVE' => 'Y']);
        while ($site = $rows->Fetch()) {
            $name = self::rebrand((string)$site['NAME']);
            $siteName = self::rebrand((string)$site['SITE_NAME']);
            if ($name !== $site['NAME'] || $siteName !== $site['SITE_NAME']) {
                $fields = [];
                if ($name !== $site['NAME']) {
                    $fields['NAME'] = $name === 'Маркетплейс Reppa' ? self::SITE_NAME : $name;
                }
                if ($siteName !== $site['SITE_NAME']) {
                    $fields['SITE_NAME'] = $siteName === 'Маркетплейс Reppa' ? self::SITE_NAME : $siteName;
                }
                if ((new \CSite())->Update($site['LID'], $fields)) {
                    $sites++;
                }
            }
        }

        BXClearCache(true);
        $this->outSuccess('Reppa: элементов %d, разделов %d, почтовых шаблонов %d, типов событий %d, сайтов %d', $elements, $sections, $messages, $types, $sites);
    }

    public function down()
    {
        $this->outInfo('Откат названия не выполняется');
    }
}
