<?php

// Свой урезанный шаблон (local/templates/cabinet_v1) — без публичного меню и
// футера маркетинг-сайта formaro_v1, у кабинета партнёра полностью своя
// вёрстка (сайдбар/топбар из formaro:cabinet.partner). Шаблон назначается не
// программным SetTemplateName() (ненадёжно — движок к этому моменту мог уже
// закэшировать выбор шаблона по правилам b_site_template), а стандартным
// правилом в Настройки → Сайты → Шаблоны сайтов с условием по /cabinet/
// (см. миграцию/установку модуля formaro.cabinet).

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
