<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */

// Карточка товара — вёрстка /html/product-detail.html. Ниже: товары
// продавца, похожие (та же категория), просмотренные. Каталог партнёра
// (PARTNER_ID): похожие и просмотренные — только его товары.
$productId = (int)$arResult['ELEMENT_ID'];
$catalogPartnerId = (int)$arResult['PARTNER_ID'];
$partnerId = (int)$APPLICATION->IncludeComponent(
    'formaro:catalog.element',
    '',
    [
        'ELEMENT_ID' => $productId,
        'CACHE_TYPE' => $arParams['CACHE_TYPE'] ?? 'A',
        'CACHE_TIME' => $arParams['CACHE_TIME'],
    ],
    $component
);
// На телефоне крошки — под блоком товара, а не над ним (header.php).
$APPLICATION->SetPageProperty('BREADCRUMBS_CLASS', 'd-none d-sm-block');
?>
<section class="section pt-0 pb-4 d-sm-none">
    <div class="section-header">
        <div class="container-fluid">
            <div class="breadcrumbs">
                <?php $APPLICATION->IncludeComponent('bitrix:breadcrumb', 'header', ['PATH' => '', 'SITE_ID' => SITE_ID, 'START_FROM' => 0]); ?>
            </div>
        </div>
    </div>
</section>

<?php if ($partnerId): ?>
<?php $APPLICATION->IncludeComponent(
    'formaro:product.carousel',
    '',
    [
        'MODE' => 'NEW',
        'COUNT' => '12',
        'PARTNER_ID' => $partnerId,
        'EXCLUDE_ID' => $productId,
        'TITLE_ACCENT' => 'Все товары',
        'TITLE' => 'продавца',
        'CATALOG_URL' => '',
        'SECTION_CLASS' => 'light-gray-stripe',
        'CACHE_TYPE' => 'A',
        'CACHE_TIME' => '600',
    ],
    $component
); ?>
<?php endif; ?>

<?php $APPLICATION->IncludeComponent(
    'formaro:product.carousel',
    '',
    [
        'MODE' => 'NEW',
        'COUNT' => '12',
        'SECTION_ID' => (int)$arResult['SECTION']['ID'],
        'PARTNER_ID' => $catalogPartnerId,
        'EXCLUDE_ID' => $productId,
        'TITLE_ACCENT' => 'Похожие',
        'TITLE' => 'товары',
        'CATALOG_URL' => $arResult['SECTION']['URL'],
        'CACHE_TYPE' => 'A',
        'CACHE_TIME' => '600',
    ],
    $component
); ?>

<?php $APPLICATION->IncludeComponent(
    'formaro:product.carousel',
    '',
    [
        'MODE' => 'VIEWED',
        'COUNT' => '20',
        'PARTNER_ID' => $catalogPartnerId,
        'EXCLUDE_ID' => $productId,
        'TITLE_ACCENT' => 'Просмотренные',
        'TITLE' => 'товары',
        'CATALOG_URL' => '',
        'SECTION_CLASS' => 'light-gray-stripe',
        'CACHE_TYPE' => 'A',
        'CACHE_TIME' => '600',
    ],
    $component
); ?>
