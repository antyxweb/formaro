<?php

use Formaro\Cabinet\Security\PartnerContext;

/** @var array $arResult */
global $USER;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
    $login = trim((string)$_POST['email']);
    $password = (string)$_POST['password'];

    $result = $USER->Login($login, $password, 'Y');
    if ($result !== true) {
        $error = 'Неверный e-mail или пароль';
    } elseif (!PartnerContext::hasAccess()) {
        $USER->Logout();
        $error = 'У этой учётной записи нет доступа в кабинет партнёра';
    } else {
        LocalRedirect($arResult['SEF_FOLDER']);
        die();
    }
}

$pageTitle = 'Вход — Formaro Partner';
require __DIR__ . '/inc/layout_auth_top.php';
?>
    <p class="text-muted-2 mb-4">Кабинет партнёра маркетплейса</p>
    <?php if ($error): ?>
        <div class="alert alert-danger small"><?= htmlspecialcharsbx($error) ?></div>
    <?php endif; ?>
    <form method="post" id="loginForm">
        <div class="mb-3">
            <label class="form-label">E-mail</label>
            <input type="email" name="email" class="form-control" id="email" value="<?= htmlspecialcharsbx($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Пароль</label>
            <input type="password" name="password" class="form-control" id="password" required>
        </div>
        <div class="text-end mb-3"><a href="<?= $arResult['SEF_FOLDER'] ?>forgot-password/" class="small">Забыли пароль?</a></div>
        <button type="submit" class="btn btn-primary w-100" id="loginBtn">Войти</button>
    </form>
<?php require __DIR__ . '/inc/layout_auth_bottom.php'; ?>
