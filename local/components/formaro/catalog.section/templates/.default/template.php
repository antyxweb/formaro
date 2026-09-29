<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arParams */
/** @var array $arResult */

use Formaro\Cabinet\Service\CatalogFilterUrl;

// Вёрстка — /html/section.html. Выбранный фильтр — в ЧПУ (class.php,
// CatalogFilterUrl): адрес при «Применить» собирает script.js из data-slug
// отмеченных чекбоксов; без JS форма уходит GET-запросом, и компонент
// перенаправляет на ЧПУ. «Показать еще» — тоже script.js.

$visibleItems = 8;

/** Адрес категории с текущим фильтром; $query — sort, page. */
$pageUrl = static function (array $query = []) use ($arResult): string {
    $query += ['sort' => $arResult['SORT'] !== 'popular' ? $arResult['SORT'] : ''];
    if ((int)($query['page'] ?? 1) <= 1) {
        unset($query['page']);
    }

    return htmlspecialcharsbx(CatalogFilterUrl::build($arResult['BASE_URL'], $arResult['URL_FILTER'], $query));
};

$checkbox = static function (string $name, string $value, string $label, bool $checked, bool $hidden, string $slug, bool $isDefault = false): string {
    return '<label class="' . ($hidden ? 'collapse-list d-none ' : '') . 'px-3 px-lg-4 py-2 mb-0">'
        . '<input type="checkbox" name="' . $name . '[]" value="' . htmlspecialcharsbx($value) . '"'
        . ' data-slug="' . htmlspecialcharsbx($slug) . '"' . ($isDefault ? ' data-default="Y"' : '') . ($checked ? ' checked' : '') . '>'
        . '<svg width="20" height="20"><use xlink:href="#icon-checkbox-tick"></use></svg>'
        . '<span>' . htmlspecialcharsbx($label) . '</span>'
        . '</label>';
};

/** Группа чекбоксов; свёрнута, если в ней ничего не отмечено. */
$filterGroup = static function (string $title, string $name, array $options) use ($checkbox, $visibleItems): string {
    $anyChecked = (bool)array_filter(array_column($options, 'CHECKED'));
    $html = '<div class="filter-row' . ($anyChecked ? '' : ' collapse-row') . '">'
        . '<div class="filter-row-title d-flex align-items-center py-2 py-lg-3 px-3 px-lg-4">'
        . '<h6 class="mr-auto mb-0">' . htmlspecialcharsbx($title) . '</h6>'
        . '<svg width="20" height="20"><use xlink:href="#icon-arrow-up"></use></svg>'
        . '</div>'
        . '<div class="filter-row-list py-2">';
    foreach (array_values($options) as $i => $option) {
        $html .= $checkbox($name, $option['VALUE'], $option['VALUE'], $option['CHECKED'], $i >= $visibleItems && !$option['CHECKED'], $option['SLUG']);
    }
    if (count($options) > $visibleItems) {
        $html .= '<a href="javascript:void(0);" class="d-block px-3 px-lg-4 py-2"><span>Показать все</span><span class="d-none">Свернуть</span></a>';
    }

    return $html . '</div></div>';
};

