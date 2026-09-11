<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20260912130001 extends Version
{
    protected $description = 'formaro.cabinet: HL-блоки CabinetTickets и CabinetTicketMessages (техподдержка, фаза 5)';

    /**
     * Обращение (тикет) + отдельный дочерний HL-блок для сообщений — растущий
     * список с файловыми вложениями естественнее как реальные строки, а не
     * JSON-массив внутри одного поля (см. план, HL-блоки без витрины).
     * UF_TICKET_ID — простое целое (ID записи CabinetTickets), не "привязка
     * к элементам" (этот тип UF-полей у HL-блоков — для связи с инфоблоком).
     *
     * UF_TEXT — 'string' с SETTINGS.ROWS > 1: highloadblock создаёт для
     * такого поля колонку TEXT вместо VARCHAR(255) (тот же приём, что и
     * UF_FULL_DESC в Version20260911193002 и UF_TARGET_IDS в
     * Version20260912120002) — сообщения не должны обрезаться по 255 симв.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $ticketsId = $helper->Hlblock()->saveHlblock([
            'NAME' => 'CabinetTickets',
            'TABLE_NAME' => 'cabinet_tickets',
            'LANG' => ['ru' => ['NAME' => 'Обращения в поддержку (кабинет)'], 'en' => ['NAME' => 'Cabinet tickets']],
        ]);
        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('HLBLOCK_' . $ticketsId, [
            ['FIELD_NAME' => 'UF_SUBJECT', 'USER_TYPE_ID' => 'string', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_STATUS', 'USER_TYPE_ID' => 'string', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_PARTNER_ID', 'USER_TYPE_ID' => 'integer', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_CREATED_AT', 'USER_TYPE_ID' => 'datetime', 'MANDATORY' => 'Y'],
        ]);

        $messagesId = $helper->Hlblock()->saveHlblock([
            'NAME' => 'CabinetTicketMessages',
            'TABLE_NAME' => 'cabinet_ticket_messages',
            'LANG' => ['ru' => ['NAME' => 'Сообщения обращений (кабинет)'], 'en' => ['NAME' => 'Cabinet ticket messages']],
        ]);
        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('HLBLOCK_' . $messagesId, [
            ['FIELD_NAME' => 'UF_TICKET_ID', 'USER_TYPE_ID' => 'integer', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_SENDER', 'USER_TYPE_ID' => 'string', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_TEXT', 'USER_TYPE_ID' => 'string', 'SETTINGS' => ['SIZE' => 1, 'ROWS' => 5]],
            ['FIELD_NAME' => 'UF_DATE', 'USER_TYPE_ID' => 'datetime', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_ATTACHMENTS', 'USER_TYPE_ID' => 'file', 'MULTIPLE' => 'Y'],
        ]);

        $this->outSuccess(
            'HL-блоки "CabinetTickets" (ID=%d) и "CabinetTicketMessages" (ID=%d) готовы',
            $ticketsId,
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
            'CabinetTickets' => ['UF_SUBJECT', 'UF_STATUS', 'UF_PARTNER_ID', 'UF_CREATED_AT'],
            'CabinetTicketMessages' => ['UF_TICKET_ID', 'UF_SENDER', 'UF_TEXT', 'UF_DATE', 'UF_ATTACHMENTS'],
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
