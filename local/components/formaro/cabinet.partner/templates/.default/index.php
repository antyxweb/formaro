<?php

use Formaro\Cabinet\Repository\CategoryRepository;
use Formaro\Cabinet\Repository\PartnerRepository;
use Formaro\Cabinet\Repository\ProductRepository;

/** @var array $arResult */
$partnerId = $arResult['PARTNER_ID'];

$partner = (new PartnerRepository())->get($partnerId);
$ownCategories = array_filter((new CategoryRepository())->listVisible($partnerId), static fn($c) => !$c['is_system']);
$ownProducts = (new ProductRepository())->listOwn($partnerId);

$activeKey = 'dashboard';
$pageTitle = 'Главная — Formaro Partner';
require __DIR__ . '/inc/layout_app_top.php';
?>
<div class="mb-3">
    <h4 class="mb-1">Здравствуйте, <?= htmlspecialcharsbx($partner['name_short'] ?: $partner['name_full']) ?>!</h4>
    <p class="text-muted-2">Кабинет партнёра сейчас на первой очереди — доступны каталог (категории и товары)
        и профиль. Заказы, скидки, финансы, новости и поддержка появятся в следующих обновлениях.</p>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <a class="text-decoration-none" href="<?= $arResult['SEF_FOLDER'] ?>categories/">
            <div class="stat-tile grad-blue">
                <svg class="stat-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-2.5a1 1 0 0 1-.8-.4l-.9-1.2A1 1 0 0 0 15 3h-2a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1Z"></path><path d="M20 21a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1h-2.9a1 1 0 0 1-.88-.55l-.42-.85a1 1 0 0 0-.92-.6H13a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1Z"></path><path d="M3 5a2 2 0 0 0 2 2h3"></path><path d="M3 3v13a2 2 0 0 0 2 2h3"></path></svg>
                <div><div class="stat-value"><?= count($ownCategories) ?></div><div class="stat-label">Своих категорий</div></div>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <a class="text-decoration-none" href="<?= $arResult['SEF_FOLDER'] ?>products/">
            <div class="stat-tile grad-green">
                <svg class="stat-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"></path><path d="M12 22V12"></path><polyline points="3.29 7 12 12 20.71 7"></polyline><path d="m7.5 4.27 9 5.15"></path></svg>
                <div><div class="stat-value"><?= count($ownProducts) ?></div><div class="stat-label">Товаров</div></div>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-tile grad-orange">
            <svg class="stat-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"></path></svg>
            <div><div class="stat-value"><?= count(array_filter($ownProducts, static fn($p) => $p['status'] === 'active')) ?></div><div class="stat-label">Активных товаров</div></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-tile grad-purple">
            <svg class="stat-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.985 12.486a9 9 0 1 1-9.473-9.472"></path></svg>
            <div><div class="stat-value"><?= htmlspecialcharsbx(ucfirst($partner['verification_status'] === 'verified' ? 'Проверен' : ($partner['verification_status'] === 'rejected' ? 'Отклонён' : 'На проверке'))) ?></div><div class="stat-label">Статус партнёра</div></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><span>Последние товары</span></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Товар</th><th>Артикул</th><th>Цена</th><th>Статус</th></tr></thead>
                <tbody>
                <?php if (!$ownProducts): ?>
                    <tr><td colspan="4" class="text-center text-muted-2 py-4">Товаров пока нет — <a href="<?= $arResult['SEF_FOLDER'] ?>products/edit/new/">добавьте первый</a>.</td></tr>
                <?php else: foreach (array_slice($ownProducts, 0, 8) as $p): ?>
                    <tr>
                        <td><a href="<?= $arResult['SEF_FOLDER'] ?>products/edit/<?= $p['id'] ?>/"><?= htmlspecialcharsbx($p['name']) ?></a></td>
                        <td class="text-muted-2 small"><?= htmlspecialcharsbx($p['sku'] ?: '—') ?></td>
                        <td><?= number_format($p['price'], 0, ',', ' ') ?> ₽</td>
                        <td><?= $p['status'] === 'active' ? '<span class="pill pill-green">Активен</span>' : '<span class="pill pill-gray">Скрыт</span>' ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer-bar justify-content-end">
            <a href="<?= $arResult['SEF_FOLDER'] ?>products/" class="small">Все товары →</a>
        </div>
    </div>
</div>
<?php require __DIR__ . '/inc/layout_app_bottom.php'; ?>
