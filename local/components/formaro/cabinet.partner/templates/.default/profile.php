<?php

use Formaro\Cabinet\Repository\PartnerDocumentRepository;
use Formaro\Cabinet\Repository\PartnerRepository;

/** @var array $arResult */
global $USER;

$partnerId = $arResult['PARTNER_ID'];
$partnerRepo = new PartnerRepository();
$documentRepo = new PartnerDocumentRepository();

$passwordError = '';
$passwordOk = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'password') {
    $current = (string)($_POST['p_current'] ?? '');
    $new = (string)($_POST['p_new'] ?? '');
    $new2 = (string)($_POST['p_new2'] ?? '');

    if (!check_bitrix_sessid()) {
        $passwordError = 'Истекла сессия, обновите страницу и попробуйте снова';
    } elseif ($new !== $new2) {
        $passwordError = 'Пароли не совпадают';
    } elseif (strlen($new) < 6) {
        $passwordError = 'Новый пароль должен быть не короче 6 символов';
    } elseif ($USER->Login($USER->GetLogin(), $current, 'N') !== true) {
        $passwordError = 'Текущий пароль указан неверно';
    } else {
        $updateResult = \CUser::Update($USER->GetID(), [
            'PASSWORD' => $new,
            'CONFIRM_PASSWORD' => $new2,
        ]);
        if ($updateResult) {
            $passwordOk = true;
        } else {
            global $APPLICATION;
            $passwordError = $APPLICATION->GetException() ? $APPLICATION->GetException()->GetString() : 'Не удалось изменить пароль';
        }
    }
}

$partner = $partnerRepo->get($partnerId);
$documents = $documentRepo->listByPartner($partnerId);

