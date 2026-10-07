<?php

namespace Formaro\Cabinet\Service;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use CIBlock;
use CIBlockElement;

/**
 * Наценка площадки (ТЗ «Финансы»): цена на сайте = цена поставщика +
 * наценка, округлённая вверх до рубля. Поставщик видит только свою цену
 * (свойство PRICE), покупатель — цену сайта (свойство PRICE_RETAIL,
 * пересчитывается при сохранении товара и при смене ставки, по нему
 * сортировка и фильтр цен). Ставка — одна на все товары, по умолчанию и
 * не меньше MIN_RATE; меняется в кабинете администратора («Финансы»).
 *
 * Скидки и промокоды поставщика считаются от его цены: сумма скидки
 * одинакова для покупателя и поставщика — комиссия площадки не меняется
 * (ProductPricingService, CartCheckoutService).
 *
 * Там же — минимальная сумма заказа у поставщика (только предупреждение в
 * корзине, оформить можно и меньше).
 */
final class MarkupService
{
    public const MODULE_ID = 'formaro.cabinet';
    public const MIN_RATE = 7.0;
    public const DEFAULT_MIN_ORDER = 10000.0;
    private const RATE_OPTION = 'markup_percent';
    private const MIN_ORDER_OPTION = 'min_order_amount';

    public static function rate(): float
    {
        return max(self::MIN_RATE, (float)Option::get(self::MODULE_ID, self::RATE_OPTION, (string)self::MIN_RATE));
    }

    /** Цена на сайте для цены поставщика. */
    public static function retail(float $supplierPrice): float
    {
        if ($supplierPrice <= 0) {
            return 0.0;
        }

        // round(…, 2) до ceil — без ложного «+1 рубль» от погрешности float (1000 × 1.07 = 1070.0000000000002).
        return (float)ceil(round($supplierPrice * (1 + self::rate() / 100), 2));
    }

    public static function minOrder(): float
    {
        return max(0.0, (float)Option::get(self::MODULE_ID, self::MIN_ORDER_OPTION, (string)self::DEFAULT_MIN_ORDER));
    }

    /**
     * Новая ставка и минимальный заказ (кабинет администратора). Ставка
     * изменилась — цены сайта всех товаров пересчитываются.
     *
     * @return array{rate: float, min_order: float, recalculated: int}
     */
    public static function save(float $rate, float $minOrder): array
    {
        if ($rate < self::MIN_RATE) {
            throw new \RuntimeException('Наценка не может быть меньше ' . self::format(self::MIN_RATE) . '%');
        }
        if ($rate > 300) {
            throw new \RuntimeException('Проверьте наценку — больше 300%');
        }
        if ($minOrder < 0) {
            throw new \RuntimeException('Минимальный заказ не может быть отрицательным');
        }
        $changed = abs($rate - self::rate()) > 0.0001;
        Option::set(self::MODULE_ID, self::RATE_OPTION, (string)round($rate, 2));
        Option::set(self::MODULE_ID, self::MIN_ORDER_OPTION, (string)round($minOrder, 2));

        return ['rate' => self::rate(), 'min_order' => self::minOrder(), 'recalculated' => $changed ? self::recalculateAll() : 0];
    }

    /** Цены сайта всех товаров каталога по текущей ставке. @return int сколько товаров */
    public static function recalculateAll(): int
    {
        Loader::includeModule('iblock');
        $iblock = CIBlock::GetList([], ['=CODE' => 'cabinet_catalog', 'CHECK_PERMISSIONS' => 'N'])->Fetch();
        if (!$iblock) {
            return 0;
        }
        $iblockId = (int)$iblock['ID'];
        $count = 0;
        $res = CIBlockElement::GetList([], ['IBLOCK_ID' => $iblockId, 'CHECK_PERMISSIONS' => 'N'], false, false, ['ID', 'IBLOCK_ID', 'PROPERTY_PRICE']);
        while ($row = $res->Fetch()) {
            CIBlockElement::SetPropertyValuesEx((int)$row['ID'], $iblockId, ['PRICE_RETAIL' => self::retail((float)$row['PROPERTY_PRICE_VALUE'])]);
            $count++;
        }
        CIBlock::clearIblockTagCache($iblockId);

        return $count;
    }

    public static function format(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', ''), '0'), ',');
    }
}
