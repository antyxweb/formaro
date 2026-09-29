<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20260929150001 extends Version
{
    protected $description = 'formaro.cabinet: HL-блок ViewedProducts (просмотренные товары авторизованных пользователей)';

    /**
     * «Просмотренные товары» на карточке товара для авторизованных
     * пользователей. У гостя список в localStorage и при входе переносится
     * сюда (см. ViewedRepository::merge(), FormaroViewed в catalog-common.js).
     * Одна строка на пару пользователь+товар, повторный просмотр обновляет
     * UF_VIEWED_AT. UF_PRODUCT_ID — ID элемента инфоблока cabinet_catalog.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $hlblockId = $helper->Hlblock()->saveHlblock([
            'NAME' => 'ViewedProducts',
            'TABLE_NAME' => 'formaro_viewed_products',
            'LANG' => ['ru' => ['NAME' => 'Просмотренные товары'], 'en' => ['NAME' => 'Viewed products']],
        ]);

        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('HLBLOCK_' . $hlblockId, [
            ['FIELD_NAME' => 'UF_USER_ID', 'USER_TYPE_ID' => 'integer', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_PRODUCT_ID', 'USER_TYPE_ID' => 'integer', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_VIEWED_AT', 'USER_TYPE_ID' => 'datetime', 'MANDATORY' => 'Y'],
        ]);

        $this->outSuccess('HL-блок "ViewedProducts" готов (ID=%d)', $hlblockId);
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('ViewedProducts');

        if ($hlblockId) {
            $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists('HLBLOCK_' . $hlblockId, [
                'UF_USER_ID', 'UF_PRODUCT_ID', 'UF_VIEWED_AT',
            ]);
            $helper->Hlblock()->deleteHlblock($hlblockId);
            $this->outSuccess('HL-блок "ViewedProducts" удалён');
        } else {
            $this->outError('HL-блок "ViewedProducts" не найден');
        }
    }
}