$activeKey = 'partner';
$pageTitle = 'Данные партнёра — Formaro Partner';
$needRichText = true;
require __DIR__ . '/inc/layout_app_top.php';
?>
<script>window.INITIAL_PARTNER = {logo: <?= json_encode($partner['logo']) ?>, image: <?= json_encode($partner['image']) ?>};</script>
<div class="card mb-3">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-patch-check fs-4 text-muted-2"></i>
            <div><div class="fw-bold">Статус верификации</div><div class="text-muted-2 small">Проверка данных компании площадкой</div></div>
        </div>
        <div style="font-size:.95rem;">
            <?php
            $statusMap = [
                'verified' => ['Проверен', 'pill-green'],
                'rejected' => ['Отклонён', 'pill-red'],
                'pending' => ['На проверке', 'pill-yellow'],
            ];
            [$statusLabel, $statusClass] = $statusMap[$partner['verification_status']] ?? $statusMap['pending'];
            ?>
            <span class="pill <?= $statusClass ?>"><?= $statusLabel ?></span>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header"><span>Информация о компании</span></div>
            <ul class="nav nav-tabs product-tabs" id="partnerTabs">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabMain" type="button">Основное</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabDocs" type="button">Документы <span class="side-count" id="docsCountBadge"><?= count($documents) ?></span></button></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="tabMain">
                    <div class="card-body">
                        <form id="partnerForm" onsubmit="return false;">
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">Полное название</label><input type="text" class="form-control" id="f_name_full" data-validate="text" value="<?= htmlspecialcharsbx($partner['name_full']) ?>"></div>
                                <div class="col-md-6 mb-3"><label class="form-label">Краткое название</label><input type="text" class="form-control" id="f_name_short" data-validate="text" value="<?= htmlspecialcharsbx($partner['name_short']) ?>"></div>
                            </div>
                            <div class="mb-3"><label class="form-label">Краткое описание</label><textarea class="form-control autoheight-input" id="f_short_desc" rows="2"><?= htmlspecialcharsbx($partner['short_desc']) ?></textarea></div>
                            <div class="mb-3"><label class="form-label">Полное описание</label><textarea id="f_full_desc"><?= $partner['full_desc'] ?></textarea></div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label d-block">Логотип</label>
                                    <div class="img-field">
                                        <div id="logoImg" class="thumb-logo bg-thumb mb-2" style="background-image:url('<?= htmlspecialcharsbx($partner['logo'] ?: 'https://placehold.co/135x135?text=%20') ?>');"></div>
                                        <div class="img-actions">
                                            <label class="btn btn-outline-secondary mb-0">
                                                <svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"></path><path d="m17 8-5-5-5 5"></path><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path></svg> Загрузить
                                                <input type="file" class="d-none" id="logoFile" accept="image/*">
                                            </label>
                                            <button type="button" class="btn btn-outline-danger" id="logoRemoveBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label class="form-label d-block">Обложка магазина</label>
                                    <div class="img-field">
                                        <div id="coverImg" class="thumb-cover bg-thumb mb-2" style="background-image:url('<?= htmlspecialcharsbx($partner['image'] ?: 'https://placehold.co/560x220?text=%20') ?>');"></div>
                                        <div class="img-actions">
                                            <label class="btn btn-outline-secondary mb-0">
                                                <svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"></path><path d="m17 8-5-5-5 5"></path><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path></svg> Загрузить
                                                <input type="file" class="d-none" id="coverFile" accept="image/*">
                                            </label>
                                            <button type="button" class="btn btn-outline-danger" id="coverRemoveBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h6 class="mb-3">Юридические реквизиты</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">ИНН</label><input type="text" class="form-control" id="f_inn" value="<?= htmlspecialcharsbx($partner['legal']['inn']) ?>"></div>
                                <div class="col-md-6 mb-3"><label class="form-label">ОГРН</label><input type="text" class="form-control" id="f_ogrn" value="<?= htmlspecialcharsbx($partner['legal']['ogrn']) ?>"></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">Банк</label><input type="text" class="form-control" id="f_bank_name" value="<?= htmlspecialcharsbx($partner['legal']['bank_name']) ?>"></div>
                                <div class="col-md-6 mb-3"><label class="form-label">БИК банка</label><input type="text" class="form-control" id="f_bik" value="<?= htmlspecialcharsbx($partner['legal']['bik']) ?>"></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">Расчётный счёт</label><input type="text" class="form-control" id="f_account" value="<?= htmlspecialcharsbx($partner['legal']['account']) ?>"></div>
                                <div class="col-md-6 mb-3"><label class="form-label">Корр. счёт</label><input type="text" class="form-control" id="f_corr_account" value="<?= htmlspecialcharsbx($partner['legal']['corr_account']) ?>"></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">Юридический адрес</label><input type="text" class="form-control" id="f_legal_address" value="<?= htmlspecialcharsbx($partner['legal']['legal_address']) ?>"></div>
                                <div class="col-md-6 mb-3"><label class="form-label">ФИО руководителя</label><input type="text" class="form-control" id="f_ceo_name" value="<?= htmlspecialcharsbx($partner['legal']['ceo_name']) ?>"></div>
                            </div>

                            <hr>
                            <h6 class="mb-3">Контактные данные</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">Телефон</label><input type="text" class="form-control" id="f_phone" data-validate="phone" value="<?= htmlspecialcharsbx($partner['contacts']['phone']) ?>"></div>
                                <div class="col-md-6 mb-3"><label class="form-label">E-mail</label><input type="email" class="form-control" id="f_email" data-validate="email" value="<?= htmlspecialcharsbx($partner['contacts']['email']) ?>"></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">Контактное лицо</label><input type="text" class="form-control" id="f_contact_person" value="<?= htmlspecialcharsbx($partner['contacts']['contact_person']) ?>"></div>
                                <div class="col-md-6 mb-3"><label class="form-label">Должность</label><input type="text" class="form-control" id="f_contact_position" value="<?= htmlspecialcharsbx($partner['contacts']['contact_position']) ?>"></div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="tab-pane fade" id="tabDocs">
                    <div class="card-body">
                        <p class="text-muted-2 small">Загрузите учредительные документы, договоры и другие файлы компании.</p>
                        <div id="docsList" class="mb-3"></div>
                        <label class="btn btn-outline-primary mb-0">
                            <svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"></path><path d="m17 8-5-5-5 5"></path><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path></svg> Загрузить файлы
                            <input type="file" id="docsFile" class="d-none" multiple>
                        </label>
                    </div>
                </div>
            </div>
            <div class="detail-actionbar">
                <button type="button" class="btn btn-primary" id="saveBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"></path><path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"></path><path d="M7 3v4a1 1 0 0 0 1 1h7"></path></svg> Сохранить</button>
            </div>
        </div>
    </div>

    <div class="col-lg-4 sticky-side">
        <div class="card">
            <div class="card-header">Смена пароля</div>
            <div class="card-body">
                <?php if ($passwordOk): ?>
                    <div class="alert alert-success small">Пароль изменён</div>
                <?php elseif ($passwordError): ?>
                    <div class="alert alert-danger small"><?= htmlspecialcharsbx($passwordError) ?></div>
                <?php endif; ?>
                <form method="post">
                    <input type="hidden" name="form" value="password">
                    <?= bitrix_sessid_post() ?>
                    <div class="mb-3"><label class="form-label">Текущий пароль</label><input type="password" name="p_current" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Новый пароль</label><input type="password" name="p_new" class="form-control" id="p_new" required minlength="6" data-validate="password"></div>
                    <div class="mb-3"><label class="form-label">Повтор нового пароля</label><input type="password" name="p_new2" class="form-control" required minlength="6" data-validate-match="#p_new"></div>
                    <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-key"></i> Изменить пароль</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
$pageScripts = ['partner.js'];
require __DIR__ . '/inc/layout_app_bottom.php';
?>
