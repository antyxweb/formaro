<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentParameters = [
    'PARAMETERS' => [
        'PAGE_SIZE' => ['PARENT' => 'BASE', 'NAME' => 'Товаров на странице категории', 'TYPE' => 'STRING', 'DEFAULT' => '24'],
        'SEF_MODE' => [
            'sections' => ['NAME' => 'Корень каталога', 'DEFAULT' => '', 'VARIABLES' => []],
            'section' => ['NAME' => 'Категория', 'DEFAULT' => '#SECTION_CODE_PATH#/', 'VARIABLES' => ['SECTION_CODE_PATH']],
            'element' => ['NAME' => 'Товар', 'DEFAULT' => '#SECTION_CODE_PATH#/#ELEMENT_CODE#/', 'VARIABLES' => ['SECTION_CODE_PATH', 'ELEMENT_CODE']],
        ],
        'CACHE_TIME' => ['DEFAULT' => 3600],
    ],
];
