<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arResult */

// «Ваш профиль»: слева — контактные данные, юридические реквизиты, смена
// пароля (каждый блок сохраняется отдельно, script.js → /local/ajax/profile.php),
// справа — меню личного кабинета (как на «Ваших заказах»). У партнёра
// кабинета реквизиты — его компании, только просмотр.
$e = static fn($s) => htmlspecialcharsbx((string)$s);
$legalLabels = [
    'company' => ['Название организации / ИП', 'col-12'],
    'inn' => ['ИНН', 'col-md-4'],
    'kpp' => ['КПП', 'col-md-4'],
    'ogrn' => ['ОГРН / ОГРНИП', 'col-md-4'],
    'legal_address' => ['Юридический адрес', 'col-12'],
    'bank_name' => ['Банк', 'col-md-8'],
    'bik' => ['БИК', 'col-md-4'],
    'account' => ['Расчётный счёт', 'col-md-6'],
    'corr_account' => ['Корр. счёт', 'col-md-6'],
    'ceo_name' => ['ФИО руководителя', 'col-12'],
];
$field = static function (string $form, string $name, string $label, string $value, string $col, array $attrs = []) use ($e): void {
    $id = 'profile-' . $form . '-' . $name;
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
<section class="section pt-4">
    <div class="section-body">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 col-xl-9">
                    <div class="personal-profile mr-xl-5" id="personal-profile" data-sessid="<?= bitrix_sessid() ?>">
                        <?php if (!$arResult['AUTHORIZED']): ?>
                        <div class="bg-white py-4 px-3 px-lg-4 mb-4">
                            <p class="mb-4">Войдите, чтобы увидеть свой профиль.</p>
                            <a href="/login/?backurl=<?= urlencode('/personal/profile/') ?>" class="f-button c-primary">Войти</a>
                        </div>
                        <?php else: ?>
                        <?php $c = $arResult['contacts']; ?>
                        <form class="profile-block bg-white py-4 px-3 px-lg-4 mb-4 js-profile-form" data-action="contacts" novalidate>
                            <h4 class="mb-4">Контактные данные</h4>
                            <div class="form-row">
                                <?php $field('contacts', 'last_name', 'Фамилия', $c['last_name'], 'col-md-4', ['type' => 'text', 'autocomplete' => 'family-name', 'maxlength' => 50]); ?>
                                <?php $field('contacts', 'name', 'Имя *', $c['name'], 'col-md-4', ['type' => 'text', 'autocomplete' => 'given-name', 'maxlength' => 50, 'required' => true]); ?>
                                <?php $field('contacts', 'second_name', 'Отчество', $c['second_name'], 'col-md-4', ['type' => 'text', 'autocomplete' => 'additional-name', 'maxlength' => 50]); ?>
                                <?php $field('contacts', 'email', 'E-mail (логин для входа) *', $c['email'], 'col-md-6', ['type' => 'email', 'autocomplete' => 'email', 'maxlength' => 255, 'required' => true]); ?>
                                <?php $field('contacts', 'phone', $arResult['partner'] ? 'Телефон (рабочий)' : 'Телефон', $c['phone'], 'col-md-6', ['type' => 'tel', 'autocomplete' => 'tel', 'maxlength' => 50, 'placeholder' => '+7 999 123-45-67']); ?>
                            </div>
                            <?php if ($arResult['partner']): ?>
                            <small class="d-block text-muted mb-3">Эти же данные — во вкладке «Авторизация» кабинета партнёра.</small>
                            <?php endif; ?>
                            <div class="d-flex flex-wrap align-items-center">
                                <button type="submit" class="f-button c-primary">Сохранить</button>
                                <small class="profile-status js-profile-status" role="status"></small>
                            </div>
                        </form>

                        <?php $l = $arResult['legal']; ?>
                        <?php if ($arResult['partner']): ?>
                        <div class="profile-block bg-white py-4 px-3 px-lg-4 mb-4">
                            <h4 class="mb-2">Юридические реквизиты</h4>
                            <p class="text-muted mb-4">Вы — партнёр маркетплейса: как покупатель вы выступаете от компании из кабинета партнёра. Реквизиты меняются там и проходят проверку маркетплейса.</p>
                            <dl class="row profile-legal mb-4">
                                <?php foreach ($legalLabels as $key => [$label]): ?>
                                <dt class="col-sm-4"><?= $e($label) ?></dt>
                                <dd class="col-sm-8"><?= $l[$key] !== '' ? $e($l[$key]) : '<span class="text-muted">не указано</span>' ?></dd>
                                <?php endforeach; ?>
                            </dl>
                            <a href="<?= $e($arResult['partner']['url']) ?>" class="f-button c-gray">Изменить в кабинете партнёра</a>
                        </div>
                        <?php else: ?>
                        <form class="profile-block bg-white py-4 px-3 px-lg-4 mb-4 js-profile-form" data-action="legal" novalidate>
                            <h4 class="mb-2">Юридические реквизиты</h4>
                            <p class="text-muted mb-4">Заполните, если покупаете от организации или ИП.</p>
                            <div class="form-row">
                                <?php foreach ($legalLabels as $key => [$label, $col]): ?>
                                <?php $field('legal', $key, $label, $l[$key], $col, ['type' => 'text', 'maxlength' => 255] + (in_array($key, ['inn', 'kpp', 'ogrn', 'bik', 'account', 'corr_account'], true) ? ['inputmode' => 'numeric'] : [])); ?>
                                <?php endforeach; ?>
                            </div>
                            <div class="d-flex flex-wrap align-items-center">
                                <button type="submit" class="f-button c-primary">Сохранить</button>
                                <small class="profile-status js-profile-status" role="status"></small>
                            </div>
                        </form>
                        <?php endif; ?>

                        <form class="profile-block bg-white py-4 px-3 px-lg-4 mb-4 js-profile-form" data-action="password" novalidate>
                            <h4 class="mb-4">Смена пароля</h4>
                            <div class="form-row">
                                <?php $field('password', 'current', 'Текущий пароль', '', 'col-md-4', ['type' => 'password', 'autocomplete' => 'current-password', 'required' => true]); ?>
                                <?php $field('password', 'new', 'Новый пароль', '', 'col-md-4', ['type' => 'password', 'autocomplete' => 'new-password', 'minlength' => 6, 'required' => true]); ?>
                                <?php $field('password', 'repeat', 'Повтор нового пароля', '', 'col-md-4', ['type' => 'password', 'autocomplete' => 'new-password', 'minlength' => 6, 'required' => true]); ?>
                            </div>
                            <small class="d-block text-muted mb-3">Не короче 6 символов.</small>
                            <div class="d-flex flex-wrap align-items-center">
                                <button type="submit" class="f-button c-primary">Изменить пароль</button>
                                <small class="profile-status js-profile-status" role="status"></small>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12 col-xl-3">
                    <?php include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/personal_sidebar.php'; ?>
                </div>
            </div>
        </div>
    </div>
</section>
