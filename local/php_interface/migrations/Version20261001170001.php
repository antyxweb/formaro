<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261001170001 extends Version
{
    protected $description = 'formaro.cabinet: чат по товару (UF_PRODUCT_ID в HL-блоке CabinetChatThreads)';

    /**
     * Диалог покупателя с продавцом — отдельный на каждую тему: заказ
     * (UF_ORDER_NUMBER), товар (UF_PRODUCT_ID; UF_PRODUCT_NAME — название на
     * момент вопроса) или общий вопрос (оба пустые).
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('CabinetChatThreads');

        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('HLBLOCK_' . $hlblockId, [
            ['FIELD_NAME' => 'UF_PRODUCT_ID', 'USER_TYPE_ID' => 'integer'],
        ]);

        $this->outSuccess('Поле UF_PRODUCT_ID в CabinetChatThreads готово');
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('CabinetChatThreads');
        if ($hlblockId) {
            $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists('HLBLOCK_' . $hlblockId, ['UF_PRODUCT_ID']);
            $this->outSuccess('Поле UF_PRODUCT_ID удалено из CabinetChatThreads');
        }
    }
}
