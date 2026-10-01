<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arResult */

// Текстовая страница: белый блок с текстом и меню раздела справа
// (include/text_page_top.php / text_page_bottom.php), под текстом —
// встроенные блоки (embeds/<код инфоблока>.php).
$e = static fn($s) => htmlspecialcharsbx((string)$s);
include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/text_page_top.php';
echo $arResult['TEXT'];
foreach ($arResult['EMBEDS'] as $embedCode => $items) {
    if ($items) {
        include __DIR__ . '/embeds/' . $embedCode . '.php';
    }
}
include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/text_page_bottom.php';
