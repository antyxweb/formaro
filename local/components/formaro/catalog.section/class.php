<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Iblock\Component\Tools;
use Bitrix\Main\Loader;
use Formaro\Cabinet\Repository\ProductRepository;
use Formaro\Cabinet\Service\CatalogFilterService;
use Formaro\Cabinet\Service\CatalogFilterUrl;
use Formaro\Cabinet\Service\ProductCardService;

/**
 * Категория публичного каталога (cabinet_catalog): фильтр, сортировка,
 * постраничный список товаров. Вёрстка — /html/section.html.
 *
 * Выбранный фильтр — в ЧПУ (FILTER_PATH, формат — CatalogFilterUrl):
 * <категория>/filter/section-…/color-…/size-…/price-from-…-to-…/;
 * сортировка и страница — ?sort=…&page=…. Адрес собирает script.js при
 * «Применить». Неизвестное значение в адресе — 404; старые ссылки с
 * фильтром в query (?sections[]=…&colors[]=…) и неканоничный порядок
 * значений — 301 на канонический адрес.
 * «Показать еще» догружает следующую страницу с теми же условиями через
 * /local/ajax/catalog_grid.php.
 *
 * Группа категорий в фильтре: у категории с подкатегориями — её
 * подкатегории; у конечной — соседние (подкатегории родителя), текущая
 * отмечена. Цена/цвета/размеры — по товарам этой группы.
 */
class FormaroCatalogSectionComponent extends CBitrixComponent
{
    private const LEGACY_KEYS = ['sections', 'colors', 'sizes', 'price_min', 'price_max'];

    public function onPrepareComponentParams($params)
    {
        $params['SECTION_ID'] = max(0, (int)($params['SECTION_ID'] ?? 0));
        $params['FILTER_PATH'] = trim((string)($params['FILTER_PATH'] ?? ''), '/');
        $params['PAGE_SIZE'] = min(48, max(1, (int)($params['PAGE_SIZE'] ?? 24)));
        $params['CACHE_TIME'] = isset($params['CACHE_TIME']) ? (int)$params['CACHE_TIME'] : 3600;

        return $params;
    }

    public function executeComponent()
    {
        $request = $this->readRequest();

        // Скидки действуют по датам — день входит в ключ кэша. Шаблон — вне
        // кэша: сначала решаем, нужен ли редирект или 404.
        if ($this->startResultCache(false, [$request, date('Y-m-d')])) {
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

            $this->arResult = $this->buildResult($iblockId, $section, $request);

            if (defined('BX_COMP_MANAGED_CACHE')) {
                global $CACHE_MANAGER;
                $CACHE_MANAGER->RegisterTag('iblock_id_' . $iblockId);
            }
            $this->endResultCache();
        }

        if (!empty($this->arResult['NOT_FOUND'])) {
            Tools::process404('Страница не найдена', true, true, true);
            return;
        }
        if (!empty($this->arResult['REDIRECT_URL'])) {
            LocalRedirect($this->arResult['REDIRECT_URL'], false, '301 Moved Permanently');
            return;
        }
        if (!empty($this->arResult['SECTION'])) {
            $this->includeComponentTemplate();
        }
    }

    /** Всё, от чего зависит страница: фильтр из ЧПУ, старый фильтр из
     *  query (для редиректа), сортировка, страница. */
    private function readRequest(): array
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

        $legacy = [];
        foreach (self::LEGACY_KEYS as $key) {
            if ($this->request->getQuery($key) !== null) {
                $legacy = [
                    'sections' => array_values(array_filter(array_map('intval', $list('sections')))),
                    'colors' => $list('colors'),
                    'sizes' => $list('sizes'),
                    'price_from' => $number('price_min'),
                    'price_to' => $number('price_max'),
                ];
                break;
            }
        }

        $sort = (string)$this->request->getQuery('sort');
        $page = $this->request->getQuery('page');

