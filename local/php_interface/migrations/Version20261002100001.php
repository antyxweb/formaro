<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261002100001 extends Version
{
    protected $description = 'Подписки на рассылку из форм «Будьте в курсе» и «Будь всегда в форме!» (HL-блок FormaroSubscriptions)';

    private const FIELDS = [
        'UF_EMAIL' => ['string', 'E-mail', 'Y'],
        'UF_FORM' => ['string', 'Форма (news — «Будьте в курсе», footer — «Будь всегда в форме!»)', 'Y'],
        'UF_TOPIC_ID' => ['integer', 'Тема: ID раздела каталога (0 — все темы)', 'N'],
        'UF_TOPIC_NAME' => ['string', 'Тема', 'N'],
        'UF_USER_ID' => ['integer', 'Пользователь (если вошёл)', 'N'],
        'UF_PAGE' => ['string', 'Страница, с которой подписались', 'N'],
        'UF_IP' => ['string', 'IP', 'N'],
        'UF_CREATED_AT' => ['datetime', 'Дата подписки', 'Y'],
    ];

    /**
     * Пока рассылки нет — подписки просто копятся здесь (форма, e-mail,
     * тема). Повторная подписка того же e-mail на ту же тему в той же форме
     * новую запись не создаёт (SubscriptionService).
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->saveHlblock([
            'NAME' => 'FormaroSubscriptions',
            'TABLE_NAME' => 'formaro_subscriptions',
            'LANG' => ['ru' => ['NAME' => 'Подписки на рассылку'], 'en' => ['NAME' => 'Newsletter subscriptions']],
        ]);

        $fields = [];
        $sort = 100;
        foreach (self::FIELDS as $code => [$type, $label, $mandatory]) {
            $fields[] = [
                'FIELD_NAME' => $code,
                'USER_TYPE_ID' => $type,
                'MANDATORY' => $mandatory,
                'SORT' => $sort += 10,
                'SHOW_FILTER' => 'I',
                'EDIT_FORM_LABEL' => ['ru' => $label],
                'LIST_COLUMN_LABEL' => ['ru' => $label],
                'LIST_FILTER_LABEL' => ['ru' => $label],
            ];
        }
        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('HLBLOCK_' . $hlblockId, $fields);

        $this->outSuccess('HL-блок "FormaroSubscriptions" готов (ID=%d)', $hlblockId);
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('FormaroSubscriptions');
        if ($hlblockId) {
            $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists('HLBLOCK_' . $hlblockId, array_keys(self::FIELDS));
            $helper->Hlblock()->deleteHlblock($hlblockId);
        }
        $this->outSuccess('HL-блок "FormaroSubscriptions" удалён');
    }
}
