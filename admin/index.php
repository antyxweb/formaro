<?php

// Кабинет администратора площадки — шаблон сайта cabinet_v1 (как у
// /cabinet/), назначается правилом шаблонов по условию /admin/ (миграция
// Version20261004100001). Доступ — только роль «Администратор».

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

$APPLICATION->SetTitle('Кабинет администратора');

$APPLICATION->IncludeComponent(
    'formaro:cabinet.admin',
    '.default',
    [
        'SEF_FOLDER' => '/admin/',
    ]
);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
