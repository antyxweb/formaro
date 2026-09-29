<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Formaro\Cabinet\Repository\ProductRepository;
use Formaro\Cabinet\Service\CatalogFilterService;
use Formaro\Cabinet\Service\ProductCardService;

/**
 * Категория публичного каталога (cabinet_catalog): фильтр, сортировка,
 * постраничный список товаров. Вёрстка — /html/section.html.
 *
 * Фильтр — обычная GET-форма (адрес страницы можно сохранить и отправить):
 * sections[] — подкатегории, price_min/price_max, colors[], sizes[];
 * sort — ProductRepository::PUBLIC_SORTS; page — номер страницы.
 * «Показать еще» догружает следующую страницу с теми же параметрами через
 * /local/ajax/catalog_grid.php (script.js).
 *
 * Группа категорий в фильтре: у категории с подкатегориями — её
 * подкатегории; у конечной — соседние (подкатегории родителя), текущая
 * отмечена. Цена/цвета/размеры — по товарам этой группы.
 */
class FormaroCatalogSectionComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($params)
    {
        $params['SECTION_ID'] = max(0, (int)($params['SECTION_ID'] ?? 0));
        $params['PAGE_SIZE'] = min(48, max(1, (int)($params['PAGE_SIZE'] ?? 24)));
        $params['CACHE_TIME'] = isset($params['CACHE_TIME']) ? (int)$params['CACHE_TIME'] : 3600;

        return $params;
    }

    public function executeComponent()
    {
        $query = $this->readQuery();

        // Скидки действуют по датам — день входит в ключ кэша.
        if ($this->startResultCache(false, [$query, date('Y-m-d')])) {
            if (!Loader::includeModule('iblock') || !Loader::includeModule('formaro.cabinet')) {
                $this->abortResultCache();
                ShowError('formaro.cabinet module not found');
                return;
            }
            $iblockId = CatalogFilterService::getCatalogIblockId();
            $section = $this->arParams['SECTION_ID']
                ? CIBlockSection::GetList([], ['IBLOCK_ID' => $iblockId, 'ID' => $this->arParams['SECTION_ID'], 'CHECK_PERMISSIONS' => 'N'], false, ['ID', 'NAME', 'IBLOCK_SECTION_ID', 'SECTION_PAGE_URL'])->GetNext()
                : null;
            if (!$section) {
                $this->abortResultCache();
                return;
            }

            $this->arResult = $this->buildResult($iblockId, $section, $query);

            if (defined('BX_COMP_MANAGED_CACHE')) {
                global $CACHE_MANAGER;
                $CACHE_MANAGER->RegisterTag('iblock_id_' . $iblockId);
            }

            $this->includeComponentTemplate();
        }
    }

    /** Параметры фильтра из адреса, приведённые к нормальному виду. */
    private function readQuery(): array
    {
        $list = function (string $key): array {
            $values = $this->request->getQuery($key);
            $values = is_array($values) ? $values : [];

            return array_values(array_unique(array_filter(array_map(static fn($v) => trim((string)$v), $values), 'strlen')));
        };
        $number = function (string $key): string {
            $value = trim((string)$this->request->getQuery($key));

            return is_numeric($value) ? (string)(int)$value : '';
        };
        $sort = (string)$this->request->getQuery('sort');

        return [
            'sections' => array_values(array_filter(array_map('intval', $list('sections')))),
            'price_min' => $number('price_min'),
            'price_max' => $number('price_max'),
            'colors' => $list('colors'),
            'sizes' => $list('sizes'),
            'sort' => in_array($sort, ProductRepository::PUBLIC_SORTS, true) ? $sort : 'popular',
            'page' => max(1, (int)$this->request->getQuery('page')),
        ];
    }

    private function buildResult(int $iblockId, array $section, array $query): array
    {
        $sectionId = (int)$section['ID'];
        $parentId = (int)$section['IBLOCK_SECTION_ID'];

        // Группа категорий фильтра.
        $children = $this->listSubsections($iblockId, $sectionId);
        if ($children) {
            $group = ['TITLE' => $section['NAME'], 'SCOPE_ID' => $sectionId, 'ITEMS' => $children, 'DEFAULT' => []];
        } elseif ($parentId) {
            $parent = CIBlockSection::GetList([], ['IBLOCK_ID' => $iblockId, 'ID' => $parentId, 'CHECK_PERMISSIONS' => 'N'], false, ['ID', 'NAME'])->GetNext();
            $group = ['TITLE' => $parent['NAME'] ?? '', 'SCOPE_ID' => $parentId, 'ITEMS' => $this->listSubsections($iblockId, $parentId, $sectionId), 'DEFAULT' => [$sectionId]];
        } else {
            $group = ['TITLE' => '', 'SCOPE_ID' => $sectionId, 'ITEMS' => [], 'DEFAULT' => []];
        }

        $allowed = array_column($group['ITEMS'], 'ID');
        $checked = array_values(array_intersect($query['sections'], $allowed)) ?: $group['DEFAULT'];
        foreach ($group['ITEMS'] as &$item) {
            $item['CHECKED'] = in_array($item['ID'], $checked, true);
        }
        unset($item);

        // Границы и значения фильтра — по товарам всей группы.
        $repo = new ProductRepository();
        $scopeFilter = ProductRepository::buildPublicFilter(['sections' => [$group['SCOPE_ID']]]);
        $range = $repo->getPublicPriceRange($scopeFilter);
        $priceMin = (int)floor($range['min']);
        $priceMax = max($priceMin + 1, (int)ceil($range['max']));
        $colors = $repo->getPublicPropertyValues('COLOR', $scopeFilter);
        sort($colors, SORT_STRING | SORT_FLAG_CASE);
        $sizes = CatalogFilterService::sortSizes($repo->getPublicPropertyValues('SIZE', $scopeFilter));

        // Выборка.
        $filterParams = [
            'sections' => $checked ?: [$sectionId],
            'price_min' => $query['price_min'],
            'price_max' => $query['price_max'],
            'colors' => array_values(array_intersect($query['colors'], $colors)),
            'sizes' => array_values(array_intersect($query['sizes'], $sizes)),
        ];
        $filter = ProductRepository::buildPublicFilter($filterParams);
        $total = $repo->countPublic($filter);
        $pageSize = $this->arParams['PAGE_SIZE'];
        $pageCount = max(1, (int)ceil($total / $pageSize));
        $page = min($query['page'], $pageCount);
        $products = $total ? $repo->listPublic($pageSize, ($page - 1) * $pageSize, $filter, $query['sort']) : [];

        $baseUrl = $section['SECTION_PAGE_URL'];

        return [
            'SECTION' => ['ID' => $sectionId, 'NAME' => $section['NAME'], 'URL' => $baseUrl],
            'GROUP' => $group,
            'PRICE_MIN' => $priceMin,
            'PRICE_MAX' => $priceMax,
            'PRICE_FROM' => $query['price_min'] !== '' ? max($priceMin, min($priceMax, (int)$query['price_min'])) : $priceMin,
            'PRICE_TO' => $query['price_max'] !== '' ? max($priceMin, min($priceMax, (int)$query['price_max'])) : $priceMax,
            'COLORS' => array_map(static fn($c) => ['VALUE' => $c, 'CHECKED' => in_array($c, $filterParams['colors'], true)], $colors),
            'SIZES' => array_map(static fn($s) => ['VALUE' => $s, 'CHECKED' => in_array($s, $filterParams['sizes'], true)], $sizes),
            'ITEMS' => ProductCardService::build($products),
            'TOTAL' => $total,
            'PAGE' => $page,
            'PAGE_COUNT' => $pageCount,
            'PAGE_SIZE' => $pageSize,
            'SORT' => $query['sort'],
            'SORTS' => [
                'popular' => 'Популярные',
                'new' => 'Сначала новинки',
                'cheap' => 'Сначала дешевле',
                'expensive' => 'Сначала дороже',
            ],
            'BASE_URL' => $baseUrl,
            // Параметры выборки для «Показать еще» (catalog_grid.php).
            'GRID_QUERY' => [
                'sections' => implode(',', $filterParams['sections']),
                'price_min' => $filterParams['price_min'],
                'price_max' => $filterParams['price_max'],
                'colors' => implode('|', $filterParams['colors']),
                'sizes' => implode('|', $filterParams['sizes']),
                'sort' => $query['sort'],
            ],
            // Текущий адрес без page — для ссылок постранички и сортировки.
            'QUERY' => array_filter([
                'sections' => $query['sections'] ? $checked : [],
                'price_min' => $query['price_min'],
                'price_max' => $query['price_max'],
                'colors' => $filterParams['colors'],
                'sizes' => $filterParams['sizes'],
                'sort' => $query['sort'] !== 'popular' ? $query['sort'] : '',
            ], static fn($v) => $v !== '' && $v !== []),
        ];
    }

    /** Активные одобренные подкатегории, в которых есть активные товары
     *  (+ $keepId — текущая, даже если пуста). */
    private function listSubsections(int $iblockId, int $parentId, int $keepId = 0): array
    {
        $items = [];
        $res = CIBlockSection::GetList(
            ['SORT' => 'ASC', 'NAME' => 'ASC'],
            ['IBLOCK_ID' => $iblockId, 'SECTION_ID' => $parentId, 'GLOBAL_ACTIVE' => 'Y', 'UF_APPROVED' => 1, 'CNT_ACTIVE' => 'Y', 'ELEMENT_SUBSECTIONS' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
            true,
            ['ID', 'NAME', 'SECTION_PAGE_URL']
        );
        while ($row = $res->GetNext()) {
            if ((int)$row['ELEMENT_CNT'] > 0 || (int)$row['ID'] === $keepId) {
                $items[] = ['ID' => (int)$row['ID'], 'NAME' => $row['NAME'], 'URL' => $row['SECTION_PAGE_URL'], 'COUNT' => (int)$row['ELEMENT_CNT']];
            }
        }

        return $items;
    }
}
