<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Вход / регистрация покупателя / восстановление пароля — на страницах
 * кабинета покупателя для гостя вместо кнопки «Войти» (профиль, заказы,
 * уведомления, чаты). Отправка — script.js → /local/ajax/auth.php
 * (BuyerAuthService); после входа и регистрации — перезагрузка страницы
 * (или переход по ?backurl=, только адрес этого сайта).
 *
 * Смена пароля по ссылке из письма: ?change_password=yes&USER_LOGIN=…&
 * USER_CHECKWORD=… (стандартная ссылка /auth/index.php… переадресуется
 * сюда, на /personal/profile/).
 *
 * Параметры: TITLE — пояснение над формой входа.
 */
class FormaroAuthFormComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($params)
    {
        $params['TITLE'] = (string)($params['TITLE'] ?? 'Войдите или зарегистрируйтесь, чтобы продолжить.');

        return $params;
    }

    public function executeComponent()
    {
        $backUrl = (string)($_GET['backurl'] ?? '');
        // Только адрес этого сайта: /путь, но не //чужой-домен.
        if (!preg_match('#^/(?!/)#', $backUrl)) {
            $backUrl = '';
        }

        $change = null;
        if (($_GET['change_password'] ?? '') === 'yes' && !empty($_GET['USER_CHECKWORD']) && !empty($_GET['USER_LOGIN'])) {
            $change = ['login' => (string)$_GET['USER_LOGIN'], 'checkword' => (string)$_GET['USER_CHECKWORD']];
        }

        $this->arResult = [
            'BACKURL' => $backUrl,
            'CHANGE' => $change,
            'VIEW' => $change ? 'change' : (($_GET['register'] ?? '') === 'yes' ? 'register' : 'login'),
        ];
        $this->includeComponentTemplate();
    }
}
