<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261008100001 extends Version
{
    protected $description = 'Финансы, этап 4: выплата поставщику по трек-номеру, операции по заказам, без заявок на вывод';

    /** Новые поля заказа (CabinetOrders). */
    private const ORDER_FIELDS = [
        ['UF_TRACK_NUMBER', 'string', 'Трек-номер отправления'],
        ['UF_TRACK_CARRIER', 'string', 'Транспортная компания'],
        ['UF_SHIPPED_AT', 'datetime', 'Поставщик указал трек-номер'],
        ['UF_PAYOUT_STATUS', 'string', 'Выплата поставщику: пусто — нет, due — к выплате, paid — выплачено'],
        ['UF_PAYOUT_AT', 'datetime', 'Выплачено поставщику'],
        ['UF_PAYOUT_AMOUNT', 'double', 'Сумма выплаты поставщику'],
        ['UF_PAYOUT_COMMENT', 'string', 'Комментарий к выплате (п/п)'],
    ];

    /**
     * Выплаты по ТЗ «Финансы» (PayoutService): оплата заказа зачисляет
     * сумму поставщика на его баланс (операция income с UF_ORDER_ID),
     * поставщик указывает трек-номер — заказ «к выплате сегодня»,
     * администратор отмечает «Выплачено» (операция payout). Заявок на вывод
     * больше нет: висящие отклоняются, история остаётся.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $ordersId = $helper->Hlblock()->getHlblockIdIfExists('CabinetOrders');
        $fields = [];
        foreach (self::ORDER_FIELDS as $i => [$name, $type, $label]) {
            $fields[] = $this->field($name, $type, $label, 400 + $i * 10);
        }
        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('HLBLOCK_' . $ordersId, $fields);

        $txId = $helper->Hlblock()->getHlblockIdIfExists('CabinetTransactions');
        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('HLBLOCK_' . $txId, [
            $this->field('UF_ORDER_ID', 'integer', 'Заказ', 200),
        ]);

        \Bitrix\Main\Loader::includeModule('formaro.cabinet');
        $tx = \Formaro\Cabinet\Repository\HlblockEntityFactory::getDataClass('CabinetTransactions');
        $orders = \Formaro\Cabinet\Repository\HlblockEntityFactory::getDataClass('CabinetOrders');

        $rejected = 0;
        $res = $tx::getList(['filter' => ['=UF_TYPE' => 'withdrawal', '=UF_STATUS' => 'pending']]);
        while ($row = $res->fetch()) {
            $tx::update($row['ID'], ['UF_STATUS' => 'rejected', 'UF_DESCRIPTION' => 'Заявка отменена: выплаты теперь по трек-номеру в заказе']);
            $rejected++;
        }

        // Старые поступления «Оплата заказа F-…» — привязать к заказу.
        $linked = 0;
        $res = $tx::getList(['filter' => ['=UF_TYPE' => 'income', 'UF_ORDER_ID' => false]]);
        while ($row = $res->fetch()) {
            if (!preg_match('/(F-\d+)/', (string)$row['UF_DESCRIPTION'], $m)) {
                continue;
            }
            $order = $orders::getList(['filter' => ['=UF_ORDER_NUMBER' => $m[1], '=UF_PARTNER_ID' => $row['UF_PARTNER_ID']], 'select' => ['ID'], 'limit' => 1])->fetch();
            if ($order) {
                $tx::update($row['ID'], ['UF_ORDER_ID' => (int)$order['ID']]);
                $linked++;
            }
        }

        $this->outSuccess('Поля выплат готовы; отменено заявок на вывод: ' . $rejected . ', привязано поступлений к заказам: ' . $linked);
    }

    /** @throws HelperException */
    public function down()
    {
        $helper = $this->getHelperManager();
        $ordersId = $helper->Hlblock()->getHlblockIdIfExists('CabinetOrders');
        $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists('HLBLOCK_' . $ordersId, array_column(self::ORDER_FIELDS, 0));
        $txId = $helper->Hlblock()->getHlblockIdIfExists('CabinetTransactions');
        $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists('HLBLOCK_' . $txId, ['UF_ORDER_ID']);
        $this->outInfo('Отменённые заявки на вывод не восстанавливаются');
    }

    private function field(string $name, string $type, string $label, int $sort): array
    {
        return [
            'FIELD_NAME' => $name,
            'USER_TYPE_ID' => $type,
            'MANDATORY' => 'N',
            'SORT' => $sort,
            'SHOW_FILTER' => 'I',
            'EDIT_FORM_LABEL' => ['ru' => $label],
            'LIST_COLUMN_LABEL' => ['ru' => $label],
            'LIST_FILTER_LABEL' => ['ru' => $label],
        ];
    }
}
