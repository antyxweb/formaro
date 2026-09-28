<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Formaro\Cabinet\Service\CatalogFilterService;

/**
 * Избранные товары (/personal/favorites/).
 *
 * Список избранного у гостя живёт только в браузере (localStorage), поэтому
 * сервер рендерит каркас страницы и дерево категорий для фильтра (кэш),
 * а сами товары, счётчик «Всего», границы цены и видимые категории
 * подгружает script.js шаблона через /local/ajax/favorites_list.php по
 * списку из js/favorites.js.
 */
class FormaroFavoritesListComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($params)
    {
        $params['PAGE_SIZE'] = max(1, min(48, (int)($params['PAGE_SIZE'] ?? 24)));
        $params['CATALOG_URL'] = (string)($params['CATALOG_URL'] ?? '/catalog/');
        $params['CACHE_TIME'] = isset($params['CACHE_TIME']) ? (int)$params['CACHE_TIME'] : 3600;

        return $params;
    }

    public function executeComponent()
    {
        if ($this->startResultCache()) {
            if (!Loader::includeModule('formaro.cabinet')) {
                $this->abortResultCache();
                ShowError('formaro.cabinet module not found');
                return;
            }

            $this->arResult['SECTION_GROUPS'] = CatalogFilterService::getSectionGroups();

            $iblockId = CatalogFilterService::getCatalogIblockId();
            if ($iblockId && defined('BX_COMP_MANAGED_CACHE')) {
                global $CACHE_MANAGER;
                $CACHE_MANAGER->RegisterTag('iblock_id_' . $iblockId);
            }

            $this->includeComponentTemplate();
        }
    }
}
