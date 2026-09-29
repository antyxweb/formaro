<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentParameters = [
    'PARAMETERS' => [
        'TITLE_HTML' => ['PARENT' => 'VISUAL', 'NAME' => 'Заголовок (HTML)', 'TYPE' => 'STRING', 'DEFAULT' => ''],
        'BUTTON_TEXT' => ['PARENT' => 'VISUAL', 'NAME' => 'Текст кнопки справа от заголовка', 'TYPE' => 'STRING', 'DEFAULT' => ''],
        'BUTTON_URL' => ['PARENT' => 'VISUAL', 'NAME' => 'Ссылка кнопки', 'TYPE' => 'STRING', 'DEFAULT' => ''],
        'POPULAR_QUERIES' => ['PARENT' => 'VISUAL', 'NAME' => 'Блок «Часто ищут»', 'TYPE' => 'STRING', 'MULTIPLE' => 'Y'],
        'PARTNER_ID' => ['PARENT' => 'BASE', 'NAME' => 'ID партнёра (только его товары)', 'TYPE' => 'STRING', 'DEFAULT' => ''],
        'SECTION_CLASS' => ['PARENT' => 'VISUAL', 'NAME' => 'CSS-класс секции', 'TYPE' => 'STRING', 'DEFAULT' => 'pt-5'],
        'PAGE_SIZE' => ['PARENT' => 'BASE', 'NAME' => 'Товаров на страницу подгрузки', 'TYPE' => 'STRING', 'DEFAULT' => '24'],
        'CACHE_TIME' => ['DEFAULT' => 3600],
    ],
];
