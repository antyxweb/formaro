<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261004110001 extends Version
{
    protected $description = 'Чаты покупателей с партнёрами: пометка подозрительного сообщения (UF_ALERT) — обмен контактами, разговоры об оплате в обход площадки';

    /**
     * UF_ALERT сообщения CabinetChatMessages — что нашла проверка
     * (ChatModerationService): «Телефон; Оплата/перевод» и т. п. Пусто —
     * сообщение чистое. Администратор видит пометку в «Чатах с клиентами»
     * и получает уведомление.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('CabinetChatMessages');
        $label = 'Проверка: найдено (контакты, оплата в обход площадки)';
        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('HLBLOCK_' . $hlblockId, [[
            'FIELD_NAME' => 'UF_ALERT',
            'USER_TYPE_ID' => 'string',
            'MANDATORY' => 'N',
            'SORT' => 160,
            'SHOW_FILTER' => 'S',
            'EDIT_FORM_LABEL' => ['ru' => $label],
            'LIST_COLUMN_LABEL' => ['ru' => $label],
            'LIST_FILTER_LABEL' => ['ru' => $label],
        ]]);

        $this->outSuccess('Поле UF_ALERT в CabinetChatMessages готово');
    }

    /** @throws HelperException */
    public function down()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('CabinetChatMessages');
        $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists('HLBLOCK_' . $hlblockId, ['UF_ALERT']);
        $this->outSuccess('Поле UF_ALERT удалено');
    }
}
