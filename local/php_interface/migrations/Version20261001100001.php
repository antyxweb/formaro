<?php

namespace Sprint\Migration;

use Bitrix\Highloadblock\HighloadBlockTable;
use Sprint\Migration\Exceptions\HelperException;

class Version20261001100001 extends Version
{
    protected $description = 'formaro.cabinet: ссылка на сущность (UF_LINK) в HL-блоке CabinetNotifications';

    /**
     * По клику на уведомление в кабинете партнёра — переход к тому, о чём
     * оно: UF_LINK — путь внутри кабинета (например, orders/edit/14/).
     * Уже созданным уведомлениям о заказах ссылка проставляется по номеру
     * заказа в заголовке (F-…).
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('CabinetNotifications');

        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('HLBLOCK_' . $hlblockId, [
            ['FIELD_NAME' => 'UF_LINK', 'USER_TYPE_ID' => 'string'],
        ]);

        $notifications = HighloadBlockTable::compileEntity(HighloadBlockTable::getById($hlblockId)->fetch())->getDataClass();
        $ordersId = $helper->Hlblock()->getHlblockIdIfExists('CabinetOrders');
        $orders = $ordersId ? HighloadBlockTable::compileEntity(HighloadBlockTable::getById($ordersId)->fetch())->getDataClass() : null;

        $linked = 0;
        if ($orders) {
            $rows = $notifications::getList(['filter' => ['=UF_TYPE' => 'order'], 'select' => ['ID', 'UF_TITLE', 'UF_PARTNER_ID', 'UF_LINK']])->fetchAll();
            foreach ($rows as $row) {
                if ($row['UF_LINK'] || !preg_match('/F-\d+/', (string)$row['UF_TITLE'], $m)) {
                    continue;
                }
                $order = $orders::getList([
                    'filter' => ['=UF_ORDER_NUMBER' => $m[0], '=UF_PARTNER_ID' => (int)$row['UF_PARTNER_ID']],
                    'select' => ['ID'],
                    'limit' => 1,
                ])->fetch();
                if ($order) {
                    $notifications::update((int)$row['ID'], ['UF_LINK' => 'orders/edit/' . (int)$order['ID'] . '/']);
                    $linked++;
                }
            }
        }

        $this->outSuccess('Поле UF_LINK в CabinetNotifications готово, ссылок на заказы проставлено: %d', $linked);
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('CabinetNotifications');
        if ($hlblockId) {
            $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists('HLBLOCK_' . $hlblockId, ['UF_LINK']);
            $this->outSuccess('Поле UF_LINK удалено из CabinetNotifications');
        }
    }
}
