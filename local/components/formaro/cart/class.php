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
 * Сервер отдаёт только каркас со скелетонами.
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
        $this->includeComponentTemplate();
    }
}
