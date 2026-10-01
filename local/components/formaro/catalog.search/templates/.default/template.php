<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arParams */
/** @var array $arResult */

// Сколько пунктов в группе фильтра видно сразу — остальные под «Показать все».
$visibleItems = 8;

$checkboxRow = static function (string $name, string $value, string $label, bool $hidden): string {
    return '<label class="' . ($hidden ? 'collapse-list d-none ' : '') . 'px-3 px-lg-4 py-2 mb-0">'
        . '<input type="checkbox" name="' . htmlspecialcharsbx($name) . '" value="' . htmlspecialcharsbx($value) . '">'
        . '<svg width="20" height="20"><use xlink:href="#icon-checkbox-tick"></use></svg>'
        . '<span>' . htmlspecialcharsbx($label) . '</span>'
        . '</label>';
};

$filterGroup = static function (string $title, string $name, array $options, bool $collapsed) use ($checkboxRow, $visibleItems): string {
    $html = '<div class="filter-row' . ($collapsed ? ' collapse-row' : '') . '">'
        . '<div class="filter-row-title d-flex align-items-center py-2 py-lg-3 px-3 px-lg-4">'
        . '<h6 class="mr-auto mb-0">' . htmlspecialcharsbx($title) . '</h6>'
        . '<svg width="20" height="20"><use xlink:href="#icon-arrow-up"></use></svg>'
        . '</div>'
        . '<div class="filter-row-list py-2">';
    $i = 0;
    foreach ($options as $value => $label) {
        $html .= $checkboxRow($name, (string)$value, (string)$label, $i++ >= $visibleItems);
    }
    if (count($options) > $visibleItems) {
        $html .= '<a href="javascript:void(0);" class="d-block px-3 px-lg-4 py-2"><span>Показать все</span><span class="d-none">Свернуть</span></a>';
    }

    return $html . '</div></div>';
};

$searchIcon = '<svg width="16" height="16"><use xlink:href="#icon-search"></use></svg>';
$priceMin = (int)$arResult['PRICE_MIN'];
$priceMax = max($priceMin + 1, (int)$arResult['PRICE_MAX']);
?>
<!-- Каталог - Поиск -->
<section id="hero-search" class="section <?= htmlspecialcharsbx($arParams['SECTION_CLASS']) ?>" data-page-size="<?= (int)$arParams['PAGE_SIZE'] ?>" data-partner-id="<?= (int)$arParams['PARTNER_ID'] ?>" data-catalog-root="<?= htmlspecialcharsbx($arParams['PARTNER_ID'] ? \Formaro\Cabinet\Catalog\CatalogUrl::partnerRoot((int)$arParams['PARTNER_ID']) : '') ?>">
    <?php if ($arParams['TITLE_HTML'] !== '' || $arParams['BUTTON_TEXT'] !== ''): ?>
    <div class="section-header mb-4">
        <div class="container-fluid pt-5 d-md-flex align-items-center">
            <?php if ($arParams['TITLE_HTML'] !== ''): ?>
            <h1 class="h2 mb-4"><?= $arParams['~TITLE_HTML'] ?></h1>
            <?php endif; ?>
            <?php if ($arParams['BUTTON_TEXT'] !== ''): ?>
            <a href="<?= htmlspecialcharsbx($arParams['~BUTTON_URL']) ?>" class="f-button c-warning text-uppercase ml-auto mb-3"><?= htmlspecialcharsbx($arParams['~BUTTON_TEXT']) ?></a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <div class="section-body">
        <div class="container-fluid">
            <div class="hero-search-block mb-5">
                <form class="hero-search-group" id="hero-search-form" autocomplete="off">
                    <div class="hero-search-input">
                        <input type="text" id="hero-search-input" name="q" class="search-input" placeholder="Начните поиск здесь...">
                        <div class="search-result-block">
                            <div class="search-result-block-wrap p-3">
                                <div class="search-result-block-wrap-ajax d-none">
                                    <ul class="mb-0" id="hero-search-suggest"></ul>
                                </div>

                                <div class="search-result-block-wrap-offer">
                                    <div id="hero-search-history" class="d-none">
                                        <h6 class="h6 text-secondary">История поиска</h6>
                                        <ul></ul>
                                    </div>

                                    <?php if ($arParams['POPULAR_QUERIES']): ?>
                                    <h6 class="h6 text-secondary">Часто ищут</h6>
                                    <ul class="mb-0">
                                        <?php foreach ($arParams['~POPULAR_QUERIES'] as $query): ?>
                                        <li><?= $searchIcon ?><a href="#" data-search-query="<?= htmlspecialcharsbx($query) ?>"><?= htmlspecialcharsbx($query) ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="search-result-bg"></div>
                        <button type="button" class="hero-search-clear d-none" id="hero-search-clear" aria-label="Очистить поиск">
                            <svg width="16" height="16"><use xlink:href="#icon-close"></use></svg>
                        </button>
                        <svg class="hero-search-input-icon" width="24" height="24">
                            <use xlink:href="#icon-search"></use>
                        </svg>
                    </div>
                    <div class="hero-search-buttons">
                        <button type="button" class="f-button c-success f-clear mr-0 px-4" data-fancybox data-src="#filter-popup">
                            <svg width="16" height="16">
                                <use xlink:href="#icon-filter-white"></use>
                            </svg>
                            <span class="pl-2 text-uppercase d-sm-none d-md-inline">Фильтры</span>
                            <span class="badge badge-light ml-2 d-none" id="hero-filter-count"></span>
                        </button>
                        <button type="submit" class="f-button c-primary">
                            <svg width="16" height="16" class="d-none d-sm-inline d-md-none">
                                <use xlink:href="#icon-arrow-control"></use>
                            </svg>
                            <span class="pl-2 text-uppercase d-sm-none d-md-inline">Искать</span>
                        </button>
                    </div>
                </form>
            </div>

            <div class="d-none d-md-block">
                <div id="catalog-grid" class="row product-list d-flex flex-wrap">
                    <?php for ($i = 0; $i < 12; $i++): ?>
                    <div class="product-item product-skeleton"><?php include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/product_card_skeleton.php'; ?></div>
                    <?php endfor; ?>
                    <div class="col-12 py-5 text-center text-secondary d-none" id="catalogGridEmpty">
                        По вашему запросу ничего не найдено
                    </div>
                    <div class="pager" id="catalogGridPager">
                        <div class="d-flex justify-content-center pt-4">
                            <button type="button" class="f-button c-secondary" id="load-more">
                                <svg width="16" height="16">
                                    <use xlink:href="#icon-grid"></use>
                                </svg>
                                <svg width="16" height="16" class="icon-progress d-none">
                                    <use xlink:href="#icon-progress"></use>
                                </svg>
                                <span class="pl-2">Показать еще</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-md-none">
                <div id="catalog-search" class="main-carousel product-carousel">
                    <?php for ($i = 0; $i < 3; $i++): ?>
                    <div class="carousel-cell product-skeleton"><?php include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/product_card_skeleton.php'; ?></div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Каталог - Фильтр -->
