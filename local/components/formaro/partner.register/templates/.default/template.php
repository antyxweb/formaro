<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arResult */

// Гость — форма регистрации партнёра (поля — как «Контактные данные» профиля
// покупателя; e-mail — логин для входа; website — ловушка для ботов).
// Вошедший покупатель — данные профиля, которые перенесутся, и кнопка
// «Создать кабинет партнёра» (подтверждение — FormaroConfirm). Отправка и
// ошибки — script.js.
$e = static fn($s) => htmlspecialcharsbx((string)$s);
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
    <?php if ($arResult['IS_PARTNER']): ?>
    <h2>Кабинет партнёра</h2>
    <div class="partner-register__block">
        <p>Вы уже партнёр маркетплейса.</p>
        <a href="/cabinet/" class="f-button c-primary">Перейти в кабинет партнёра</a>
    </div>
    <?php elseif ($arResult['PROFILE'] !== null): ?>
    <?php
    $p = $arResult['PROFILE'];
    $person = trim($p['last_name'] . ' ' . $p['name'] . ' ' . $p['second_name']);
    $rows = [
        'Название организации / ИП' => $p['company'],
        'ИНН' => $p['inn'],
        'ОГРН / ОГРНИП' => $p['ogrn'],
        'Юридический адрес' => $p['legal_address'],
        'Банк' => $p['bank_name'] !== '' ? $p['bank_name'] . ($p['bik'] !== '' ? ', БИК ' . $p['bik'] : '') : '',
        'Расчётный счёт' => $p['account'],
        'Руководитель' => $p['ceo_name'],
        'Контактное лицо' => $person,
        'Телефон' => $p['phone'],
        'E-mail' => $p['email'],
    ];
    ?>
    <h2>Кабинет партнёра</h2>
    <div class="partner-register__block js-partner-create" data-sessid="<?= bitrix_sessid() ?>">
        <p>Вы уже зарегистрированы как покупатель — можно создать кабинет партнёра для этой же учётной записи: входите с тем же e-mail и паролем, а покупки и заказы останутся на месте.</p>
        <p class="mb-2">В данные партнёра перенесутся данные вашего профиля:</p>
        <table class="text-page__props partner-register__profile"><tbody>
            <?php foreach ($rows as $label => $value): ?>
            <tr><th scope="row"><?= $e($label) ?></th><td><?= $value !== '' ? $e($value) : '<span class="text-muted">не указано — заполните в кабинете</span>' ?></td></tr>
            <?php endforeach; ?>
        </tbody></table>
        <p class="small text-muted">Изменить их сейчас можно в <a href="/personal/profile/">профиле покупателя</a>. После создания кабинета дополните профиль компании и отправьте его на проверку — магазин появится на витрине после проверки маркетплейсом.</p>
        <div class="d-flex flex-wrap align-items-center">
            <button type="button" class="f-button c-primary js-partner-create-submit">Создать кабинет партнёра</button>
            <small class="partner-register__status text-danger js-partner-create-error" role="alert"></small>
        </div>
    </div>
    <?php else: ?>
    <h2>Регистрация в кабинете партнёра</h2>
    <form class="partner-register__block js-partner-register" data-sessid="<?= bitrix_sessid() ?>" novalidate>
        <h3>Компания</h3>
        <div class="form-row">
            <?php $field('company', 'Название организации / ИП *', '', 'col-md-8', ['type' => 'text', 'autocomplete' => 'organization', 'maxlength' => 255, 'required' => true]); ?>
            <?php $field('inn', 'ИНН *', '', 'col-md-4', ['type' => 'text', 'inputmode' => 'numeric', 'maxlength' => 12, 'pattern' => '\d{10}|\d{12}', 'required' => true]); ?>
        </div>

        <h3>Контактное лицо</h3>
        <div class="form-row">
            <?php $field('last_name', 'Фамилия *', '', 'col-md-6', ['type' => 'text', 'autocomplete' => 'family-name', 'maxlength' => 50, 'required' => true]); ?>
            <?php $field('name', 'Имя *', '', 'col-md-6', ['type' => 'text', 'autocomplete' => 'given-name', 'maxlength' => 50, 'required' => true]); ?>
            <?php $field('phone', 'Телефон *', '', 'col-md-6', ['type' => 'tel', 'autocomplete' => 'tel', 'maxlength' => 50, 'placeholder' => '+7 999 123-45-67', 'required' => true]); ?>
            <?php $field('email', 'E-mail (логин для входа) *', '', 'col-md-6', ['type' => 'email', 'autocomplete' => 'email', 'maxlength' => 255, 'required' => true]); ?>
        </div>

        <h3>Пароль для входа</h3>
        <div class="form-row">
            <?php $field('password', 'Пароль * (не короче 6 символов)', '', 'col-md-6', ['type' => 'password', 'autocomplete' => 'new-password', 'minlength' => 6, 'required' => true]); ?>
            <?php $field('password_repeat', 'Повторите пароль *', '', 'col-md-6', ['type' => 'password', 'autocomplete' => 'new-password', 'minlength' => 6, 'required' => true]); ?>
        </div>

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
