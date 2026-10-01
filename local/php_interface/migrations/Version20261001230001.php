<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261001230001 extends Version
{
    protected $description = 'Контентные страницы: встроенный блок «Форма регистрации партнёра» на странице «Стать партнером»';

    /**
     * В свойство «Встроенные блоки» (EMBED) — значение «Форма регистрации
     * партнёра» (XML_ID partner_registration: блок-компонент
     * formaro:partner.register, не инфоблок); на странице «Стать партнером»
     * (become-a-partner) оно добавляется к уже выбранным блокам.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists('content_pages', 'content');
        $property = \CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => 'EMBED'])->Fetch();
        if (!$property) {
            throw new HelperException('Нет свойства EMBED — сначала Version20261001190001');
        }

        $enum = \CIBlockPropertyEnum::GetList([], ['PROPERTY_ID' => $property['ID'], 'XML_ID' => 'partner_registration'])->Fetch();
        $enumId = $enum ? (int)$enum['ID'] : (int)(new \CIBlockPropertyEnum())->Add([
            'PROPERTY_ID' => $property['ID'],
            'VALUE' => 'Форма регистрации партнёра',
            'XML_ID' => 'partner_registration',
            'SORT' => 400,
        ]);
        if (!$enumId) {
            throw new HelperException('Не удалось добавить значение partner_registration');
        }

        $elementId = $helper->Iblock()->getElementId($iblockId, 'become-a-partner');
        if ($elementId) {
            $values = [];
            $res = \CIBlockElement::GetProperty($iblockId, $elementId, [], ['CODE' => 'EMBED']);
            while ($row = $res->Fetch()) {
                if ($row['VALUE']) {
                    $values[] = (int)$row['VALUE'];
                }
            }
            if (!in_array($enumId, $values, true)) {
                $values[] = $enumId;
                \CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, ['EMBED' => $values]);
            }
        }
        \CIBlock::clearIblockTagCache($iblockId);

        $this->outSuccess('Блок «Форма регистрации партнёра» добавлен');
    }

    /** @throws HelperException */
    public function down()
    {
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists('content_pages', 'content');
        $property = \CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => 'EMBED'])->Fetch();
        $enum = $property ? \CIBlockPropertyEnum::GetList([], ['PROPERTY_ID' => $property['ID'], 'XML_ID' => 'partner_registration'])->Fetch() : null;
        if ($enum) {
            \CIBlockPropertyEnum::Delete($enum['ID']);
        }
        \CIBlock::clearIblockTagCache($iblockId);
        $this->outSuccess('Блок «Форма регистрации партнёра» удалён');
    }
}
