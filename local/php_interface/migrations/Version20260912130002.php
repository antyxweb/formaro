<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20260912130002 extends Version
{
    protected $description = 'formaro.cabinet: HL-блоки CabinetChatThreads и CabinetChatMessages (чат с клиентами, фаза 5)';

    /**
     * Диалог с клиентом + дочерний HL-блок сообщений — тот же приём, что и
     * CabinetTickets/CabinetTicketMessages (Version20260912130001). Клиент
     * маркетплейса не является пользователем кабинета партнёра — это просто
     * покупатель на витрине, поэтому UF_CLIENT_NAME — простое строковое
     * значение, а не связь с UF-полями/инфоблоком. UF_ORDER_NUMBER/
     * UF_PRODUCT_NAME — как в прототипе (chat.json: order_id/product_name),
     * свободный текст для отображения в списке диалогов, а не жёсткая
     * привязка к CabinetOrders/cabinet_catalog — в прототипе эта связь тоже
     * не была реляционной (демо-данные не ссылались на реальные заказы).
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $threadsId = $helper->Hlblock()->saveHlblock([
            'NAME' => 'CabinetChatThreads',
            'TABLE_NAME' => 'cabinet_chat_threads',
            'LANG' => ['ru' => ['NAME' => 'Диалоги с клиентами (кабинет)'], 'en' => ['NAME' => 'Cabinet chat threads']],
        ]);
        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('HLBLOCK_' . $threadsId, [
            ['FIELD_NAME' => 'UF_CLIENT_NAME', 'USER_TYPE_ID' => 'string', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_ORDER_NUMBER', 'USER_TYPE_ID' => 'string'],
            ['FIELD_NAME' => 'UF_PRODUCT_NAME', 'USER_TYPE_ID' => 'string'],
            ['FIELD_NAME' => 'UF_PARTNER_ID', 'USER_TYPE_ID' => 'integer', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_CREATED_AT', 'USER_TYPE_ID' => 'datetime', 'MANDATORY' => 'Y'],
        ]);

        $messagesId = $helper->Hlblock()->saveHlblock([
            'NAME' => 'CabinetChatMessages',
            'TABLE_NAME' => 'cabinet_chat_messages',
            'LANG' => ['ru' => ['NAME' => 'Сообщения диалогов (кабинет)'], 'en' => ['NAME' => 'Cabinet chat messages']],
        ]);
        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('HLBLOCK_' . $messagesId, [
            ['FIELD_NAME' => 'UF_THREAD_ID', 'USER_TYPE_ID' => 'integer', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_SENDER', 'USER_TYPE_ID' => 'string', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_TEXT', 'USER_TYPE_ID' => 'string', 'SETTINGS' => ['SIZE' => 1, 'ROWS' => 5]],
            ['FIELD_NAME' => 'UF_DATE', 'USER_TYPE_ID' => 'datetime', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_IS_READ', 'USER_TYPE_ID' => 'boolean'],
            ['FIELD_NAME' => 'UF_ATTACHMENTS', 'USER_TYPE_ID' => 'file', 'MULTIPLE' => 'Y'],
        ]);

        $this->outSuccess(
            'HL-блоки "CabinetChatThreads" (ID=%d) и "CabinetChatMessages" (ID=%d) готовы',
            $threadsId,
            $messagesId
        );
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();

        foreach ([
            'CabinetChatThreads' => ['UF_CLIENT_NAME', 'UF_ORDER_NUMBER', 'UF_PRODUCT_NAME', 'UF_PARTNER_ID', 'UF_CREATED_AT'],
            'CabinetChatMessages' => ['UF_THREAD_ID', 'UF_SENDER', 'UF_TEXT', 'UF_DATE', 'UF_IS_READ', 'UF_ATTACHMENTS'],
        ] as $name => $fields) {
            $id = $helper->Hlblock()->getHlblockIdIfExists($name);
            if ($id) {
                $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists('HLBLOCK_' . $id, $fields);
                $helper->Hlblock()->deleteHlblock($id);
                $this->outSuccess('HL-блок "%s" удалён', $name);
            } else {
                $this->outError('HL-блок "' . $name . '" не найден');
            }
        }
    }
}
