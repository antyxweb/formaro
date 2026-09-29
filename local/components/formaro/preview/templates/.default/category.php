<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arResult */

// Категория: плитка в корне каталога (шаблон catalog.section.list
// «catalog» сайта) и страница категории — товары сеткой, как в
// formaro:catalog.section (фильтра нет: он строится по сохранённым данным).
include __DIR__ . '/banner.php';
require __DIR__ . '/render.php';
$view = new FormaroPreviewTemplate();
?>
<?php if ($arResult['TILE']): ?>
<div class="container-fluid pt-4"><h4 class="text-secondary mb-0">В каталоге</h4></div>
<?php $view->render(
    $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/components/bitrix/catalog.section.list/catalog/template.php',
    ['SECTIONS_LIST' => [$arResult['TILE']], 'IBLOCK' => ['~NAME' => '', 'DESCRIPTION' => '']],
    ['HIDE_HEADER' => 'Y', 'SECTION_CLASS' => 'pt-4']
); ?>
<?php endif; ?>

<section class="section pt-4">
    <div class="section-body">
        <div class="container-fluid">
            <h4 class="text-secondary mb-4">Страница категории</h4>
            <div class="row product-list d-flex flex-wrap">
                <?php foreach ($arResult['CATALOG_ITEMS'] as $item): ?>
                <div class="product-item">
                    <?php include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/product_card.php'; ?>
                </div>
                <?php endforeach; ?>

                <?php if (!$arResult['CATALOG_ITEMS']): ?>
                <div class="col-12 py-5 text-center text-secondary">
                    <p class="mb-0">В категории пока нет товаров — они появятся здесь, когда вы привяжете к ней товары.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
