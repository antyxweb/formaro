<?php

// Свой урезанный шаблон (local/templates/cabinet_v1) — без публичного меню и
// футера маркетинг-сайта formaro_v1, у кабинета партнёра полностью своя
// вёрстка (сайдбар/топбар из formaro:cabinet.partner). Должно быть вызвано
// ДО header.php — иначе движок успеет выбрать шаблон по умолчанию.
$APPLICATION->SetTemplateName('cabinet_v1');

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->SetTitle('Кабинет партнёра');

$APPLICATION->IncludeComponent(
    'formaro:cabinet.partner',
    '.default',
    [
        'SEF_MODE' => 'Y',
        'SEF_FOLDER' => '/cabinet/',
    ]
);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
