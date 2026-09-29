<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Formaro\Cabinet\Repository\DiscountRepository;
use Formaro\Cabinet\Repository\ProductRepository;
use Formaro\Cabinet\Service\ProductCardService;
use Formaro\Cabinet\Service\ProductPricingService;

/**
 * Витринная карусель товаров cabinet_catalog для главной страницы.
 *
 * MODE:
 *  - DISCOUNT — "Выгодные предложения": товары, на которые прямо сейчас
 *    действует скидка партнёра (метки/цену считает ProductPricingService —
 *    та же логика, что у #catalog-grid), по убыванию размера скидки;
 *  - NEW — "Новые поступления": последние добавленные (DATE_CREATE).
 */
class FormaroProductCarouselComponent extends CBitrixComponent
{
    private const MAX_DISCOUNT_CANDIDATES = 300;

    public function onPrepareComponentParams($params)
    {
        $params['MODE'] = strtoupper((string)($params['MODE'] ?? 'NEW')) === 'DISCOUNT' ? 'DISCOUNT' : 'NEW';
        $params['COUNT'] = max(1, (int)($params['COUNT'] ?? 12));
        $params['TITLE_ACCENT'] = (string)($params['TITLE_ACCENT'] ?? '');
        $params['TITLE'] = (string)($params['TITLE'] ?? '');
        $params['CATALOG_URL'] = (string)($params['CATALOG_URL'] ?? '/catalog/');
        $params['SECTION_CLASS'] = (string)($params['SECTION_CLASS'] ?? '');
        // Страница партнёра: только его товары и его скидки.
        $params['PARTNER_ID'] = max(0, (int)($params['PARTNER_ID'] ?? 0));
        $params['CACHE_TIME'] = isset($params['CACHE_TIME']) ? (int)$params['CACHE_TIME'] : 600;

        return $params;
    }

    public function executeComponent()
    {
        // Скидки действуют по датам — день входит в ключ кэша, чтобы
        // закончившаяся/начавшаяся акция не висела в кэше до его истечения.
        if ($this->startResultCache(false, [date('Y-m-d')])) {
            if (!Loader::includeModule('formaro.cabinet')) {
                $this->abortResultCache();
                ShowError('formaro.cabinet module not found');
                return;
            }

            $productRepo = new ProductRepository();
            $activeDiscounts = (new DiscountRepository())->listActive();
            if ($this->arParams['PARTNER_ID']) {
                $activeDiscounts = array_values(array_filter(
                    $activeDiscounts,
                    fn(array $d) => (int)$d['partner_id'] === $this->arParams['PARTNER_ID']
                ));
            }

            $items = $this->arParams['MODE'] === 'DISCOUNT'
                ? $this->loadDiscounted($productRepo, $activeDiscounts)
                : $this->loadNew($productRepo, $activeDiscounts);

            $this->arResult['ITEMS'] = $items;

            if (!$items) {
                $this->abortResultCache();
            }

            if (defined('BX_COMP_MANAGED_CACHE')) {
                global $CACHE_MANAGER;
                $iblockId = (int)(CIBlock::GetList([], ['CODE' => 'cabinet_catalog', 'CHECK_PERMISSIONS' => 'N'])->Fetch()['ID'] ?? 0);
                if ($iblockId) {
                    $CACHE_MANAGER->RegisterTag('iblock_id_' . $iblockId);
                }
            }

            $this->includeComponentTemplate();
        }
    }

    private function loadNew(ProductRepository $repo, array $activeDiscounts): array
    {
        $products = $repo->findPublic($this->partnerFilter(), ['DATE_CREATE' => 'DESC', 'ID' => 'DESC'], $this->arParams['COUNT']);

        return array_map(fn(array $p) => ProductCardService::toItem($p, ProductPricingService::computeDisplay($p, $activeDiscounts)), $products);
    }

    private function loadDiscounted(ProductRepository $repo, array $activeDiscounts): array
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

        $candidates = $repo->findPublic(array_merge([$or], $this->partnerFilter()), ['ID' => 'DESC'], self::MAX_DISCOUNT_CANDIDATES);

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

        return array_slice($items, 0, $this->arParams['COUNT']);
    }

    private function partnerFilter(): array
    {
        return $this->arParams['PARTNER_ID'] ? ['PROPERTY_PARTNER_ID' => $this->arParams['PARTNER_ID']] : [];
    }
}