<div id="filter-popup" style="display: none">
    <form id="filter" data-price-min="<?= $priceMin ?>" data-price-max="<?= $priceMax ?>">
        <div class="filter bg-white">
            <div class="catalog-options mb-1 d-flex align-items-center">
                <button type="button" class="f-button f-dropdown bg-white text-decoration-none">
                    <svg width="16" height="16">
                        <use xlink:href="#icon-filter-black"></use>
                    </svg>
                    <span class="pl-2 d-none d-md-inline-block">Фильтры</span>
                </button>
            </div>

            <?php if ($arResult['SECTION_GROUPS']): ?>
            <!-- Категории: корневые с чекбоксом, подкатегории раскрываются стрелкой -->
            <div class="filter-row">
                <div class="filter-row-title d-flex align-items-center py-2 py-lg-3 px-3 px-lg-4">
                    <h6 class="mr-auto mb-0">Категории</h6>
                    <svg width="20" height="20">
                        <use xlink:href="#icon-arrow-up"></use>
                    </svg>
                </div>
                <div class="filter-row-list filter-row-categories py-2">
                    <?php foreach ($arResult['SECTION_GROUPS'] as $index => $group): ?>
                    <div class="filter-cat<?= $index >= $visibleItems ? ' collapse-list d-none' : '' ?>">
                        <?= $checkboxRow('sections', (string)$group['ID'], $group['NAME'], false) ?>
                        <?php if ($group['ITEMS']): ?>
                        <button type="button" class="filter-cat-toggle" aria-label="Подкатегории">
                            <svg width="16" height="16"><use xlink:href="#icon-arrow-down"></use></svg>
                        </button>
                        <div class="filter-cat-children d-none">
                            <?php foreach ($group['ITEMS'] as $child): ?>
                            <?= $checkboxRow('sections', (string)$child['ID'], $child['NAME'], false) ?>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php if (count($arResult['SECTION_GROUPS']) > $visibleItems): ?>
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
                        <input type="text" class="filterPriceMin" name="price_min" value="<?= $priceMin ?>" placeholder="<?= $priceMin ?>">
                        <span class="text-secondary">&mdash;</span>
                        <input type="text" class="filterPriceMax" name="price_max" value="<?= $priceMax ?>" placeholder="<?= $priceMax ?>">
                    </div>
                    <div class="mx-2 mx-lg-3 d-flex justify-content-center mb-3">
                        <input id="filterPrice" type="text" value="" data-slider-min="<?= $priceMin ?>" data-slider-max="<?= $priceMax ?>" data-slider-step="1" data-slider-value="[<?= $priceMin ?>,<?= $priceMax ?>]" data-slider-tooltip="hide"/>
                    </div>
                </div>
            </div>

            <?php if ($arResult['COLORS']): ?>
            <?= $filterGroup('Цвет', 'colors', array_combine($arResult['COLORS'], $arResult['COLORS']), true) ?>
            <?php endif; ?>

            <?php if ($arResult['SIZES']): ?>
            <?= $filterGroup('Размеры', 'sizes', array_combine($arResult['SIZES'], $arResult['SIZES']), true) ?>
            <?php endif; ?>
        </div>

        <div class="d-flex">
            <button type="reset" class="f-button c-white text-secondary f-clear mr-0 px-2 px-lg-4 w-50">
                <svg width="16" height="16">
                    <use xlink:href="#icon-close"></use>
                </svg>
                <span class="pl-2">Сбросить</span>
            </button>
            <button type="submit" class="f-button c-success px-2 px-lg-4 w-50" style="margin-right: 15px">
                <svg width="16" height="16" class="d-none d-sm-inline d-md-none">
                    <use xlink:href="#icon-arrow-control"></use>
                </svg>
                <span class="pl-2">Применить</span>
            </button>
        </div>
    </form>
</div>
