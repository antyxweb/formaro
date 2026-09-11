<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20260912140001 extends Version
{
    protected $description = 'formaro.cabinet: HL-блок CabinetNotifications (уведомления, фаза 6)';

    /**
     * Уведомления партнёра — плоский список без своей витрины (см. план,
     * HL-блоки без витрины). В отличие от заказов/скидок/etc. эта сущность
     * не редактируется партнёром — только читается и помечается
     * прочитанной/прочитанными (см. NotificationRepository). Ничто в этой
     * фазе автоматически не создаёт записи здесь при реальных событиях
     * (новый заказ, сообщение в чате и т.д.) — это отдельная, более крупная
     * интеграционная задача (события нужно генерировать из каждого места,
     * где меняются заказы/чат/финансы/тикеты), сознательно вынесенная за
     * рамки этой фазы; сущность и её отображение в кабинете уже полностью
     * готовы к тому, чтобы что-то в них писало.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $hlblockId = $helper->Hlblock()->saveHlblock([
            'NAME' => 'CabinetNotifications',
            'TABLE_NAME' => 'cabinet_notifications',
            'LANG' => ['ru' => ['NAME' => 'Уведомления (кабинет)'], 'en' => ['NAME' => 'Cabinet notifications']],
        ]);

        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('HLBLOCK_' . $hlblockId, [
            ['FIELD_NAME' => 'UF_TYPE', 'USER_TYPE_ID' => 'string', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_TITLE', 'USER_TYPE_ID' => 'string', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_MESSAGE', 'USER_TYPE_ID' => 'string', 'SETTINGS' => ['SIZE' => 1, 'ROWS' => 3]],
            ['FIELD_NAME' => 'UF_IS_READ', 'USER_TYPE_ID' => 'boolean'],
            ['FIELD_NAME' => 'UF_PARTNER_ID', 'USER_TYPE_ID' => 'integer', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_CREATED_AT', 'USER_TYPE_ID' => 'datetime', 'MANDATORY' => 'Y'],
        ]);

        $this->outSuccess('HL-блок "CabinetNotifications" готов (ID=%d)', $hlblockId);
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('CabinetNotifications');

        if ($hlblockId) {
            $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists('HLBLOCK_' . $hlblockId, [
                'UF_TYPE', 'UF_TITLE', 'UF_MESSAGE', 'UF_IS_READ', 'UF_PARTNER_ID', 'UF_CREATED_AT',
            ]);
            $helper->Hlblock()->deleteHlblock($hlblockId);
            $this->outSuccess('HL-блок "CabinetNotifications" удалён');
        } else {
            $this->outError('HL-блок "CabinetNotifications" не найден');
        }
    }
}
