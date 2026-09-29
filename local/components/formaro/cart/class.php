<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Корзина витрины (/personal/cart/). Вёрстка — /html/cart.html.
 *
 * Состав корзины живёт в браузере или в аккаунте (js/cart.js,
 * window.FormaroCart), поэтому страницу целиком строит script.js шаблона:
 * товары и цены — /local/ajax/cart_list.php, сгруппированы по поставщикам.
 * Сервер отдаёт только каркас со скелетонами и форму оформления, для
 * вошедшего — с предзаполненными «Данными покупателя»
 * (CartCheckoutService::buyerDefaults()).
 */
class FormaroCartComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($params)
    {
        $params['CATALOG_URL'] = (string)($params['CATALOG_URL'] ?? '/catalog/');

        return $params;
    }

    public function executeComponent()
    {
        global $USER;
        $this->arResult['BUYER'] = ['name' => '', 'phone' => '', 'email' => '', 'address' => ''];
        $userId = (is_object($USER) && $USER->IsAuthorized()) ? (int)$USER->GetID() : 0;
        if ($userId && \Bitrix\Main\Loader::includeModule('formaro.cabinet')) {
            $this->arResult['BUYER'] = \Formaro\Cabinet\Service\CartCheckoutService::buyerDefaults($userId);
        }
        $this->includeComponentTemplate();
    }
}
