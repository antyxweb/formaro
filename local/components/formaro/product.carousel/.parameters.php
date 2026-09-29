<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentParameters = [
    'PARAMETERS' => [
        'MODE' => [
            'PARENT' => 'BASE',
            'NAME' => 'Подборка',
            'TYPE' => 'LIST',
            'VALUES' => ['DISCOUNT' => 'Выгодные предложения (со скидкой)', 'NEW' => 'Новые поступления'],
            'DEFAULT' => 'NEW',
        ],
        'COUNT' => ['PARENT' => 'BASE', 'NAME' => 'Количество товаров', 'TYPE' => 'STRING', 'DEFAULT' => '12'],
        'TITLE_ACCENT' => ['PARENT' => 'VISUAL', 'NAME' => 'Заголовок: выделенная часть', 'TYPE' => 'STRING', 'DEFAULT' => ''],
        'TITLE' => ['PARENT' => 'VISUAL', 'NAME' => 'Заголовок: остальная часть', 'TYPE' => 'STRING', 'DEFAULT' => ''],
        'CATALOG_URL' => ['PARENT' => 'VISUAL', 'NAME' => 'Ссылка "Перейти в каталог"', 'TYPE' => 'STRING', 'DEFAULT' => '/catalog/'],
        'SECTION_CLASS' => ['PARENT' => 'VISUAL', 'NAME' => 'Доп. CSS-класс секции', 'TYPE' => 'STRING', 'DEFAULT' => ''],
        'PARTNER_ID' => ['PARENT' => 'BASE', 'NAME' => 'ID партнёра (только его товары)', 'TYPE' => 'STRING', 'DEFAULT' => ''],
        'CACHE_TIME' => ['DEFAULT' => 600],
    ],
];
