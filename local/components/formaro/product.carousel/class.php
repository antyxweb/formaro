<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Formaro\Cabinet\Service\ProductCarouselService;

/**
 * Витринная карусель товаров cabinet_catalog (главная, страница партнёра).
 *
 * MODE — подборка, см. ProductCarouselService: DISCOUNT ("Выгодные
 * предложения") или NEW ("Новые поступления"). Сервер отдаёт первые COUNT
 * товаров; если есть ещё — последний слайд «+» подгружает следующие по
 * COUNT через /local/ajax/product_carousel.php (script.js шаблона).
 */
class FormaroProductCarouselComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($params)
    {
        $params['MODE'] = ProductCarouselService::normalizeMode($params['MODE'] ?? 'NEW');
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

            $page = ProductCarouselService::load($this->arParams['MODE'], $this->arParams['PARTNER_ID'], 0, $this->arParams['COUNT']);
            $this->arResult['ITEMS'] = $page['items'];
            $this->arResult['HAS_MORE'] = $page['hasMore'];

            if (!$page['items']) {
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
}
