<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Formaro\Cabinet\Repository\ProductRepository;
use Formaro\Cabinet\Service\CatalogFilterService;
use Formaro\Cabinet\Service\ProductCardService;

/**
 * Каталог новости на детальной странице (/news/…): категории и товары,
 * к которым партнёр привязал новость во вкладке «Каталог» кабинета
 * (свойства CATALOG_SECTIONS/CATALOG_PRODUCTS инфоблока cabinet_news).
 *
 * Категории группируются по корневой: привязана корневая — показываются
 * все её подкатегории с товарами; привязаны подкатегории — только они.
 * Выводятся только допущенные к показу (UF_APPROVED) категории и
 * активные товары.
 */
class FormaroNewsCatalogComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($params)
    {
        $toIds = static fn($v) => array_values(array_unique(array_filter(array_map('intval', (array)$v))));
        $params['SECTION_IDS'] = $toIds($params['SECTION_IDS'] ?? []);
        $params['PRODUCT_IDS'] = $toIds($params['PRODUCT_IDS'] ?? []);
        $params['PRODUCTS_TITLE'] = (string)($params['PRODUCTS_TITLE'] ?? 'Товары из новости');
        $params['CACHE_TIME'] = isset($params['CACHE_TIME']) ? (int)$params['CACHE_TIME'] : 3600;

        return $params;
    }

    public function executeComponent()
    {
        if (!$this->arParams['SECTION_IDS'] && !$this->arParams['PRODUCT_IDS']) {
            return;
        }

        // Цены со скидкой зависят от даты — день в ключе кэша.
        if ($this->startResultCache(false, [date('Y-m-d')])) {
            if (!Loader::includeModule('formaro.cabinet') || !Loader::includeModule('iblock')) {
                $this->abortResultCache();
                return;
            }

            $iblockId = CatalogFilterService::getCatalogIblockId();
            $this->arResult['CATEGORIES'] = $iblockId ? $this->loadCategories($iblockId, $this->arParams['SECTION_IDS']) : [];
            $this->arResult['PRODUCTS'] = $this->loadProducts($this->arParams['PRODUCT_IDS']);

            if ($iblockId && defined('BX_COMP_MANAGED_CACHE')) {
                global $CACHE_MANAGER;
                $CACHE_MANAGER->RegisterTag('iblock_id_' . $iblockId);
            }

            $this->includeComponentTemplate();
        }
    }

    private function loadProducts(array $ids): array
    {
        if (!$ids) {
            return [];
        }

        $products = (new ProductRepository())->findPublic(['ID' => $ids], ['ID' => 'ASC'], count($ids));
        // Порядок — как партнёр выбрал в кабинете.
        $position = array_flip($ids);
        usort($products, static fn($a, $b) => ($position[$a['id']] ?? 0) <=> ($position[$b['id']] ?? 0));

        return ProductCardService::build($products);
    }

    private function loadCategories(int $iblockId, array $ids): array
    {
        if (!$ids) {
            return [];
        }

        $sections = [];
        $res = CIBlockSection::GetList(
            ['LEFT_MARGIN' => 'ASC'],
            ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y', 'UF_APPROVED' => 1, 'CNT_ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
            true,
            ['ID', 'NAME', 'DEPTH_LEVEL', 'IBLOCK_SECTION_ID', 'PICTURE', 'SECTION_PAGE_URL']
        );
        while ($row = $res->GetNext()) {
            $sections[(int)$row['ID']] = $row;
        }

        $groups = [];
        $addChild = static function (array &$group, array $child) {
            if ((int)$child['ELEMENT_CNT'] > 0) {
                $group['CHILDREN'][(int)$child['ID']] = [
                    'NAME' => $child['~NAME'],
                    'URL' => $child['SECTION_PAGE_URL'],
                    'COUNT' => (int)$child['ELEMENT_CNT'],
                ];
            }
        };

        foreach ($ids as $id) {
            $section = $sections[$id] ?? null;
            if (!$section) {
                continue;
            }
            $rootId = (int)$section['DEPTH_LEVEL'] === 1 ? $id : (int)$section['IBLOCK_SECTION_ID'];
            $root = $sections[$rootId] ?? null;
            if (!$root) {
                continue;
            }

            if (!isset($groups[$rootId])) {
                $groups[$rootId] = [
                    'ID' => $rootId,
                    'NAME' => $root['~NAME'],
                    'URL' => $root['SECTION_PAGE_URL'],
                    'PICTURE' => $root['PICTURE'] ? CFile::GetPath($root['PICTURE']) : '',
                    'COUNT' => (int)$root['ELEMENT_CNT'],
                    'CHILDREN' => [],
                ];
            }

            if ($rootId === $id) {
                foreach ($sections as $child) {
                    if ((int)$child['IBLOCK_SECTION_ID'] === $rootId) {
                        $addChild($groups[$rootId], $child);
                    }
                }
            } else {
                $addChild($groups[$rootId], $section);
            }
        }

        // Категории без товаров не показываем, как в каталоге на главной.
        $groups = array_filter($groups, static fn($g) => $g['COUNT'] > 0);
        foreach ($groups as &$group) {
            $group['CHILDREN'] = array_values($group['CHILDREN']);
        }
        unset($group);

        return array_values($groups);
    }
}
