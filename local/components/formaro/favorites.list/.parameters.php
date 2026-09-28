<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentParameters = [
    'PARAMETERS' => [
        'PAGE_SIZE' => ['PARENT' => 'BASE', 'NAME' => 'Товаров на страницу подгрузки', 'TYPE' => 'STRING', 'DEFAULT' => '24'],
        'CATALOG_URL' => ['PARENT' => 'VISUAL', 'NAME' => 'Ссылка «Перейти в каталог» (пустое избранное)', 'TYPE' => 'STRING', 'DEFAULT' => '/catalog/'],
        'CACHE_TIME' => ['DEFAULT' => 3600],
    ],
];