$group = $arResult['GROUP'];
$page = (int)$arResult['PAGE'];
$pageCount = (int)$arResult['PAGE_COUNT'];
$hasMore = $page < $pageCount;
?>
<section class="section pt-4" id="catalog-section"
         data-page="<?= $page ?>"
         data-page-size="<?= (int)$arResult['PAGE_SIZE'] ?>"
         data-total="<?= (int)$arResult['TOTAL'] ?>"
         data-query="<?= htmlspecialcharsbx(json_encode($arResult['GRID_QUERY'], JSON_UNESCAPED_UNICODE)) ?>">
    <div class="section-body">
        <div class="container-fluid">
            <div class="row">
                <div class="filter-wrap col-12 col-xl-3 d-none d-xl-block">
                    <form id="filter" method="get" action="<?= htmlspecialcharsbx($arResult['BASE_URL']) ?>" data-base-url="<?= htmlspecialcharsbx($arResult['BASE_URL']) ?>"
                          data-price-min="<?= (int)$arResult['PRICE_MIN'] ?>" data-price-max="<?= (int)$arResult['PRICE_MAX'] ?>">
                        <?php if ($arResult['SORT'] !== 'popular'): ?>
                        <input type="hidden" name="sort" value="<?= htmlspecialcharsbx($arResult['SORT']) ?>">
                        <?php endif; ?>
                        <div class="filter bg-white">
                            <div class="catalog-options mb-1 d-flex align-items-center">
                                <button type="button" class="f-button f-dropdown bg-white text-decoration-none">
                                    <svg width="16" height="16">
                                        <use xlink:href="#icon-filter-black"></use>
                                    </svg>
                                    <span class="pl-2 d-none d-md-inline-block">Фильтры</span>
                                </button>
                            </div>

                            <?php if ($group['ITEMS']): ?>
                            <div class="filter-row">
                                <div class="filter-row-title d-flex align-items-center py-2 py-lg-3 px-3 px-lg-4">
                                    <h6 class="mr-auto mb-0"><?= htmlspecialcharsbx($group['TITLE']) ?></h6>
                                    <svg width="20" height="20">
                                        <use xlink:href="#icon-arrow-up"></use>
                                    </svg>
                                </div>
                                <div class="filter-row-list py-2">
                                    <?php foreach ($group['ITEMS'] as $i => $item): ?>
                                    <?= $checkbox('sections', (string)$item['ID'], $item['NAME'], $item['CHECKED'], $i >= $visibleItems && !$item['CHECKED'], $item['CODE'], in_array($item['ID'], $group['DEFAULT'], true)) ?>
                                    <?php endforeach; ?>
                                    <?php if (count($group['ITEMS']) > $visibleItems): ?>
                                    <a href="javascript:void(0);" class="d-block px-3 px-lg-4 py-2"><span>Показать все</span><span class="d-none">Свернуть</span></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="filter-row">
                                <div class="filter-row-title d-flex align-items-center py-2 py-lg-3 px-3 px-lg-4">
                                    <h6 class="mr-auto mb-0">Цена, руб.</h6>
                                    <svg width="20" height="20">
                                        <use xlink:href="#icon-arrow-up"></use>
                                    </svg>
                                </div>
                                <div class="filter-row-list filter-row-price">
                                    <div class="filter-row-price-inputs d-flex align-items-center justify-content-between py-2 py-lg-3 px-3 px-lg-4">
                                        <input type="text" class="filterPriceMin" name="price_min" value="<?= (int)$arResult['PRICE_FROM'] ?>" placeholder="<?= (int)$arResult['PRICE_MIN'] ?>">
                                        <span class="text-secondary">&mdash;</span>
                                        <input type="text" class="filterPriceMax" name="price_max" value="<?= (int)$arResult['PRICE_TO'] ?>" placeholder="<?= (int)$arResult['PRICE_MAX'] ?>">
                                    </div>
                                    <div class="mx-2 mx-lg-3 d-flex justify-content-center mb-3">
                                        <input id="filterPrice" type="text" value=""
                                               data-slider-min="<?= (int)$arResult['PRICE_MIN'] ?>" data-slider-max="<?= (int)$arResult['PRICE_MAX'] ?>" data-slider-step="1"
                                               data-slider-value="[<?= (int)$arResult['PRICE_FROM'] ?>,<?= (int)$arResult['PRICE_TO'] ?>]" data-slider-tooltip="hide"/>
                                    </div>
                                </div>
                            </div>

                            <?php if ($arResult['COLORS']): ?>
                            <?= $filterGroup('Цвет', 'colors', $arResult['COLORS']) ?>
                            <?php endif; ?>

                            <?php if ($arResult['SIZES']): ?>
                            <?= $filterGroup('Размеры', 'sizes', $arResult['SIZES']) ?>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex">
                            <a href="<?= htmlspecialcharsbx($arResult['BASE_URL']) ?>" class="f-button c-white text-secondary f-clear mr-0 px-2 px-lg-4 w-50">
                                <svg width="16" height="16">
                                    <use xlink:href="#icon-close"></use>
                                </svg>
                                <span class="pl-2">Сбросить</span>
                            </a>
                            <button type="submit" class="f-button c-success px-2 px-lg-4 w-50" style="margin-right: 15px">
                                <svg width="16" height="16" class="d-none d-sm-inline d-md-none">
                                    <use xlink:href="#icon-arrow-control"></use>
                                </svg>
                                <span class="pl-2">Применить</span>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="col-12 col-xl-9 catalog-section">
                    <div class="catalog-options mb-3 mb-lg-4 d-flex align-items-center">
                        <span class="mr-auto text-dark">Всего: <?= (int)$arResult['TOTAL'] ?></span>

                        <button type="button" class="f-button f-dropdown text-decoration-none catalog-sort">
                            <svg width="16" height="16">
                                <use xlink:href="#icon-sort-black"></use>
                            </svg>
                            <span class="pl-2 d-none d-sm-inline-block"><?= htmlspecialcharsbx($arResult['SORTS'][$arResult['SORT']]) ?></span>

                            <ul class="f-dropdown-list">
                                <?php foreach ($arResult['SORTS'] as $code => $label): ?>
                                <li><a class="dropdown-item small<?= $code === $arResult['SORT'] ? ' active' : '' ?>" href="<?= $pageUrl(['sort' => $code !== 'popular' ? $code : '']) ?>"><?= htmlspecialcharsbx($label) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </button>

                        <button type="button" class="f-button f-dropdown bg-white text-decoration-none d-xl-none" data-fancybox data-src="#filter">
                            <svg width="16" height="16">
                                <use xlink:href="#icon-filter-black"></use>
                            </svg>
                            <span class="pl-2">Фильтры</span>
                        </button>
                    </div>

                    <div id="catalog-grid" class="row product-list d-flex flex-wrap">
                        <?php foreach ($arResult['ITEMS'] as $item): ?>
                        <div class="product-item">
                            <?php include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/product_card.php'; ?>
                        </div>
                        <?php endforeach; ?>

                        <?php if (!$arResult['ITEMS']): ?>
                        <div class="col-12 py-5 text-center text-secondary">
                            <p class="mb-4">По выбранным условиям ничего не найдено</p>
                            <a href="<?= htmlspecialcharsbx($arResult['BASE_URL']) ?>" class="f-button c-secondary">Сбросить фильтр</a>
                        </div>
                        <?php endif; ?>

                        <?php if ($pageCount > 1): ?>
                        <div class="pager">
                            <div class="d-flex justify-content-center pt-4<?= $hasMore ? '' : ' d-none' ?>" id="catalog-load-more-wrap">
                                <button type="button" class="f-button c-secondary" id="catalog-load-more">
                                    <svg width="16" height="16">
                                        <use xlink:href="#icon-grid"></use>
                                    </svg>
                                    <svg width="16" height="16" class="icon-progress d-none">
                                        <use xlink:href="#icon-progress"></use>
                                    </svg>
                                    <span class="pl-2">Показать еще</span>
                                </button>
                            </div>

                            <div class="d-flex justify-content-center pt-5">
                                <ul class="pagging__list">
                                    <?php if ($page > 1): ?>
                                    <li class="mr-4">
                                        <a href="<?= $pageUrl(['page' => $page - 1]) ?>" class="pagging__item bg-secondary text-white" aria-label="Предыдущая страница">
                                            <svg width="16" height="16"><use xlink:href="#icon-arrow-left"></use></svg>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php
                                    // 1 … (текущая ±2) … последняя
                                    $pages = array_unique(array_merge([1, $pageCount], range(max(1, $page - 2), min($pageCount, $page + 2))));
                                    sort($pages);
                                    $prev = 0;
                                    foreach ($pages as $n):
                                        if ($n - $prev > 1): ?>
                                    <li><span class="pagging__item">...</span></li>
                                        <?php endif;
                                        $prev = $n; ?>
                                    <?php if ($n === $page): ?>
                                    <li><span class="pagging__item"><?= $n ?></span></li>
                                    <?php else: ?>
                                    <li><a href="<?= $pageUrl(['page' => $n]) ?>" class="pagging__item"><?= $n ?></a></li>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                    <?php if ($page < $pageCount): ?>
                                    <li class="ml-4">
                                        <a href="<?= $pageUrl(['page' => $page + 1]) ?>" class="pagging__item bg-secondary text-white" aria-label="Следующая страница">
                                            <svg width="16" height="16"><use xlink:href="#icon-arrow-right"></use></svg>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
