<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Formaro\Cabinet\Security\PartnerContext;

/**
 * Форма регистрации партнёра («Стать партнером»; встроенный блок
 * контентной страницы — embeds/partner_registration.php компонента
 * formaro:content.page). Отправка — script.js → /local/ajax/partner_register.php
 * (PartnerRegistrationService), после — в профиль кабинета партнёра.
 *
 * Авторизованный покупатель — без полей пароля (кабинет привяжется к его
 * учётной записи), контакты — из профиля. Уже партнёр — ссылка в кабинет.
 */
class FormaroPartnerRegisterComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        global $USER;
        $authorized = is_object($USER) && $USER->IsAuthorized();
        $user = $authorized ? (CUser::GetByID((int)$USER->GetID())->Fetch() ?: []) : [];
        $isPartner = $authorized && !$USER->IsAdmin() && Loader::includeModule('formaro.cabinet') && PartnerContext::hasAccess();

        $this->arResult = [
            'AUTHORIZED' => $authorized,
            'IS_PARTNER' => $isPartner,
            'USER' => [
                'last_name' => (string)($user['LAST_NAME'] ?? ''),
                'name' => (string)($user['NAME'] ?? ''),
                'email' => (string)($user['EMAIL'] ?? ''),
                'phone' => (string)(($user['WORK_PHONE'] ?? '') ?: ($user['PERSONAL_PHONE'] ?? '')),
                'company' => (string)($user['UF_BUYER_COMPANY'] ?? ''),
                'inn' => (string)($user['UF_BUYER_INN'] ?? ''),
            ],
        ];
        if ($authorized) {
            $extra = CUser::GetList($by = 'id', $order = 'asc', ['ID' => (int)$USER->GetID()], ['SELECT' => ['UF_BUYER_COMPANY', 'UF_BUYER_INN']])->Fetch() ?: [];
            $this->arResult['USER']['company'] = (string)($extra['UF_BUYER_COMPANY'] ?? '');
            $this->arResult['USER']['inn'] = (string)($extra['UF_BUYER_INN'] ?? '');
        }

        $this->includeComponentTemplate();
    }
}
