<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Formaro\Cabinet\Security\PartnerContext;
use Formaro\Cabinet\Service\PartnerRegistrationService;

/**
 * Форма регистрации партнёра («Стать партнером»; встроенный блок
 * контентной страницы — embeds/partner_registration.php компонента
 * formaro:content.page). Отправка — script.js → /local/ajax/partner_register.php
 * (PartnerRegistrationService), после — в профиль кабинета партнёра.
 *
 * Гость — форма регистрации. Вошедший покупатель — без формы: что
 * перенесётся из профиля и кнопка «Создать кабинет партнёра» (с окном
 * подтверждения). Уже партнёр — ссылка в кабинет.
 */
class FormaroPartnerRegisterComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        global $USER;
        $authorized = is_object($USER) && $USER->IsAuthorized();
        $loaded = Loader::includeModule('formaro.cabinet');
        $isPartner = $authorized && $loaded && PartnerContext::hasOwnPartner();

        $this->arResult = [
            'AUTHORIZED' => $authorized,
            'IS_PARTNER' => $isPartner,
            // Вошедший покупатель — что перенесётся в данные партнёра.
            'PROFILE' => $authorized && !$isPartner && $loaded
                ? PartnerRegistrationService::buyerProfile((int)$USER->GetID())
                : null,
        ];

        $this->includeComponentTemplate();
    }
}
