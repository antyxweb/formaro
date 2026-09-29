<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Formaro\Cabinet\Repository\ProductRepository;
use Formaro\Cabinet\Service\CatalogFilterService;

/**
 * Поиск по каталогу на главной: блок #hero-search (строка поиска, сетка
 * товаров #catalog-grid / мобильная карусель #catalog-search) и попап
 * фильтра #filter-popup.
 *
 * Сервер отдаёт только данные для фильтра (одобренные категории, диапазон
 * цен, цвета/размеры). Сами товары подгружает script.js шаблона через
 * /local/ajax/catalog_grid.php с учётом поиска и фильтра.
 */
class FormaroCatalogSearchComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($params)
    {
        // После этого метода Битрикс экранирует параметры (исходные значения
        // остаются в "~КЛЮЧ") — шаблон берёт "~"-версии и экранирует сам.
        $params['TITLE_HTML'] = (string)($params['TITLE_HTML'] ?? '');
        $params['BUTTON_TEXT'] = (string)($params['BUTTON_TEXT'] ?? '');
        $params['BUTTON_URL'] = (string)($params['BUTTON_URL'] ?? '');
        $params['POPULAR_QUERIES'] = array_values(array_filter(array_map('trim', (array)($params['POPULAR_QUERIES'] ?? []))));
        $params['PAGE_SIZE'] = max(1, min(48, (int)($params['PAGE_SIZE'] ?? 24)));
        // Страница партнёра: сетка, подсказки и фильтр — только его товары.
        $params['PARTNER_ID'] = max(0, (int)($params['PARTNER_ID'] ?? 0));
        $params['SECTION_CLASS'] = (string)($params['SECTION_CLASS'] ?? 'pt-5');
        $params['CACHE_TIME'] = isset($params['CACHE_TIME']) ? (int)$params['CACHE_TIME'] : 3600;

        return $params;
    }

    public function executeComponent()
    {
        if ($this->startResultCache()) {
            if (!Loader::includeModule('formaro.cabinet') || !Loader::includeModule('iblock')) {
                $this->abortResultCache();
                ShowError('formaro.cabinet module not found');
                return;
            }

            $iblockId = CatalogFilterService::getCatalogIblockId();
            $repo = new ProductRepository();
            $partnerId = $this->arParams['PARTNER_ID'];
            $productFilter = $partnerId ? ['PROPERTY_PARTNER_ID' => $partnerId] : [];
            $range = $repo->getPublicPriceRange($productFilter);

            $this->arResult['SECTION_GROUPS'] = CatalogFilterService::getSectionGroups($partnerId);
            $this->arResult['PRICE_MIN'] = (int)floor($range['min']);
            $this->arResult['PRICE_MAX'] = (int)ceil($range['max']);
            $this->arResult['COLORS'] = $repo->getPublicPropertyValues('COLOR', $productFilter);
            $this->arResult['SIZES'] = $repo->getPublicPropertyValues('SIZE', $productFilter);

            if ($iblockId && defined('BX_COMP_MANAGED_CACHE')) {
                global $CACHE_MANAGER;
                $CACHE_MANAGER->RegisterTag('iblock_id_' . $iblockId);
            }

            $this->includeComponentTemplate();
        }
    }
}
