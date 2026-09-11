<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20260912120002 extends Version
{
    protected $description = 'formaro.cabinet: HL-блоки CabinetDiscounts и CabinetCoupons (фаза 3)';

    /**
     * В прототипе discounts.json хранил обе сущности одним объектом
     * {discounts, coupons} — приём, нужный только из-за localStorage
     * (см. cabinet-html/CLAUDE.md). С реальной БД в этом нет смысла —
     * два независимых HL-блока. TARGET_IDS — JSON-массив ID (ELEMENT_ID
     * товаров или SECTION_ID категорий инфоблока cabinet_catalog, в
     * зависимости от TARGET_TYPE).
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $discountsId = $helper->Hlblock()->saveHlblock([
            'NAME' => 'CabinetDiscounts',
            'TABLE_NAME' => 'cabinet_discounts',
            'LANG' => ['ru' => ['NAME' => 'Скидки (кабинет)'], 'en' => ['NAME' => 'Cabinet discounts']],
        ]);
        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('HLBLOCK_' . $discountsId, [
            ['FIELD_NAME' => 'UF_NAME', 'USER_TYPE_ID' => 'string', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_TARGET_TYPE', 'USER_TYPE_ID' => 'string'],
            ['FIELD_NAME' => 'UF_TARGET_IDS', 'USER_TYPE_ID' => 'string', 'SETTINGS' => ['SIZE' => 1, 'ROWS' => 3]],
            ['FIELD_NAME' => 'UF_DISCOUNT_TYPE', 'USER_TYPE_ID' => 'string'],
            ['FIELD_NAME' => 'UF_VALUE', 'USER_TYPE_ID' => 'double'],
            ['FIELD_NAME' => 'UF_MIN_QTY', 'USER_TYPE_ID' => 'integer'],
            ['FIELD_NAME' => 'UF_MIN_AMOUNT', 'USER_TYPE_ID' => 'double'],
            ['FIELD_NAME' => 'UF_DATE_FROM', 'USER_TYPE_ID' => 'date'],
            ['FIELD_NAME' => 'UF_DATE_TO', 'USER_TYPE_ID' => 'date'],
            ['FIELD_NAME' => 'UF_STATUS', 'USER_TYPE_ID' => 'string'],
            ['FIELD_NAME' => 'UF_PARTNER_ID', 'USER_TYPE_ID' => 'integer', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_CREATED_AT', 'USER_TYPE_ID' => 'datetime', 'MANDATORY' => 'Y'],
        ]);

        $couponsId = $helper->Hlblock()->saveHlblock([
            'NAME' => 'CabinetCoupons',
            'TABLE_NAME' => 'cabinet_coupons',
            'LANG' => ['ru' => ['NAME' => 'Купоны (кабинет)'], 'en' => ['NAME' => 'Cabinet coupons']],
        ]);
        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('HLBLOCK_' . $couponsId, [
            ['FIELD_NAME' => 'UF_CODE', 'USER_TYPE_ID' => 'string', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_DISCOUNT_TYPE', 'USER_TYPE_ID' => 'string'],
            ['FIELD_NAME' => 'UF_VALUE', 'USER_TYPE_ID' => 'double'],
            ['FIELD_NAME' => 'UF_USAGE_TYPE', 'USER_TYPE_ID' => 'string'],
            ['FIELD_NAME' => 'UF_USED_COUNT', 'USER_TYPE_ID' => 'integer'],
            ['FIELD_NAME' => 'UF_DATE_FROM', 'USER_TYPE_ID' => 'date'],
            ['FIELD_NAME' => 'UF_DATE_TO', 'USER_TYPE_ID' => 'date'],
            ['FIELD_NAME' => 'UF_STATUS', 'USER_TYPE_ID' => 'string'],
            ['FIELD_NAME' => 'UF_PARTNER_ID', 'USER_TYPE_ID' => 'integer', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_CREATED_AT', 'USER_TYPE_ID' => 'datetime', 'MANDATORY' => 'Y'],
        ]);

        $this->outSuccess('HL-блоки "CabinetDiscounts" (ID=%d) и "CabinetCoupons" (ID=%d) готовы', $discountsId, $couponsId);
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();

        foreach (['CabinetDiscounts' => [
            'UF_NAME', 'UF_TARGET_TYPE', 'UF_TARGET_IDS', 'UF_DISCOUNT_TYPE', 'UF_VALUE', 'UF_MIN_QTY',
            'UF_MIN_AMOUNT', 'UF_DATE_FROM', 'UF_DATE_TO', 'UF_STATUS', 'UF_PARTNER_ID', 'UF_CREATED_AT',
        ], 'CabinetCoupons' => [
            'UF_CODE', 'UF_DISCOUNT_TYPE', 'UF_VALUE', 'UF_USAGE_TYPE', 'UF_USED_COUNT', 'UF_DATE_FROM',
            'UF_DATE_TO', 'UF_STATUS', 'UF_PARTNER_ID', 'UF_CREATED_AT',
        ]] as $name => $fields) {
            $id = $helper->Hlblock()->getHlblockIdIfExists($name);
            if ($id) {
                $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists('HLBLOCK_' . $id, $fields);
                $helper->Hlblock()->deleteHlblock($id);
                $this->outSuccess('HL-блок "%s" удалён', $name);
            } else {
                $this->outError('HL-блок "' . $name . '" не найден');
            }
        }
    }
}
