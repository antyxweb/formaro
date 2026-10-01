<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */

// Страница раздела — элемент инфоблока «Контентные страницы» по коду.
$APPLICATION->IncludeComponent(
    'formaro:content.page',
    '',
    [
        'CODE' => $arResult['CODE'],
        'SECTION_CODE' => $arParams['SECTION_CODE'],
        'ADD_CHAIN' => $arResult['ADD_CHAIN'],
        'CACHE_TYPE' => $arParams['CACHE_TYPE'],
        'CACHE_TIME' => $arParams['CACHE_TIME'],
    ],
    $component
);