        return [
            'filter_path' => $this->arParams['FILTER_PATH'],
            'legacy' => $legacy,
            'sort' => in_array($sort, ProductRepository::PUBLIC_SORTS, true) ? $sort : 'popular',
            'page' => max(1, (int)$page),
            // «?page=1», «?sort=popular», мусор в sort — неканонично.
            'query_canonical' => ($page === null || (int)$page > 1)
                && ($this->request->getQuery('sort') === null || in_array($sort, ProductRepository::PUBLIC_SORTS, true) && $sort !== 'popular'),
        ];
    }

    private function buildResult(int $iblockId, array $section, array $request): array
    {
        $sectionId = (int)$section['ID'];
        $parentId = (int)$section['IBLOCK_SECTION_ID'];
        $baseUrl = $section['SECTION_PAGE_URL'];

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

        // Значения фильтра — по товарам всей группы.
        $repo = new ProductRepository();
        $scopeFilter = ProductRepository::buildPublicFilter(['sections' => [$group['SCOPE_ID']]]);
        $range = $repo->getPublicPriceRange($scopeFilter);
        $priceMin = (int)floor($range['min']);
        $priceMax = max($priceMin + 1, (int)ceil($range['max']));
        $colors = $repo->getPublicPropertyValues('COLOR', $scopeFilter);
        sort($colors, SORT_STRING | SORT_FLAG_CASE);
        $sizes = CatalogFilterService::sortSizes($repo->getPublicPropertyValues('SIZE', $scopeFilter));

        // Выбранное: из ЧПУ или (старые ссылки) из query.
        $selected = $request['legacy']
            ? $this->selectFromLegacy($request['legacy'], $group, $colors, $sizes)
            : $this->selectFromPath($request['filter_path'], $group, $colors, $sizes);
        if ($selected === null) {
            return ['NOT_FOUND' => true];
        }
        // Цена на границе диапазона — не сужает, в адрес не пишем.
        if ($selected['price_from'] !== '' && (int)$selected['price_from'] <= $priceMin) {
            $selected['price_from'] = '';
        }
        if ($selected['price_to'] !== '' && (int)$selected['price_to'] >= $priceMax) {
            $selected['price_to'] = '';
        }

        $checked = $selected['sections'] ?: $group['DEFAULT'];
        foreach ($group['ITEMS'] as &$item) {
            $item['CHECKED'] = in_array($item['ID'], $checked, true);
        }
        unset($item);

        // Канонический адрес: отмеченная по умолчанию текущая категория
        // в адрес не пишется, значения — в порядке списка фильтра.
        $codes = array_column($group['ITEMS'], 'CODE', 'ID');
        $sectionsInUrl = $checked === $group['DEFAULT'] ? [] : $checked;
        $urlFilter = [
            'section' => array_map(static fn($id) => $codes[$id], $sectionsInUrl),
            'color' => array_values(array_unique(array_map([CatalogFilterUrl::class, 'slug'], $selected['colors']))),
            'size' => array_values(array_unique(array_map([CatalogFilterUrl::class, 'slug'], $selected['sizes']))),
            'price_from' => $selected['price_from'],
            'price_to' => $selected['price_to'],
        ];
        $sortQuery = $request['sort'] !== 'popular' ? $request['sort'] : '';
        $canonical = CatalogFilterUrl::build($baseUrl, $urlFilter, ['sort' => $sortQuery, 'page' => $request['page'] > 1 ? $request['page'] : '']);
        $current = CatalogFilterUrl::build($baseUrl, ['section' => [], 'color' => [], 'size' => []]) . ($request['filter_path'] !== '' ? 'filter/' . $request['filter_path'] . '/' : '');
        if ($request['legacy'] || !$request['query_canonical'] || strtok($canonical, '?') !== $current) {
            return ['REDIRECT_URL' => $canonical];
        }

        // Выборка.
        $filter = ProductRepository::buildPublicFilter([
            'sections' => $checked ?: [$sectionId],
            'price_min' => $selected['price_from'],
            'price_max' => $selected['price_to'],
            'colors' => $selected['colors'],
            'sizes' => $selected['sizes'],
        ]);
        $total = $repo->countPublic($filter);
        $pageSize = $this->arParams['PAGE_SIZE'];
        $pageCount = max(1, (int)ceil($total / $pageSize));
        $page = min($request['page'], $pageCount);
        $products = $total ? $repo->listPublic($pageSize, ($page - 1) * $pageSize, $filter, $request['sort']) : [];

        $clamp = static fn($v, $default) => $v !== '' ? max($priceMin, min($priceMax, (int)$v)) : $default;

        return [
            'SECTION' => ['ID' => $sectionId, 'NAME' => $section['NAME'], 'URL' => $baseUrl],
            'GROUP' => $group,
            'PRICE_MIN' => $priceMin,
            'PRICE_MAX' => $priceMax,
            'PRICE_FROM' => $clamp($selected['price_from'], $priceMin),
            'PRICE_TO' => $clamp($selected['price_to'], $priceMax),
            'COLORS' => array_map(static fn($c) => ['VALUE' => $c, 'SLUG' => CatalogFilterUrl::slug($c), 'CHECKED' => in_array($c, $selected['colors'], true)], $colors),
            'SIZES' => array_map(static fn($s) => ['VALUE' => $s, 'SLUG' => CatalogFilterUrl::slug($s), 'CHECKED' => in_array($s, $selected['sizes'], true)], $sizes),
            'ITEMS' => ProductCardService::build($products),
            'TOTAL' => $total,
            'PAGE' => $page,
            'PAGE_COUNT' => $pageCount,
            'PAGE_SIZE' => $pageSize,
            'SORT' => $request['sort'],
            'SORTS' => [
                'popular' => 'Популярные',
                'new' => 'Сначала новинки',
                'cheap' => 'Сначала дешевле',
                'expensive' => 'Сначала дороже',
            ],
            'BASE_URL' => $baseUrl,
            // Адрес с текущим фильтром — для постранички и сортировки.
            'URL_FILTER' => $urlFilter,
            // Параметры выборки для «Показать еще» (catalog_grid.php).
            'GRID_QUERY' => [
                'sections' => implode(',', $checked ?: [$sectionId]),
                'price_min' => $selected['price_from'],
                'price_max' => $selected['price_to'],
                'colors' => implode('|', $selected['colors']),
                'sizes' => implode('|', $selected['sizes']),
                'sort' => $request['sort'],
            ],
        ];
    }

    /** Фильтр из ЧПУ → id категорий и значения; null — неизвестное значение (404). */
    private function selectFromPath(string $path, array $group, array $colors, array $sizes): ?array
    {
        $parsed = $path !== '' ? CatalogFilterUrl::parse($path) : ['section' => [], 'color' => [], 'size' => [], 'price_from' => '', 'price_to' => ''];
        if ($parsed === null) {
            return null;
        }

        $sectionIds = array_column($group['ITEMS'], 'ID', 'CODE');
        $sections = [];
        foreach ($parsed['section'] as $code) {
            if (!isset($sectionIds[$code])) {
                return null;
            }
            $sections[] = $sectionIds[$code];
        }

        $bySlug = static function (array $slugs, array $values): ?array {
            $result = [];
            foreach ($slugs as $slug) {
                $matched = array_values(array_filter($values, static fn($v) => CatalogFilterUrl::slug($v) === $slug));
                if (!$matched) {
                    return null;
                }
                $result = array_merge($result, $matched);
            }

            return $result;
        };
        $selectedColors = $bySlug($parsed['color'], $colors);
        $selectedSizes = $bySlug($parsed['size'], $sizes);
        if ($selectedColors === null || $selectedSizes === null) {
            return null;
        }

        return [
            'sections' => $this->inListOrder($sections, array_column($group['ITEMS'], 'ID')),
            'colors' => $this->inListOrder($selectedColors, $colors),
            'sizes' => $this->inListOrder($selectedSizes, $sizes),
            'price_from' => $parsed['price_from'],
            'price_to' => $parsed['price_to'],
        ];
    }

    /** Старые ссылки (?sections[]=…&colors[]=…): берём только то, что есть в фильтре. */
    private function selectFromLegacy(array $legacy, array $group, array $colors, array $sizes): array
    {
        $ids = array_column($group['ITEMS'], 'ID');

        return [
            'sections' => $this->inListOrder(array_intersect($legacy['sections'], $ids), $ids),
            'colors' => $this->inListOrder(array_intersect($legacy['colors'], $colors), $colors),
            'sizes' => $this->inListOrder(array_intersect($legacy['sizes'], $sizes), $sizes),
            'price_from' => $legacy['price_from'],
            'price_to' => $legacy['price_to'],
        ];
    }

    /** $values без повторов в порядке списка $order. */
    private function inListOrder(array $values, array $order): array
    {
        return array_values(array_filter($order, static fn($v) => in_array($v, $values, true)));
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
            ['ID', 'NAME', 'CODE', 'SECTION_PAGE_URL']
        );
        while ($row = $res->GetNext()) {
            if ((int)$row['ELEMENT_CNT'] > 0 || (int)$row['ID'] === $keepId) {
                $items[] = ['ID' => (int)$row['ID'], 'NAME' => $row['NAME'], 'CODE' => $row['~CODE'], 'URL' => $row['SECTION_PAGE_URL'], 'COUNT' => (int)$row['ELEMENT_CNT']];
            }
        }

        return $items;
    }
}
