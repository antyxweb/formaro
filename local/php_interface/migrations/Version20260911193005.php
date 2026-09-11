<?php

namespace Sprint\Migration;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;
use Sprint\Migration\Exceptions\HelperException;

class Version20260911193005 extends Version
{
    protected $description = 'formaro.cabinet: импорт готового HL-блока ProductColors из ProductColors.xml';

    /**
     * ProductColors.xml — реальный экспорт HL-блока (справочник цветов,
     * 247 строк с русским/английским названием, hex, rgb), лежит в корне
     * сайта. Это не сущность кабинета, а готовый общий справочник — миграция
     * ТОЛЬКО импортирует его (структуру полей и строки, обе — прямо из XML,
     * без хардкода схемы, чтобы не разойтись с источником), и идемпотентна:
     * если HL-блок "ProductColors" уже существует (создан кем-то на сервере
     * раньше) — ничего не делает.
     *
     * Используется для автоподсказок в поле "Цвет" товара (см.
     * cabinet-html/assets/js/product-detail.js — тот же паттерн подсказок,
     * только источник данных вместо data/product-colors.json станет этот
     * HL-блок). Аналогичный HL-блок для "Размеров" будет заведён отдельной
     * миграцией по этому же образцу, когда появится ProductSizes.xml — его
     * пока нет.
     *
     * @throws HelperException
     */
    public function up()
    {
        Loader::includeModule('highloadblock');
        $helper = $this->getHelperManager();

        $existing = $helper->Hlblock()->getHlblockIfExists('ProductColors');
        if ($existing) {
            $this->outNotice('HL-блок "ProductColors" уже существует (ID=%d) — импорт пропущен', $existing['ID']);
            return;
        }

        $xmlPath = $_SERVER['DOCUMENT_ROOT'] . '/ProductColors.xml';
        if (!is_file($xmlPath)) {
            throw new HelperException('Файл не найден: ' . $xmlPath);
        }

        $xml = simplexml_load_file($xmlPath);
        if ($xml === false) {
            throw new HelperException('Не удалось распарсить ' . $xmlPath);
        }

        $name = (string)$xml->hiblock->name;
        $tableName = (string)$xml->hiblock->table_name;
        if ($name === '' || $tableName === '') {
            throw new HelperException('В ProductColors.xml не найдены name/table_name HL-блока');
        }

        $lang = [];
        foreach ($xml->langs->lang as $langNode) {
            $lid = (string)$langNode->lid;
            $lang[$lid] = ['NAME' => (string)$langNode->name];
        }

        $hlblockId = $helper->Hlblock()->addHlblock([
            'NAME' => $name,
            'TABLE_NAME' => $tableName,
            'LANG' => $lang,
        ]);

        foreach ($xml->fields->field as $fieldNode) {
            $fieldName = (string)$fieldNode->field_name;

            $settings = [];
            $size = (string)$fieldNode->settings->size;
            $rows = (string)$fieldNode->settings->rows;
            if ($size !== '') {
                $settings['SIZE'] = (int)$size;
            }
            if ($rows !== '') {
                $settings['ROWS'] = (int)$rows;
            }

            $helper->UserTypeEntity()->addUserTypeEntityIfNotExists(
                'HLBLOCK_' . $hlblockId,
                $fieldName,
                [
                    'USER_TYPE_ID' => (string)$fieldNode->user_type_id,
                    'MULTIPLE' => (string)$fieldNode->multiple === 'Y' ? 'Y' : 'N',
                    'MANDATORY' => (string)$fieldNode->mandatory === 'Y' ? 'Y' : 'N',
                    'SETTINGS' => $settings,
                    'EDIT_FORM_LABEL' => [
                        'ru' => (string)$fieldNode->edit_form_label->ru,
                        'en' => (string)$fieldNode->edit_form_label->en,
                    ],
                ]
            );
        }

        $hlblockRow = HighloadBlockTable::getById($hlblockId)->fetch();
        $dataClass = HighloadBlockTable::compileEntity($hlblockRow)->getDataClass();

        $imported = 0;
        foreach ($xml->items->item as $itemNode) {
            $fields = [];
            foreach ($itemNode->children() as $child) {
                $tag = $child->getName();
                if ($tag === 'id') {
                    continue; // старый numeric id из экспорта не переносим — HL сам назначит новый
                }
                $fields[strtoupper($tag)] = (string)$child;
            }
            $result = $dataClass::add($fields);
            if ($result->isSuccess()) {
                $imported++;
            }
        }

        $this->outSuccess('HL-блок "%s" создан (ID=%d), импортировано строк: %d', $name, $hlblockId, $imported);
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('ProductColors');

        if ($hlblockId) {
            $helper->Hlblock()->deleteHlblock($hlblockId);
            $this->outSuccess('HL-блок "ProductColors" удалён');
        } else {
            $this->outError('HL-блок "ProductColors" не найден');
        }
    }
}
