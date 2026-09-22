<?php

namespace Formaro\Cabinet\Repository;

/** Справочник цветов (HL-блок ProductColors, импортирован миграцией из
 *  ProductColors.xml) — источник данных для автоподсказок в поле "Цвет"
 *  товара (тот же UX, что в cabinet-html/assets/js/product-detail.js,
 *  только источник данных теперь не data/product-colors.json). */
class ProductColorRepository
{
    private const HLBLOCK_NAME = 'ProductColors';

    public function listAll(): array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $rows = $dataClass::getList(['order' => ['UF_NAME_RU' => 'ASC']])->fetchAll();

        return array_map(static fn(array $r) => [
            'name' => $r['UF_NAME_RU'],
            'name_en' => $r['UF_NAME_EN'],
            'hex' => $r['UF_HEX'],
        ], $rows);
    }
}
