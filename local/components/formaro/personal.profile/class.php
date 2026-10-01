<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Formaro\Cabinet\Repository\BuyerProfileRepository;

/**
 * «Ваш профиль» покупателя (/personal/profile/): контактные данные,
 * юридические реквизиты, смена пароля — BuyerProfileRepository;
 * сохранение — /local/ajax/profile.php (script.js шаблона).
 *
 * Партнёр кабинета — тоже покупатель: контакты у него общие с вкладкой
 * «Авторизация» кабинета, реквизиты — его компании из кабинета (только
 * просмотр, со ссылкой в кабинет). Данные личные — без кэша.
 */
class FormaroPersonalProfileComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        global $USER;
        $userId = (is_object($USER) && $USER->IsAuthorized()) ? (int)$USER->GetID() : 0;
        $this->arResult = ['AUTHORIZED' => $userId > 0];

        if ($userId && Loader::includeModule('iblock') && Loader::includeModule('formaro.cabinet')) {
            $this->arResult += (new BuyerProfileRepository())->get($userId);
        }

        $this->includeComponentTemplate();
    }
}
