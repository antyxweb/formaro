<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261001150001 extends Version
{
    protected $description = 'formaro.cabinet: уведомления покупателя (UF_USER_ID в HL-блоке CabinetNotifications)';

    /**
     * Уведомления покупателю (/personal/notify/) — в том же HL-блоке, что
     * уведомления партнёра: у покупательских UF_USER_ID — id пользователя,
     * UF_PARTNER_ID — 0 (кабинет партнёра выбирает по UF_PARTNER_ID и их не
     * видит). UF_LINK у них — адрес на сайте (/personal/orders/#order-14).
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('CabinetNotifications');

        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('HLBLOCK_' . $hlblockId, [
            ['FIELD_NAME' => 'UF_USER_ID', 'USER_TYPE_ID' => 'integer'],
        ]);

        $this->outSuccess('Поле UF_USER_ID в CabinetNotifications готово');
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('CabinetNotifications');
        if ($hlblockId) {
            $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists('HLBLOCK_' . $hlblockId, ['UF_USER_ID']);
            $this->outSuccess('Поле UF_USER_ID удалено из CabinetNotifications');
        }
    }
}
