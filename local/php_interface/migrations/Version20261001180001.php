<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261001180001 extends Version
{
    protected $description = 'formaro.cabinet: уведомления о сообщениях чата — через час, если не прочитаны (UF_NOTIFIED + агент)';

    private const AGENT = '\\Formaro\\Cabinet\\Service\\ChatNotificationService::agent();';

    /**
     * Уведомление о новом сообщении чата (продавцу — от покупателя,
     * покупателю — от продавца) приходит, только если сообщение не прочитали
     * в течение часа. Проверяет агент раз в 5 минут
     * (ChatNotificationService::agent()); UF_NOTIFIED — по сообщению
     * уведомление уже отправлено.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('CabinetChatMessages');
        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('HLBLOCK_' . $hlblockId, [
            ['FIELD_NAME' => 'UF_NOTIFIED', 'USER_TYPE_ID' => 'boolean'],
        ]);

        if (!\CAgent::GetList([], ['NAME' => self::AGENT])->Fetch()) {
            \CAgent::AddAgent(self::AGENT, 'formaro.cabinet', 'N', 300, '', 'Y', ConvertTimeStamp(time() + 300, 'FULL'));
        }

        $this->outSuccess('UF_NOTIFIED в CabinetChatMessages и агент уведомлений чата готовы');
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        \CAgent::RemoveAgent(self::AGENT, 'formaro.cabinet');
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('CabinetChatMessages');
        if ($hlblockId) {
            $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists('HLBLOCK_' . $hlblockId, ['UF_NOTIFIED']);
        }
        $this->outSuccess('Агент уведомлений чата и UF_NOTIFIED удалены');
    }
}
