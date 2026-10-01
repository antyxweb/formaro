<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @global CMain $APPLICATION */

// Главная раздела — плитки пунктов раздела (меню left папки, его наполняет
// .left.menu_ext.php из инфоблока).
$APPLICATION->IncludeComponent(
    'bitrix:menu',
    'section-tiles',
    [
        'ROOT_MENU_TYPE' => 'left',
        'MAX_LEVEL' => '1',
        'USE_EXT' => 'Y',
        'ALLOW_MULTI_SELECT' => 'N',
        'MENU_CACHE_TYPE' => 'N',
    ],
    $component,
    ['HIDE_ICONS' => 'Y']
);
