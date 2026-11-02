<?php

namespace Sprint\Migration;

use Bitrix\Main\Config\Option;

class Version20261102100003 extends Version
{
    protected $description = 'Минимальная сумма заказа — 30 000 ₽ (правки клиента 19.10)';

    public function up()
    {
        // Ключ — MarkupService::MIN_ORDER_OPTION; в админке — «Финансы» → наценка
        Option::set('formaro.cabinet', 'min_order_amount', '30000');
        $this->outSuccess('Минимальный заказ: 30 000');
    }

    public function down()
    {
        Option::set('formaro.cabinet', 'min_order_amount', '10000');
    }
}
