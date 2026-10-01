<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261002120001 extends Version
{
    protected $description = 'Подписки на рассылку: привязка к партнёру (UF_PARTNER_ID) — «Будьте в курсе» в разделе партнёра';

    /**
     * В разделе партнёра (/partners/<код>/…) форма «Будьте в курсе»
     * подписывает на обновления только этого партнёра — без выбора темы
     * (SubscriptionService).
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('FormaroSubscriptions');
        $label = 'Партнёр: ID элемента cabinet_partners (0 — общая подписка)';
        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('HLBLOCK_' . $hlblockId, [[
            'FIELD_NAME' => 'UF_PARTNER_ID',
            'USER_TYPE_ID' => 'integer',
            'MANDATORY' => 'N',
            'SORT' => 145,
            'SHOW_FILTER' => 'I',
            'EDIT_FORM_LABEL' => ['ru' => $label],
            'LIST_COLUMN_LABEL' => ['ru' => $label],
            'LIST_FILTER_LABEL' => ['ru' => $label],
        ]]);

        $this->outSuccess('Поле UF_PARTNER_ID в FormaroSubscriptions готово');
    }

    /** @throws HelperException */
    public function down()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('FormaroSubscriptions');
        $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists('HLBLOCK_' . $hlblockId, ['UF_PARTNER_ID']);
        $this->outSuccess('Поле UF_PARTNER_ID удалено');
    }
}
