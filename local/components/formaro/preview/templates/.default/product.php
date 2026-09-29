<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arResult */
/** @global CMain $APPLICATION */

// Карточка товара — компонентом витрины (formaro:catalog.element) с
// данными из формы; ниже — «Товары продавца», как на странице товара.
include __DIR__ . '/banner.php';

$product = $arResult['PRODUCT'];
$APPLICATION->IncludeComponent('formaro:catalog.element', '', ['PREVIEW' => $product], false);
?>

<?php $APPLICATION->IncludeComponent(
    'formaro:product.carousel',
    '',
    [
        'MODE' => 'NEW',
        'COUNT' => '12',
        'PARTNER_ID' => $product['partner_id'],
        'EXCLUDE_ID' => $product['id'],
        'TITLE_ACCENT' => 'Товары',
        'TITLE' => 'продавца',
        'CATALOG_URL' => '',
        'SECTION_CLASS' => 'light-gray-stripe',
        'CACHE_TYPE' => 'A',
        'CACHE_TIME' => '600',
    ],
    false
); ?>

<?php if ($arResult['SECTION']): ?>
<?php $APPLICATION->IncludeComponent(
    'formaro:product.carousel',
    '',
    [
        'MODE' => 'NEW',
        'COUNT' => '12',
        'SECTION_ID' => $arResult['SECTION']['ID'],
        'EXCLUDE_ID' => $product['id'],
        'TITLE_ACCENT' => 'Похожие',
        'TITLE' => 'товары',
        'CATALOG_URL' => $arResult['SECTION']['URL'],
        'CACHE_TYPE' => 'A',
        'CACHE_TIME' => '600',
    ],
    false
); ?>
<?php endif; ?>
