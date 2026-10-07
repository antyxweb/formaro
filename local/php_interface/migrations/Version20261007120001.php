<?php

namespace Sprint\Migration;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Formaro\Cabinet\Service\MarkupService;
use Sprint\Migration\Exceptions\HelperException;

class Version20261007120001 extends Version
{
    protected $description = 'Финансы, этап 2: наценка площадки 7% — свойство PRICE_RETAIL (цена на сайте) и пересчёт цен; минимальный заказ 10 000 ₽';

    /**
     * Цена на сайте = цена поставщика + наценка (MarkupService), округлена
     * вверх до рубля. Ставка и минимальный заказ — в настройках модуля
     * (меняются в кабинете администратора, «Финансы»).
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists('cabinet_catalog', 'catalog');
        $helper->Iblock()->saveProperty($iblockId, [
            'NAME' => 'Цена на сайте (с наценкой площадки)',
            'CODE' => 'PRICE_RETAIL',
            'PROPERTY_TYPE' => 'N',
            'SORT' => 205,
            'HINT' => 'Считается автоматически: цена поставщика + наценка площадки (настройки — «Финансы» в кабинете администратора)',
        ]);

        Loader::includeModule('formaro.cabinet');
        if (Option::get('formaro.cabinet', 'markup_percent', '') === '') {
            Option::set('formaro.cabinet', 'markup_percent', (string)MarkupService::MIN_RATE);
        }
        if (Option::get('formaro.cabinet', 'min_order_amount', '') === '') {
            Option::set('formaro.cabinet', 'min_order_amount', (string)MarkupService::DEFAULT_MIN_ORDER);
        }
        $count = MarkupService::recalculateAll();

        $this->outSuccess('Наценка %s%%, пересчитано товаров: %d', MarkupService::format(MarkupService::rate()), $count);
    }

    /** @throws HelperException */
    public function down()
    {
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists('cabinet_catalog', 'catalog');
        $helper->Iblock()->deletePropertyIfExists($iblockId, 'PRICE_RETAIL');
        $this->outSuccess('Свойство PRICE_RETAIL удалено');
    }
}
