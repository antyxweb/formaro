<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arParams */
/** @var array $arResult */

// Вёрстка — /html/favorites.html. Категории и цену фильтра script.js
// сужает до того, что реально есть в избранном (facets из
// /local/ajax/favorites_list.php).

$checkboxRow = static function (string $value, string $label): string {
    return '<label class="px-3 px-lg-4 py-2 mb-0">'
        . '<input type="checkbox" name="sections" value="' . htmlspecialcharsbx($value) . '">'
        . '<svg width="20" height="20"><use xlink:href="#icon-checkbox-tick"></use></svg>'
        . '<span>' . htmlspecialcharsbx($label) . '</span>'
        . '</label>';
};
?>
<section class="section pt-4" id="favorites-list" data-page-size="<?= (int)$arParams['PAGE_SIZE'] ?>">
    <div class="section-body">
        <div class="container-fluid">
            <div class="row">
                <div class="filter-wrap col-12 col-xl-3 d-none" id="favorites-filter-wrap">
                    <form id="filter" data-price-min="0" data-price-max="1">
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
                            <div class="filter-row" id="favorites-filter-categories">
                                <div class="filter-row-title d-flex align-items-center py-2 py-lg-3 px-3 px-lg-4">
                                    <h6 class="mr-auto mb-0">Категории</h6>
                                    <svg width="20" height="20">
                                        <use xlink:href="#icon-arrow-up"></use>
                                    </svg>
                                </div>
                                <div class="filter-row-list filter-row-categories py-2">
                                    <?php foreach ($arResult['SECTION_GROUPS'] as $group): ?>
                                    <div class="filter-cat d-none" data-section-id="<?= (int)$group['ID'] ?>">
                                        <?= $checkboxRow((string)$group['ID'], $group['NAME']) ?>
                                        <?php if ($group['ITEMS']): ?>
                                        <button type="button" class="filter-cat-toggle" aria-label="Подкатегории">
                                            <svg width="16" height="16"><use xlink:href="#icon-arrow-down"></use></svg>
                                        </button>
                                        <div class="filter-cat-children d-none">
                                            <?php foreach ($group['ITEMS'] as $child): ?>
                                            <div class="filter-cat-child d-none" data-section-id="<?= (int)$child['ID'] ?>">
                                                <?= $checkboxRow((string)$child['ID'], $child['NAME']) ?>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
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
                                        <input type="text" class="filterPriceMin" name="price_min" value="0">
                                        <span class="text-secondary">&mdash;</span>
                                        <input type="text" class="filterPriceMax" name="price_max" value="1">
                                    </div>
                                    <div class="mx-2 mx-lg-3 d-flex justify-content-center mb-3">
                                        <input id="filterPrice" type="text" value="" data-slider-min="0" data-slider-max="1" data-slider-step="1" data-slider-value="[0,1]" data-slider-tooltip="hide"/>
                                    </div>
                                </div>
                            </div>
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

                <div class="col-12 col-xl-9 catalog-section" id="favorites-content">
                    <div class="d-none" id="favorites-options">
                    <div class="catalog-options mb-3 mb-lg-4 d-flex align-items-center">
                        <span class="mr-auto text-dark">Всего: <span id="favorites-total">0</span></span>

                        <button type="button" class="f-button f-dropdown text-decoration-none">
                            <svg width="16" height="16">
                                <use xlink:href="#icon-sort-black"></use>
                            </svg>
                            <span class="pl-2 d-none d-sm-inline-block">Недавно добавленные</span>

                            <ul class="f-dropdown-list">
                                <li><a class="dropdown-item small active" href="#" data-sort="added">Недавно добавленные</a></li>
                                <li><a class="dropdown-item small" href="#" data-sort="new">Сначала новинки</a></li>
                                <li><a class="dropdown-item small" href="#" data-sort="cheap">Сначала дешевле</a></li>
                                <li><a class="dropdown-item small" href="#" data-sort="expensive">Сначала дороже</a></li>
                            </ul>
                        </button>

                        <button type="button" class="f-button f-dropdown bg-white text-decoration-none d-xl-none" data-fancybox data-src="#filter">
                            <svg width="16" height="16">
                                <use xlink:href="#icon-filter-black"></use>
                            </svg>
                            <span class="pl-2">Фильтры</span>
                        </button>
                    </div>
                    </div>

                    <div id="catalog-grid" class="row product-list d-flex flex-wrap">
                        <div class="col-12 py-5 text-center text-secondary d-none" id="favorites-empty">
                            <p class="mb-4">В избранном пока нет товаров</p>
                            <a href="<?= htmlspecialcharsbx($arParams['CATALOG_URL']) ?>" class="f-button c-primary">Перейти в каталог</a>
                        </div>
                        <div class="col-12 py-5 text-center text-secondary d-none" id="favorites-not-found">
                            <p class="mb-4">По выбранным условиям ничего не найдено</p>
                            <button type="button" class="f-button c-secondary" id="favorites-reset-filter">Сбросить фильтр</button>
                        </div>
                        <?php // Скелетоны карточек, пока грузится избранное (убирает script.js). ?>
                        <?php for ($i = 0; $i < 8; $i++): ?>
                        <div class="product-item product-skeleton"><?php include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/product_card_skeleton.php'; ?></div>
                        <?php endfor; ?>
                        <div class="pager d-none" id="favorites-pager">
                            <div class="d-flex justify-content-center pt-4">
                                <button type="button" class="f-button c-secondary" id="favorites-load-more">
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
            </div>
        </div>
    </div>
</section>
