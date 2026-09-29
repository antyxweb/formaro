<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20260929170001 extends Version
{
    protected $description = 'formaro.cabinet: поле UF_USER_ID (покупатель) в HL-блоке CabinetOrders';

    /**
     * Заказы с витрины (/personal/cart/ → CartCheckoutService) — с ID
     * пользователя-покупателя, для будущей страницы «Мои заказы». У
     * заказов, созданных партнёром в кабинете вручную, — 0.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('CabinetOrders');

        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('HLBLOCK_' . $hlblockId, [
            ['FIELD_NAME' => 'UF_USER_ID', 'USER_TYPE_ID' => 'integer'],
        ]);

        $this->outSuccess('Поле UF_USER_ID в CabinetOrders готово');
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('CabinetOrders');

        if ($hlblockId) {
            $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists('HLBLOCK_' . $hlblockId, ['UF_USER_ID']);
            $this->outSuccess('Поле UF_USER_ID удалено из CabinetOrders');
        }
    }
}
