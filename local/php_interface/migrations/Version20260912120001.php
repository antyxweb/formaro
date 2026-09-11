<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20260912120001 extends Version
{
    protected $description = 'formaro.cabinet: HL-блок CabinetOrders (заказы, фаза 2)';

    /**
     * Заказы — свои сущности (HL-блок), без реальной интеграции с модулем
     * Sale (см. план, фаза 2). UF_ITEMS/UF_HISTORY/UF_CUSTOMER — JSON в
     * текстовом поле (тот же компромисс, что и CUSTOM_PROPS_JSON у товаров).
     * UF_PARTNER_ID — явное поле (в прототипе владение выводилось из
     * items[].product_id, здесь проще и надёжнее хранить напрямую).
     * Статусы (status/payment_status) — обычные строки, не enumeration:
     * список статусов у прототипа маленький и фиксированный, человеко-
     * читаемый лейбл всё равно строит клиент (statusPill() в common.js),
     * заводить под это enum-справочник в БД избыточно.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $hlblockId = $helper->Hlblock()->saveHlblock([
            'NAME' => 'CabinetOrders',
            'TABLE_NAME' => 'cabinet_orders',
            'LANG' => [
                'ru' => ['NAME' => 'Заказы (кабинет)'],
                'en' => ['NAME' => 'Cabinet orders'],
            ],
        ]);

        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists(
            'HLBLOCK_' . $hlblockId,
            [
                ['FIELD_NAME' => 'UF_ORDER_NUMBER', 'USER_TYPE_ID' => 'string'],
                ['FIELD_NAME' => 'UF_STATUS', 'USER_TYPE_ID' => 'string'],
                ['FIELD_NAME' => 'UF_CUSTOMER', 'USER_TYPE_ID' => 'string', 'SETTINGS' => ['SIZE' => 1, 'ROWS' => 3]],
                ['FIELD_NAME' => 'UF_ITEMS', 'USER_TYPE_ID' => 'string', 'SETTINGS' => ['SIZE' => 1, 'ROWS' => 10]],
                ['FIELD_NAME' => 'UF_SUBTOTAL', 'USER_TYPE_ID' => 'double'],
                ['FIELD_NAME' => 'UF_TOTAL', 'USER_TYPE_ID' => 'double'],
                ['FIELD_NAME' => 'UF_DELIVERY_METHOD', 'USER_TYPE_ID' => 'string'],
                ['FIELD_NAME' => 'UF_PAYMENT_METHOD', 'USER_TYPE_ID' => 'string'],
                ['FIELD_NAME' => 'UF_PAYMENT_STATUS', 'USER_TYPE_ID' => 'string'],
                ['FIELD_NAME' => 'UF_CUSTOMER_COMMENT', 'USER_TYPE_ID' => 'string', 'SETTINGS' => ['SIZE' => 1, 'ROWS' => 3]],
                ['FIELD_NAME' => 'UF_HISTORY', 'USER_TYPE_ID' => 'string', 'SETTINGS' => ['SIZE' => 1, 'ROWS' => 10]],
                ['FIELD_NAME' => 'UF_DISCOUNT_ID', 'USER_TYPE_ID' => 'integer'],
                ['FIELD_NAME' => 'UF_DISCOUNT_NAME', 'USER_TYPE_ID' => 'string'],
                ['FIELD_NAME' => 'UF_COUPON_CODE', 'USER_TYPE_ID' => 'string'],
                ['FIELD_NAME' => 'UF_DISCOUNT_AMOUNT', 'USER_TYPE_ID' => 'double'],
                ['FIELD_NAME' => 'UF_PARTNER_ID', 'USER_TYPE_ID' => 'integer', 'MANDATORY' => 'Y'],
                ['FIELD_NAME' => 'UF_CREATED_AT', 'USER_TYPE_ID' => 'datetime', 'MANDATORY' => 'Y'],
            ]
        );

        $this->outSuccess('HL-блок "CabinetOrders" готов (ID=%d)', $hlblockId);
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('CabinetOrders');

        if ($hlblockId) {
            $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists(
                'HLBLOCK_' . $hlblockId,
                [
                    'UF_ORDER_NUMBER', 'UF_STATUS', 'UF_CUSTOMER', 'UF_ITEMS', 'UF_SUBTOTAL', 'UF_TOTAL',
                    'UF_DELIVERY_METHOD', 'UF_PAYMENT_METHOD', 'UF_PAYMENT_STATUS', 'UF_CUSTOMER_COMMENT',
                    'UF_HISTORY', 'UF_DISCOUNT_ID', 'UF_DISCOUNT_NAME', 'UF_COUPON_CODE', 'UF_DISCOUNT_AMOUNT',
                    'UF_PARTNER_ID', 'UF_CREATED_AT',
                ]
            );
            $helper->Hlblock()->deleteHlblock($hlblockId);
            $this->outSuccess('HL-блок "CabinetOrders" удалён');
        } else {
            $this->outError('HL-блок "CabinetOrders" не найден');
        }
    }
}
