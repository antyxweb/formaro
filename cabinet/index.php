<?php

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
