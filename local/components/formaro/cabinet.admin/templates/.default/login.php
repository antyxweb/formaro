<?php

use Formaro\Cabinet\Security\AdminContext;

/** @var array $arResult */
global $USER;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password']) && check_bitrix_sessid()) {
    $email = trim((string)$_POST['email']);

    // Вход по e-mail или логину — как в кабинете партнёра (login.php там).
    $login = $email;
    $byEmail = CUser::GetList($by = 'id', $order = 'asc', ['=EMAIL' => $email], ['SELECT' => ['ID', 'LOGIN']])->Fetch();
    if ($byEmail) {
        $login = $byEmail['LOGIN'];
    }

    $result = $USER->Login($login, (string)$_POST['password'], 'Y');
    if ($result !== true) {
        $error = 'Неверный e-mail или пароль';
    } elseif (!AdminContext::hasAccess()) {
        $USER->Logout();
        $error = 'Кабинет доступен только администраторам площадки';
    } else {
        LocalRedirect($arResult['SEF_FOLDER']);
        die();
    }
}

$pageTitle = 'Вход для администратора';
require $_SERVER['DOCUMENT_ROOT'] . '/local/components/formaro/cabinet.partner/templates/.default/inc/layout_auth_top.php';
?>
    <p class="text-muted-2 mb-4">Кабинет администратора площадки</p>
    <?php if ($error): ?>
        <div class="alert alert-danger small"><?= htmlspecialcharsbx($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <?= bitrix_sessid_post() ?>
        <div class="mb-3">
            <label class="form-label" for="email">E-mail или логин</label>
            <input type="text" name="email" class="form-control" id="email" value="<?= htmlspecialcharsbx($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">Пароль</label>
            <input type="password" name="password" class="form-control" id="password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Войти</button>
    </form>
<?php require $_SERVER['DOCUMENT_ROOT'] . '/local/components/formaro/cabinet.partner/templates/.default/inc/layout_auth_bottom.php'; ?>
