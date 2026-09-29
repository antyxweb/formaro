<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20260929160001 extends Version
{
    protected $description = 'formaro.cabinet: HL-блок CartItems (корзина авторизованных пользователей)';

    /**
     * Корзина витрины для авторизованных пользователей: одна строка на
     * пару пользователь+товар с количеством. У гостя корзина в
     * localStorage и при входе переносится сюда (CartRepository::merge(),
     * js/cart.js). Цены не храним — считаются при показе
     * (ProductPricingService), корзина не заказ. UF_PRODUCT_ID — ID
     * элемента инфоблока cabinet_catalog.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $hlblockId = $helper->Hlblock()->saveHlblock([
            'NAME' => 'CartItems',
            'TABLE_NAME' => 'formaro_cart_items',
            'LANG' => ['ru' => ['NAME' => 'Корзина'], 'en' => ['NAME' => 'Cart items']],
        ]);

        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('HLBLOCK_' . $hlblockId, [
            ['FIELD_NAME' => 'UF_USER_ID', 'USER_TYPE_ID' => 'integer', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_PRODUCT_ID', 'USER_TYPE_ID' => 'integer', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_QUANTITY', 'USER_TYPE_ID' => 'integer', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_ADDED_AT', 'USER_TYPE_ID' => 'datetime', 'MANDATORY' => 'Y'],
        ]);

        $this->outSuccess('HL-блок "CartItems" готов (ID=%d)', $hlblockId);
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('CartItems');

        if ($hlblockId) {
            $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists('HLBLOCK_' . $hlblockId, [
                'UF_USER_ID', 'UF_PRODUCT_ID', 'UF_QUANTITY', 'UF_ADDED_AT',
            ]);
            $helper->Hlblock()->deleteHlblock($hlblockId);
            $this->outSuccess('HL-блок "CartItems" удалён');
        } else {
            $this->outError('HL-блок "CartItems" не найден');
        }
    }
}
