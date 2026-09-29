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
 *  - NEW — "Новые поступления": последние добавленные (DATE_CREATE);
 *  - VIEWED — "Просмотренные товары": товары из $scope['ids'] в том же
 *    порядке (список ведёт браузер, см. formaro:catalog.element).
 *
 * $scope — чем ограничить подборку:
 *  - partner_id — страница партнёра / «Товары продавца»: только его
 *    товары и его скидки;
 *  - section_id — «Похожие товары», карусели на странице категории:
 *    товары категории с подкатегориями;
 *  - exclude_ids — не показывать (текущий товар на детальной);
 *  - ids — для VIEWED.
 *
 * Возвращает строки ProductCardService::toItem() и признак, есть ли ещё.
 */
class ProductCarouselService
{
    public const MODE_DISCOUNT = 'DISCOUNT';
    public const MODE_NEW = 'NEW';
    public const MODE_VIEWED = 'VIEWED';

    private const MAX_DISCOUNT_CANDIDATES = 300;

    public static function normalizeMode($mode): string
    {
        $mode = strtoupper((string)$mode);

        return in_array($mode, [self::MODE_DISCOUNT, self::MODE_VIEWED], true) ? $mode : self::MODE_NEW;
    }

    /** @return array{items: array, hasMore: bool} */
    public static function load(string $mode, array $scope, int $offset, int $limit): array
    {
        $offset = max(0, $offset);
        $limit = max(1, $limit);
        $partnerId = max(0, (int)($scope['partner_id'] ?? 0));
        $sectionId = max(0, (int)($scope['section_id'] ?? 0));
        $excludeIds = array_values(array_filter(array_map('intval', (array)($scope['exclude_ids'] ?? []))));

        $activeDiscounts = (new DiscountRepository())->listActive();
        if ($partnerId) {
            // Страница партнёра: только его товары и его скидки.
            $activeDiscounts = array_values(array_filter(
                $activeDiscounts,
                static fn(array $d) => (int)$d['partner_id'] === $partnerId
            ));
        }
        $scopeFilter = [];
        if ($partnerId) {
            $scopeFilter['PROPERTY_PARTNER_ID'] = $partnerId;
        }
        if ($sectionId) {
            $scopeFilter['SECTION_ID'] = $sectionId;
            $scopeFilter['INCLUDE_SUBSECTIONS'] = 'Y';
        }
        if ($excludeIds) {
            $scopeFilter['!ID'] = $excludeIds;
        }

        switch (self::normalizeMode($mode)) {
            case self::MODE_DISCOUNT:
                $items = self::loadDiscounted($activeDiscounts, $scopeFilter);
                break;
            case self::MODE_VIEWED:
                $items = self::loadByIds($activeDiscounts, $scopeFilter, (array)($scope['ids'] ?? []));
                break;
            default:
                $items = self::loadNew($activeDiscounts, $scopeFilter, $offset + $limit + 1);
        }

        return [
            'items' => array_slice($items, $offset, $limit),
            'hasMore' => count($items) > $offset + $limit,
        ];
    }

    /** Первые $top новинок — на одну больше страницы, чтобы узнать, есть ли ещё. */
    private static function loadNew(array $activeDiscounts, array $scopeFilter, int $top): array
    {
        $products = (new ProductRepository())->findPublic($scopeFilter, ['DATE_CREATE' => 'DESC', 'ID' => 'DESC'], $top);

        return array_map(
            static fn(array $p) => ProductCardService::toItem($p, ProductPricingService::computeDisplay($p, $activeDiscounts)),
            $products
        );
    }

    /** Товары по списку id в том же порядке (неактивные/удалённые — пропускаем). */
    private static function loadByIds(array $activeDiscounts, array $scopeFilter, array $ids): array
    {
        $ids = array_slice(array_values(array_unique(array_filter(array_map('intval', $ids)))), 0, 100);
        if (!$ids) {
            return [];
        }
        $products = (new ProductRepository())->findPublic(array_merge($scopeFilter, ['ID' => $ids]), ['ID' => 'DESC'], count($ids));
        $position = array_flip($ids);
        usort($products, static fn($a, $b) => $position[$a['id']] <=> $position[$b['id']]);

        return array_map(
            static fn(array $p) => ProductCardService::toItem($p, ProductPricingService::computeDisplay($p, $activeDiscounts)),
            $products
        );
    }

    /** Все товары со скидкой, отсортированные — страницу режет load(). */
    private static function loadDiscounted(array $activeDiscounts, array $scopeFilter): array
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

        $candidates = (new ProductRepository())->findPublic(array_merge([$or], $scopeFilter), ['ID' => 'DESC'], self::MAX_DISCOUNT_CANDIDATES);

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
