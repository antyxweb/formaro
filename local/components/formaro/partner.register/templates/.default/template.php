<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arResult */

// Форма регистрации партнёра. Поля — как «Контактные данные» профиля
// покупателя; e-mail — логин для входа. Скрытое поле website — ловушка для
// ботов. Отправка и ошибки — script.js.
$e = static fn($s) => htmlspecialcharsbx((string)$s);
$u = $arResult['USER'];
$field = static function (string $name, string $label, string $value, string $col, array $attrs = []) use ($e): void {
    $id = 'partner-register-' . $name;
    $extra = '';
    foreach ($attrs as $attr => $attrValue) {
        $extra .= ' ' . $attr . ($attrValue === true ? '' : '="' . $e($attrValue) . '"');
    }
    ?>
    <div class="form-group <?= $col ?>">
        <label for="<?= $id ?>"><?= $e($label) ?></label>
        <input class="form-control" id="<?= $id ?>" name="<?= $name ?>" value="<?= $e($value) ?>"<?= $extra ?>>
    </div>
    <?php
};
?>
<div class="partner-register" id="partner-register">
    <h2>Регистрация в кабинете партнёра</h2>
    <?php if ($arResult['IS_PARTNER']): ?>
    <div class="partner-register__block">
        <p>Вы уже партнёр маркетплейса.</p>
        <a href="/cabinet/" class="f-button c-primary">Перейти в кабинет партнёра</a>
    </div>
    <?php else: ?>
    <form class="partner-register__block js-partner-register" data-sessid="<?= bitrix_sessid() ?>" novalidate>
        <?php if ($arResult['AUTHORIZED']): ?>
        <p class="text-muted">Вы вошли как <?= $e($u['email']) ?> — кабинет партнёра будет привязан к этой учётной записи, входите с тем же e-mail и паролем.</p>
        <?php endif; ?>

        <h3>Компания</h3>
        <div class="form-row">
            <?php $field('company', 'Название организации / ИП *', $u['company'], 'col-md-8', ['type' => 'text', 'autocomplete' => 'organization', 'maxlength' => 255, 'required' => true]); ?>
            <?php $field('inn', 'ИНН *', $u['inn'], 'col-md-4', ['type' => 'text', 'inputmode' => 'numeric', 'maxlength' => 12, 'pattern' => '\d{10}|\d{12}', 'required' => true]); ?>
        </div>

        <h3>Контактное лицо</h3>
        <div class="form-row">
            <?php $field('last_name', 'Фамилия *', $u['last_name'], 'col-md-6', ['type' => 'text', 'autocomplete' => 'family-name', 'maxlength' => 50, 'required' => true]); ?>
            <?php $field('name', 'Имя *', $u['name'], 'col-md-6', ['type' => 'text', 'autocomplete' => 'given-name', 'maxlength' => 50, 'required' => true]); ?>
            <?php $field('phone', 'Телефон *', $u['phone'], 'col-md-6', ['type' => 'tel', 'autocomplete' => 'tel', 'maxlength' => 50, 'placeholder' => '+7 999 123-45-67', 'required' => true]); ?>
            <?php if ($arResult['AUTHORIZED']): ?>
            <?php $field('email', 'E-mail для связи *', $u['email'], 'col-md-6', ['type' => 'email', 'autocomplete' => 'email', 'maxlength' => 255, 'required' => true]); ?>
            <?php else: ?>
            <?php $field('email', 'E-mail (логин для входа) *', '', 'col-md-6', ['type' => 'email', 'autocomplete' => 'email', 'maxlength' => 255, 'required' => true]); ?>
            <?php endif; ?>
        </div>

        <?php if (!$arResult['AUTHORIZED']): ?>
        <h3>Пароль для входа</h3>
        <div class="form-row">
            <?php $field('password', 'Пароль * (не короче 6 символов)', '', 'col-md-6', ['type' => 'password', 'autocomplete' => 'new-password', 'minlength' => 6, 'required' => true]); ?>
            <?php $field('password_repeat', 'Повторите пароль *', '', 'col-md-6', ['type' => 'password', 'autocomplete' => 'new-password', 'minlength' => 6, 'required' => true]); ?>
        </div>
        <?php endif; ?>

        <div class="partner-register__trap" aria-hidden="true">
            <label for="partner-register-website">Сайт</label>
            <input type="text" id="partner-register-website" name="website" tabindex="-1" autocomplete="off">
        </div>

        <div class="custom-control custom-checkbox mb-4">
            <input type="checkbox" class="custom-control-input" id="partner-register-consent" name="consent" value="1" required>
            <label class="custom-control-label" for="partner-register-consent">Я согласен на обработку персональных данных в соответствии с <a href="/support/policy/" target="_blank">политикой обработки персональных данных</a></label>
        </div>

        <div class="d-flex flex-wrap align-items-center">
            <button type="submit" class="f-button c-primary js-partner-register-submit">Зарегистрироваться</button>
            <small class="partner-register__status text-danger js-partner-register-error" role="alert"></small>
        </div>
        <p class="small text-muted mt-3 mb-0">После регистрации откроется кабинет партнёра: заполните профиль компании и отправьте его на проверку. Магазин появится на витрине после проверки маркетплейсом.</p>
    </form>
    <?php endif; ?>
</div>
