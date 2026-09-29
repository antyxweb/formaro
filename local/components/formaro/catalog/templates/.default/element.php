<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */

// Карточка товара — вёрстка /html/product-detail.html. Ниже: товары
// продавца, похожие (та же категория), просмотренные.
$productId = (int)$arResult['ELEMENT_ID'];
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
?>

<?php if ($partnerId): ?>
<?php $APPLICATION->IncludeComponent(
    'formaro:product.carousel',
    '',
    [
        'MODE' => 'NEW',
        'COUNT' => '12',
        'PARTNER_ID' => $partnerId,
        'EXCLUDE_ID' => $productId,
        'TITLE_ACCENT' => 'Товары',
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
