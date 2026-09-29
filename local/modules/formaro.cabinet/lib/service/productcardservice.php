<?php

namespace Formaro\Cabinet\Service;

use Formaro\Cabinet\Repository\DiscountRepository;

/**
 * Данные карточки товара витрины для серверных шаблонов
 * (local/templates/formaro_v1/include/product_card.php): метки и цена со
 * скидкой — ProductPricingService.
 */
class ProductCardService
{
    /**
     * @param array $products строки ProductRepository::toArray()
     * @param array|null $activeDiscounts DiscountRepository::listActive(); null — загрузить
     */
    public static function build(array $products, ?array $activeDiscounts = null): array
    {
        $activeDiscounts = $activeDiscounts ?? (new DiscountRepository())->listActive();

        return array_map(
            static fn(array $p) => self::toItem($p, ProductPricingService::computeDisplay($p, $activeDiscounts)),
            $products
        );
    }

    /** @param array $display ProductPricingService::computeDisplay() */
    public static function toItem(array $p, array $display): array
    {
        return [
            'ID' => $p['id'],
            'NAME' => $p['name'],
            'SKU' => $p['sku'],
            'URL' => $p['public_url'] ?: '#',
            'IMAGE' => $p['preview_image'],
            'STOCK' => $p['stock'],
            'IS_PREORDER' => $p['is_preorder'],
            'PRICE' => $display['price'],
            'OLD_PRICE' => $display['old_price'],
            'BADGES' => $display['badges'],
        ];
    }
}
