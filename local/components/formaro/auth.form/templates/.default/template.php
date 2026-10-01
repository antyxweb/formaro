<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arParams */
/** @var array $arResult */

// Четыре вида в одном блоке (переключает script.js без перезагрузки):
// «Вход» и «Регистрация» — вкладками, «Восстановление пароля» — по ссылке
// «Забыли пароль?», «Новый пароль» — по ссылке из письма.
$e = static fn($s) => htmlspecialcharsbx((string)$s);
$view = $arResult['VIEW'];
$field = static function (string $form, string $name, string $label, string $col, array $attrs = []) use ($e): void {
    $id = 'auth-' . $form . '-' . $name;
    $extra = '';
    foreach ($attrs as $attr => $attrValue) {
        $extra .= ' ' . $attr . ($attrValue === true ? '' : '="' . $e($attrValue) . '"');
    }
    ?>
    <div class="form-group <?= $col ?>">
        <label for="<?= $id ?>"><?= $e($label) ?></label>
        <input class="form-control" id="<?= $id ?>" name="<?= $name ?>"<?= $extra ?>>
    </div>
    <?php
};
$submit = static function (string $text): void {
    ?>
    <div class="d-flex flex-wrap align-items-center">
        <button type="submit" class="f-button c-primary js-auth-submit"><?= htmlspecialcharsbx($text) ?></button>
        <small class="auth-form__error text-danger js-auth-error" role="alert"></small>
    </div>
    <?php
};
?>
<div class="auth-form bg-white py-4 px-3 px-lg-4 mb-4" id="auth-form" data-sessid="<?= bitrix_sessid() ?>" data-backurl="<?= $e($arResult['BACKURL']) ?>">
    <?php if ($view !== 'change'): ?>
    <div class="auth-form__tabs mb-4" role="tablist">
        <button type="button" class="auth-form__tab js-auth-tab" role="tab" data-view="login" aria-selected="<?= $view === 'login' ? 'true' : 'false' ?>">Вход</button>
        <button type="button" class="auth-form__tab js-auth-tab" role="tab" data-view="register" aria-selected="<?= $view === 'register' ? 'true' : 'false' ?>">Регистрация</button>
    </div>
    <?php endif; ?>

    <form class="js-auth-view" data-view="login" data-action="login" novalidate<?= $view === 'login' ? '' : ' hidden' ?>>
        <p class="mb-4"><?= $e($arParams['TITLE']) ?></p>
        <div class="form-row">
            <?php $field('login', 'email', 'E-mail', 'col-md-6', ['type' => 'email', 'autocomplete' => 'username', 'required' => true]); ?>
            <?php $field('login', 'password', 'Пароль', 'col-md-6', ['type' => 'password', 'autocomplete' => 'current-password', 'required' => true]); ?>
        </div>
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="auth-login-remember" name="remember" value="1" checked>
                <label class="custom-control-label" for="auth-login-remember">Запомнить меня</label>
            </div>
            <a href="#" class="js-auth-tab" data-view="forgot">Забыли пароль?</a>
        </div>
        <?php $submit('Войти'); ?>
    </form>

    <form class="js-auth-view" data-view="register" data-action="register" novalidate<?= $view === 'register' ? '' : ' hidden' ?>>
        <p class="mb-4">Зарегистрируйтесь, чтобы оформлять заказы и следить за ними.</p>
        <div class="form-row">
            <?php $field('register', 'last_name', 'Фамилия', 'col-md-6', ['type' => 'text', 'autocomplete' => 'family-name', 'maxlength' => 50]); ?>
            <?php $field('register', 'name', 'Имя *', 'col-md-6', ['type' => 'text', 'autocomplete' => 'given-name', 'maxlength' => 50, 'required' => true]); ?>
            <?php $field('register', 'email', 'E-mail (логин для входа) *', 'col-md-6', ['type' => 'email', 'autocomplete' => 'email', 'maxlength' => 255, 'required' => true]); ?>
            <?php $field('register', 'phone', 'Телефон', 'col-md-6', ['type' => 'tel', 'autocomplete' => 'tel', 'maxlength' => 50, 'placeholder' => '+7 999 123-45-67']); ?>
            <?php $field('register', 'password', 'Пароль * (не короче 6 символов)', 'col-md-6', ['type' => 'password', 'autocomplete' => 'new-password', 'minlength' => 6, 'required' => true]); ?>
            <?php $field('register', 'password_repeat', 'Повторите пароль *', 'col-md-6', ['type' => 'password', 'autocomplete' => 'new-password', 'minlength' => 6, 'required' => true]); ?>
        </div>
        <div class="auth-form__trap" aria-hidden="true">
            <label for="auth-register-website">Сайт</label>
            <input type="text" id="auth-register-website" name="website" tabindex="-1" autocomplete="off">
        </div>
        <div class="custom-control custom-checkbox mb-4">
            <input type="checkbox" class="custom-control-input" id="auth-register-consent" name="consent" value="1" required>
            <label class="custom-control-label" for="auth-register-consent">Я согласен на обработку персональных данных в соответствии с <a href="/support/policy/" target="_blank">политикой обработки персональных данных</a></label>
        </div>
        <?php $submit('Зарегистрироваться'); ?>
    </form>

    <form class="js-auth-view" data-view="forgot" data-action="forgot" novalidate hidden>
        <h4 class="mb-3">Восстановление пароля</h4>
        <p class="mb-4">Укажите e-mail, с которым вы зарегистрированы, — пришлём ссылку для смены пароля.</p>
        <div class="form-row">
            <?php $field('forgot', 'email', 'E-mail', 'col-md-6', ['type' => 'email', 'autocomplete' => 'email', 'required' => true]); ?>
        </div>
        <?php $submit('Отправить ссылку'); ?>
        <p class="mt-4 mb-0"><a href="#" class="js-auth-tab" data-view="login">← Вернуться ко входу</a></p>
    </form>

    <?php if ($arResult['CHANGE']): ?>
    <form class="js-auth-view" data-view="change" data-action="change_password" novalidate>
        <h4 class="mb-3">Новый пароль</h4>
        <p class="mb-4">Придумайте новый пароль для <?= $e($arResult['CHANGE']['login']) ?>.</p>
        <input type="hidden" name="login" value="<?= $e($arResult['CHANGE']['login']) ?>">
        <input type="hidden" name="checkword" value="<?= $e($arResult['CHANGE']['checkword']) ?>">
        <div class="form-row">
            <?php $field('change', 'password', 'Новый пароль * (не короче 6 символов)', 'col-md-6', ['type' => 'password', 'autocomplete' => 'new-password', 'minlength' => 6, 'required' => true]); ?>
            <?php $field('change', 'password_repeat', 'Повторите пароль *', 'col-md-6', ['type' => 'password', 'autocomplete' => 'new-password', 'minlength' => 6, 'required' => true]); ?>
        </div>
        <?php $submit('Сохранить пароль'); ?>
    </form>
    <?php endif; ?>

    <div class="auth-form__done js-auth-done" role="status" hidden>
        <p class="mb-4 js-auth-done-text"></p>
        <a href="#" class="f-button c-primary js-auth-tab" data-view="login">Войти</a>
    </div>
</div>
