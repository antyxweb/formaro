<?php

namespace Formaro\Cabinet\Service;

/**
 * Считает "витринные" метки и цену со скидкой для товара — общая логика
 * между публичной витриной (главная, #catalog-grid) и тем, что уже
 * умеет order-detail.js на клиенте (autoApplyBestDiscount/evalDiscountAmount) —
 * тот же принцип подбора "лучшей" скидки, только здесь для одной штуки
 * товара вне контекста заказа: скидка применима, если partner_id скидки
 * совпадает с partner_id товара (скидка одного партнёра не касается чужих
 * товаров), таргет совпадает (all/products/category) и условия
 * min_qty/min_amount выполняются уже для одной единицы (min_qty<=1,
 * min_amount<=цена) — иначе показывать сниженную цену для покупки одной
 * штуки было бы нечестно. В корзине условия проверяются для реального
 * количества ($qty): min_qty<=qty, min_amount<=цена×qty.
 */
class ProductPricingService
{
    /**
     * @param array $product строка ProductRepository::toArray()
     * @param array $activeDiscounts список DiscountRepository::listActive()
     * @param int $qty количество (корзина); цена — всё равно за штуку
     * @return array{badges: string[], price: float, old_price: ?float, discount_percent: ?int}
     */
    public static function computeDisplay(array $product, array $activeDiscounts, int $qty = 1): array
    {
        $badges = [];
        if (in_array('bestseller', $product['tags'] ?? [], true)) {
            $badges[] = ['class' => 'bg-warning', 'text' => 'Топ продаж'];
        }
        if (in_array('new', $product['tags'] ?? [], true)) {
            $badges[] = ['class' => 'bg-success', 'text' => 'Новинка'];
        }

        $price = (float)($product['price'] ?? 0);
        $best = self::findBestDiscount($product, $activeDiscounts, $price, max(1, $qty));

        $oldPrice = null;
        $discountPercent = null;
        if ($best !== null && $price > 0) {
            $discounted = self::applyDiscount($best, $price);
            if ($discounted < $price) {
                $oldPrice = $price;
                $price = $discounted;
                $discountPercent = (int)round((1 - $discounted / $oldPrice) * 100);
                if ($discountPercent > 0) {
                    $badges[] = ['class' => 'bg-danger', 'text' => '-' . $discountPercent . '%'];
                }
            }
        }

        return [
            'badges' => $badges,
            'price' => $price,
            'old_price' => $oldPrice,
        ];
    }

    private static function findBestDiscount(array $product, array $activeDiscounts, float $price, int $qty): ?array
    {
        $best = null;
        $bestAmount = 0.0;
        foreach ($activeDiscounts as $d) {
            if ((int)$d['partner_id'] !== (int)($product['partner_id'] ?? 0)) {
                continue;
            }
            if (!self::targetMatches($d, $product)) {
                continue;
            }
            if ($d['min_qty'] > $qty || $d['min_amount'] > $price * $qty) {
                continue; // условия на количество/сумму для этого количества не выполняются
            }

            $amount = $price - self::applyDiscount($d, $price);
            if ($amount > $bestAmount) {
                $bestAmount = $amount;
                $best = $d;
            }
        }

        return $best;
    }

    private static function targetMatches(array $discount, array $product): bool
    {
        return match ($discount['target_type']) {
            'all' => true,
            'products' => in_array((int)$product['id'], $discount['target_ids'], true),
            'category' => (bool)array_intersect($discount['target_ids'], $product['category_ids'] ?? []),
            default => false,
        };
    }

    private static function applyDiscount(array $discount, float $price): float
    {
        $discounted = $discount['discount_type'] === 'percent'
            ? $price * (1 - $discount['value'] / 100)
            : $price - $discount['value'];

        return max(0.0, round($discounted));
    }
}
