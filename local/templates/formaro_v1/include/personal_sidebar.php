<?php
/**
 * Меню личного кабинета справа на страницах /personal/ (вёрстка
 * /html/orders.html) — меню типа left раздела /personal/, шаблон
 * personal-sidebar. Подключается шаблонами компонентов раздела.
 */
/** @global CMain $APPLICATION */
$APPLICATION->IncludeComponent(
    'bitrix:menu',
    'personal-sidebar',
    [
        'ROOT_MENU_TYPE' => 'left',
        'MAX_LEVEL' => '1',
        'USE_EXT' => 'N',
        'ALLOW_MULTI_SELECT' => 'N',
        'MENU_CACHE_TYPE' => 'N',
    ],
    false,
    ['HIDE_ICONS' => 'Y']
);
