<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentParameters = [
    'PARAMETERS' => [
        'SECTION_ID' => ['PARENT' => 'BASE', 'NAME' => 'ID категории', 'TYPE' => 'STRING', 'DEFAULT' => ''],
        'PAGE_SIZE' => ['PARENT' => 'BASE', 'NAME' => 'Товаров на странице', 'TYPE' => 'STRING', 'DEFAULT' => '24'],
        'CACHE_TIME' => ['DEFAULT' => 3600],
    ],
];
