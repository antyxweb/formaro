<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */

// Категория — вёрстка /html/section.html: фильтр + товары, ниже выгодные
// предложения и новинки этой категории, просмотренные товары. Каталог
// партнёра (PARTNER_ID) — везде только его товары.
$sectionId = (int)$arResult['SECTION']['ID'];
$partnerId = (int)$arResult['PARTNER_ID'];
?>
<?php $APPLICATION->IncludeComponent(
    'formaro:catalog.section',
    '',
    [
        'SECTION_ID' => $sectionId,
        'FILTER_PATH' => $arResult['FILTER_PATH'],
        'PARTNER_ID' => $partnerId,
        'PAGE_SIZE' => $arParams['PAGE_SIZE'],
        'CACHE_TYPE' => $arParams['CACHE_TYPE'] ?? 'A',
        'CACHE_TIME' => $arParams['CACHE_TIME'],
    ],
    $component
); ?>

<?php $APPLICATION->IncludeComponent(
    'formaro:product.carousel',
    '',
    [
        'MODE' => 'DISCOUNT',
        'COUNT' => '12',
        'PARTNER_ID' => $partnerId,
        'SECTION_ID' => $sectionId,
        'FILTER_PATH' => $arResult['FILTER_PATH'],
        'TITLE_ACCENT' => 'Выгодные',
        'TITLE' => 'предложения',
        'CATALOG_URL' => '',
        'SECTION_CLASS' => 'light-gray-stripe',
        'CACHE_TYPE' => 'A',
        'CACHE_TIME' => '600',
    ],
    $component
); ?>

<?php $APPLICATION->IncludeComponent(
    'formaro:product.carousel',
    '',
    [
        'MODE' => 'NEW',
        'COUNT' => '12',
        'PARTNER_ID' => $partnerId,
        'SECTION_ID' => $sectionId,
        'FILTER_PATH' => $arResult['FILTER_PATH'],
        'TITLE_ACCENT' => 'Новые',
        'TITLE' => 'поступления',
        'CATALOG_URL' => '',
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
        'PARTNER_ID' => $partnerId,
        'TITLE_ACCENT' => 'Просмотренные',
        'TITLE' => 'товары',
        'CATALOG_URL' => '',
        'SECTION_CLASS' => 'light-gray-stripe',
        'CACHE_TYPE' => 'A',
        'CACHE_TIME' => '600',
    ],
    $component
); ?>
