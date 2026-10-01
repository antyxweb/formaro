<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261001160001 extends Version
{
    protected $description = 'formaro.cabinet: чат покупателя с продавцом (UF_USER_ID в HL-блоке CabinetChatThreads)';

    /**
     * Диалог чата — покупатель (UF_USER_ID) × продавец (UF_PARTNER_ID), один
     * на пару: покупатель пишет из «Чатов и сообщений» (/personal/messages/),
     * продавец отвечает в «Чате с клиентами» кабинета.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('CabinetChatThreads');

        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('HLBLOCK_' . $hlblockId, [
            ['FIELD_NAME' => 'UF_USER_ID', 'USER_TYPE_ID' => 'integer'],
        ]);

        $this->outSuccess('Поле UF_USER_ID в CabinetChatThreads готово');
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('CabinetChatThreads');
        if ($hlblockId) {
            $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists('HLBLOCK_' . $hlblockId, ['UF_USER_ID']);
            $this->outSuccess('Поле UF_USER_ID удалено из CabinetChatThreads');
        }
    }
}
