<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentParameters = [
    'PARAMETERS' => [
        'SECTION_IDS' => ['PARENT' => 'BASE', 'NAME' => 'ID категорий', 'TYPE' => 'STRING', 'MULTIPLE' => 'Y'],
        'PRODUCT_IDS' => ['PARENT' => 'BASE', 'NAME' => 'ID товаров', 'TYPE' => 'STRING', 'MULTIPLE' => 'Y'],
        'PRODUCTS_TITLE' => ['PARENT' => 'VISUAL', 'NAME' => 'Заголовок блока товаров', 'TYPE' => 'STRING', 'DEFAULT' => 'Товары из новости'],
        'CACHE_TIME' => ['DEFAULT' => 3600],
    ],
];
