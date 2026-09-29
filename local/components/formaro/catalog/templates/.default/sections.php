<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */

// Корень каталога — вёрстка /html/catalog.html: категории с подкатегориями,
// выгодные предложения, новые поступления.

// Только категории, допущенные площадкой (UF_APPROVED), как на главной.
$GLOBALS['catalogSectionsFilter'] = ['UF_APPROVED' => 1];
?>
<?php $APPLICATION->IncludeComponent(
    'bitrix:catalog.section.list',
    'catalog',
    [
        'IBLOCK_TYPE' => 'catalog',
        'IBLOCK_ID' => $arResult['IBLOCK_ID'],
        'SECTION_ID' => '',
        'SECTION_CODE' => '',
        'COUNT_ELEMENTS' => 'Y',
        'COUNT_ELEMENTS_FILTER' => 'CNT_ACTIVE',
        'HIDE_SECTIONS_WITH_ZERO_COUNT_ELEMENTS' => 'Y',
        'TOP_DEPTH' => '2',
        'SECTION_FIELDS' => ['NAME', 'PICTURE'],
        'SECTION_USER_FIELDS' => [],
        'FILTER_NAME' => 'catalogSectionsFilter',
        'VIEW_MODE' => 'LIST',
        'SHOW_PARENT_NAME' => 'Y',
        'SECTION_URL' => '',
        'HIDE_HEADER' => 'Y',
        'SECTION_CLASS' => 'pt-4',
        'CACHE_TYPE' => $arParams['CACHE_TYPE'] ?? 'A',
        'CACHE_TIME' => $arParams['CACHE_TIME'],
        'CACHE_GROUPS' => 'Y',
        'CACHE_FILTER' => 'Y',
        'ADD_SECTIONS_CHAIN' => 'N',
    ],
    $component
); ?>

<?php $APPLICATION->IncludeComponent(
    'formaro:product.carousel',
    '',
    [
        'MODE' => 'DISCOUNT',
        'COUNT' => '12',
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
        'TITLE_ACCENT' => 'Новые',
        'TITLE' => 'поступления',
        'CATALOG_URL' => '',
        'CACHE_TYPE' => 'A',
        'CACHE_TIME' => '600',
    ],
    $component
); ?>
