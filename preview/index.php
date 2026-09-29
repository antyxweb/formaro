<?php
// Предпросмотр товара, категории или новости из формы кабинета партнёра
// (кнопка «Предпросмотр» шлёт сюда POST) — см. formaro:preview.
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
$APPLICATION->SetTitle('Предпросмотр');
$APPLICATION->IncludeComponent('formaro:preview', '', [], false);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
