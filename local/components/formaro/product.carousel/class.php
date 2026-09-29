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
 * предложения"), NEW ("Новые поступления") или VIEWED ("Просмотренные
 * товары" — список в браузере, карусель целиком строит script.js).
 * PARTNER_ID / SECTION_ID / EXCLUDE_ID — ограничения подборки (страница
 * партнёра, «Товары продавца», «Похожие товары», категория каталога).
 * Сервер отдаёт первые COUNT товаров; если есть ещё — последний слайд «+»
 * подгружает следующие по COUNT через /local/ajax/product_carousel.php.
 */
class FormaroProductCarouselComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($params)
    {
        // Модуль — уже здесь: у гостя его ещё никто не подключил (шапка
        // подключает только для вошедших), а класс нужен для MODE.
        $params['MODE'] = Loader::includeModule('formaro.cabinet')
            ? ProductCarouselService::normalizeMode($params['MODE'] ?? 'NEW')
            : 'NEW';
        $params['COUNT'] = max(1, (int)($params['COUNT'] ?? 12));
        $params['TITLE_ACCENT'] = (string)($params['TITLE_ACCENT'] ?? '');
        $params['TITLE'] = (string)($params['TITLE'] ?? '');
        $params['CATALOG_URL'] = (string)($params['CATALOG_URL'] ?? '/catalog/');
        $params['SECTION_CLASS'] = (string)($params['SECTION_CLASS'] ?? '');
        // Страница партнёра: только его товары и его скидки.
        $params['PARTNER_ID'] = max(0, (int)($params['PARTNER_ID'] ?? 0));
        $params['SECTION_ID'] = max(0, (int)($params['SECTION_ID'] ?? 0));
        $params['EXCLUDE_ID'] = max(0, (int)($params['EXCLUDE_ID'] ?? 0));
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

            // Просмотренные — только в браузере: сервер рисует пустую
            // скрытую секцию, товары подгружает script.js.
            $page = $this->arParams['MODE'] === ProductCarouselService::MODE_VIEWED
                ? ['items' => [], 'hasMore' => false]
                : ProductCarouselService::load($this->arParams['MODE'], $this->getScope(), 0, $this->arParams['COUNT']);
            $this->arResult['ITEMS'] = $page['items'];
            $this->arResult['HAS_MORE'] = $page['hasMore'];

            if (!$page['items'] && $this->arParams['MODE'] !== ProductCarouselService::MODE_VIEWED) {
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

    private function getScope(): array
    {
        return [
            'partner_id' => $this->arParams['PARTNER_ID'],
            'section_id' => $this->arParams['SECTION_ID'],
            'exclude_ids' => $this->arParams['EXCLUDE_ID'] ? [$this->arParams['EXCLUDE_ID']] : [],
        ];
    }
}
