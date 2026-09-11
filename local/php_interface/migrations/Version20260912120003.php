<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20260912120003 extends Version
{
    protected $description = 'formaro.cabinet: HL-блок CabinetTransactions (финансы, фаза 3)';

    /**
     * Баланс (available/pending/total_earned) НЕ хранится — считается на
     * лету суммой транзакций (см. Formaro\Cabinet\Repository\
     * TransactionRepository::getSummary()), чтобы не рассинхронизироваться
     * с реальной историей операций, как это было у демо-данных прототипа.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $hlblockId = $helper->Hlblock()->saveHlblock([
            'NAME' => 'CabinetTransactions',
            'TABLE_NAME' => 'cabinet_transactions',
            'LANG' => ['ru' => ['NAME' => 'Финансовые операции (кабинет)'], 'en' => ['NAME' => 'Cabinet transactions']],
        ]);

        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('HLBLOCK_' . $hlblockId, [
            ['FIELD_NAME' => 'UF_TYPE', 'USER_TYPE_ID' => 'string', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_AMOUNT', 'USER_TYPE_ID' => 'double', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_STATUS', 'USER_TYPE_ID' => 'string'],
            ['FIELD_NAME' => 'UF_DESCRIPTION', 'USER_TYPE_ID' => 'string'],
            ['FIELD_NAME' => 'UF_PARTNER_ID', 'USER_TYPE_ID' => 'integer', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_CREATED_AT', 'USER_TYPE_ID' => 'datetime', 'MANDATORY' => 'Y'],
        ]);

        $this->outSuccess('HL-блок "CabinetTransactions" готов (ID=%d)', $hlblockId);
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('CabinetTransactions');

        if ($hlblockId) {
            $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists('HLBLOCK_' . $hlblockId, [
                'UF_TYPE', 'UF_AMOUNT', 'UF_STATUS', 'UF_DESCRIPTION', 'UF_PARTNER_ID', 'UF_CREATED_AT',
            ]);
            $helper->Hlblock()->deleteHlblock($hlblockId);
            $this->outSuccess('HL-блок "CabinetTransactions" удалён');
        } else {
            $this->outError('HL-блок "CabinetTransactions" не найден');
        }
    }
}
