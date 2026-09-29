<?php

namespace Formaro\Cabinet\Service;

use Formaro\Cabinet\Repository\DiscountRepository;
use Formaro\Cabinet\Repository\ProductRepository;

/**
 * Подборки витринных каруселей (formaro:product.carousel и её подгрузка
 * по слайду «+» — /local/ajax/product_carousel.php):
 *  - DISCOUNT — "Выгодные предложения": товары, на которые прямо сейчас
 *    действует скидка партнёра (метки/цену считает ProductPricingService),
 *    по убыванию размера скидки;
 *  - NEW — "Новые поступления": последние добавленные (DATE_CREATE).
 *
 * Возвращает строки ProductCardService::toItem() и признак, есть ли ещё.
 */
class ProductCarouselService
{
    public const MODE_DISCOUNT = 'DISCOUNT';
    public const MODE_NEW = 'NEW';

    private const MAX_DISCOUNT_CANDIDATES = 300;

    public static function normalizeMode($mode): string
    {
        return strtoupper((string)$mode) === self::MODE_DISCOUNT ? self::MODE_DISCOUNT : self::MODE_NEW;
    }

    /** @return array{items: array, hasMore: bool} */
    public static function load(string $mode, int $partnerId, int $offset, int $limit): array
    {
        $offset = max(0, $offset);
        $limit = max(1, $limit);

        $activeDiscounts = (new DiscountRepository())->listActive();
        if ($partnerId) {
            // Страница партнёра: только его товары и его скидки.
            $activeDiscounts = array_values(array_filter(
                $activeDiscounts,
                static fn(array $d) => (int)$d['partner_id'] === $partnerId
            ));
        }
        $partnerFilter = $partnerId ? ['PROPERTY_PARTNER_ID' => $partnerId] : [];

        $items = self::normalizeMode($mode) === self::MODE_DISCOUNT
            ? self::loadDiscounted($activeDiscounts, $partnerFilter)
            : self::loadNew($activeDiscounts, $partnerFilter, $offset + $limit + 1);

        return [
            'items' => array_slice($items, $offset, $limit),
            'hasMore' => count($items) > $offset + $limit,
        ];
    }

    /** Первые $top новинок — на одну больше страницы, чтобы узнать, есть ли ещё. */
    private static function loadNew(array $activeDiscounts, array $partnerFilter, int $top): array
    {
        $products = (new ProductRepository())->findPublic($partnerFilter, ['DATE_CREATE' => 'DESC', 'ID' => 'DESC'], $top);

        return array_map(
            static fn(array $p) => ProductCardService::toItem($p, ProductPricingService::computeDisplay($p, $activeDiscounts)),
            $products
        );
    }

    /** Все товары со скидкой, отсортированные — страницу режет load(). */
    private static function loadDiscounted(array $activeDiscounts, array $partnerFilter): array
    {
        // Сужаем выборку до товаров, которых касается хоть одна активная
        // скидка (по её таргету); окончательно применимость (min_qty,
        // min_amount, партнёр) проверяет ProductPricingService.
        $or = ['LOGIC' => 'OR'];
        foreach ($activeDiscounts as $d) {
            $ids = array_values(array_filter(array_map('intval', $d['target_ids'])));
            switch ($d['target_type']) {
                case 'all':
                    $or[] = ['PROPERTY_PARTNER_ID' => (int)$d['partner_id']];
                    break;
                case 'products':
                    if ($ids) {
                        $or[] = ['ID' => $ids];
                    }
                    break;
                case 'category':
                    if ($ids) {
                        $or[] = ['SECTION_ID' => $ids];
                    }
                    break;
            }
        }
        if (count($or) === 1) {
            return [];
        }

        $candidates = (new ProductRepository())->findPublic(array_merge([$or], $partnerFilter), ['ID' => 'DESC'], self::MAX_DISCOUNT_CANDIDATES);

        $items = [];
        foreach ($candidates as $p) {
            $display = ProductPricingService::computeDisplay($p, $activeDiscounts);
            if ($display['old_price'] === null) {
                continue;
            }
            $item = ProductCardService::toItem($p, $display);
            $item['DISCOUNT_RATIO'] = 1 - $display['price'] / $display['old_price'];
            $items[] = $item;
        }

        usort($items, static fn($a, $b) => $b['DISCOUNT_RATIO'] <=> $a['DISCOUNT_RATIO'] ?: $b['ID'] <=> $a['ID']);

        return $items;
    }
}
