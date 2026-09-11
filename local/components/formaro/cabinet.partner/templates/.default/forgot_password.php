<?php

/** @var array $arResult */
global $USER;

$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
    $email = trim((string)$_POST['email']);
    // Намеренно не показываем, найден ли такой e-mail в системе — тот же
    // приватность-ориентированный UX, что был в прототипе (forgot-password.html):
    // экран "проверьте почту" показываем в любом случае, независимо от результата.
    $USER->SendPassword($email, $email);
    $sent = true;
}

$pageTitle = 'Восстановление пароля — Formaro Partner';
require __DIR__ . '/inc/layout_auth_top.php';

if ($sent): ?>
    <div class="text-center py-2">
        <i class="bi bi-envelope-check" style="font-size:2.5rem;color:var(--brand);"></i>
        <p class="fw-bold mt-3 mb-2">Проверьте почту</p>
        <p class="small text-muted-2 mb-4">Если такой e-mail зарегистрирован в системе, на него отправлено письмо со ссылкой для восстановления пароля.</p>
        <a href="<?= $arResult['SEF_FOLDER'] ?>login/" class="btn btn-outline-primary w-100">Вернуться ко входу</a>
    </div>
<?php else: ?>
    <p class="text-muted-2 mb-4">Восстановление пароля</p>
    <p class="small text-muted-2 mb-3">Укажите e-mail, привязанный к кабинету партнёра — мы отправим на него ссылку для сброса пароля.</p>
    <form method="post">
        <div class="mb-3">
            <label class="form-label">E-mail</label>
            <input type="email" name="email" class="form-control" id="email" data-validate="email" placeholder="you@company.ru" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Отправить ссылку</button>
    </form>
    <div class="text-center mt-3"><a href="<?= $arResult['SEF_FOLDER'] ?>login/" class="small"><i class="bi bi-arrow-left"></i> Вернуться ко входу</a></div>
<?php endif;
require __DIR__ . '/inc/layout_auth_bottom.php'; ?>
