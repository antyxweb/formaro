<?php
/** @var string $activeKey пункт меню, который подсветить
 *  @var string $cabinetUrl базовый URL раздела — "/admin/" */
$icon = static fn(string $paths) => '<svg class="side-ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg>';
$link = static function (string $key, string $href, string $icon, string $label, string $count = '') use ($activeKey) {
    $active = $activeKey === $key ? ' active' : '';
    $countHtml = $count !== '' ? ' <span class="side-count" data-count="' . $count . '" style="display:none;">0</span>' : '';

    return '<a class="side-link' . $active . '" href="' . $href . '">' . $icon . ' ' . $label . $countHtml . '</a>';
};
?>
<div class="sidebar-brand">
    <div class="f-mark"><span></span><span></span><span></span></div>
    <div class="brand-text">formaro <small>Администратор</small></div>
    <button class="sidebar-close-btn" id="sidebarCloseBtn" title="Закрыть меню"><i class="bi bi-x-lg"></i></button>
</div>
<nav class="sidebar-nav">
    <?= $link('dashboard', $cabinetUrl, $icon('<rect width="7" height="9" x="3" y="3" rx="1"></rect><rect width="7" height="5" x="14" y="3" rx="1"></rect><rect width="7" height="9" x="14" y="12" rx="1"></rect><rect width="7" height="5" x="3" y="16" rx="1"></rect>'), 'Главная') ?>
    <?= $link('moderation', $cabinetUrl . 'moderation/', $icon('<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path><path d="m9 12 2 2 4-4"></path>'), 'Модерация', 'moderation') ?>

    <div class="sidebar-section-label">Площадка</div>
    <?= $link('partners', $cabinetUrl . 'partners/', $icon('<path d="M10 12h4"></path><path d="M10 8h4"></path><path d="M14 21v-3a2 2 0 0 0-4 0v3"></path><path d="M6 10H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2"></path><path d="M6 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"></path>'), 'Партнёры') ?>
    <?= $link('categories', $cabinetUrl . 'categories/', $icon('<path d="M20 10a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-2.5a1 1 0 0 1-.8-.4l-.9-1.2A1 1 0 0 0 15 3h-2a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1Z"></path><path d="M20 21a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1h-2.9a1 1 0 0 1-.88-.55l-.42-.85a1 1 0 0 0-.92-.6H13a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1Z"></path><path d="M3 5a2 2 0 0 0 2 2h3"></path><path d="M3 3v13a2 2 0 0 0 2 2h3"></path>'), 'Категории') ?>
    <?= $link('news', $cabinetUrl . 'news/', $icon('<path d="M15 18h-5"></path><path d="M18 14h-8"></path><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-4 0v-9a2 2 0 0 1 2-2h2"></path><rect width="8" height="4" x="10" y="6" rx="1"></rect>'), 'Новости') ?>

    <div class="sidebar-section-label">Продажи</div>
    <?= $link('orders', $cabinetUrl . 'orders/', $icon('<path d="m2.05 2.05 1.099-.028a1 1 0 0 1 1.008.815l2.69 14.347A1 1 0 0 0 7.83 18H18"></path><path d="M4.563 5h16.435a1 1 0 0 1 .981 1.204l-1.026 6.226A2 2 0 0 1 18.962 14H6.25"></path><circle cx="18" cy="20" r="2"></circle><circle cx="8" cy="20" r="2"></circle>'), 'Заказы') ?>
    <?= $link('finance', $cabinetUrl . 'finance/', $icon('<path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"></path><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"></path>'), 'Финансы', 'withdrawals') ?>

    <div class="sidebar-section-label">Коммуникации</div>
    <?= $link('notifications', $cabinetUrl . 'notifications/', $icon('<path d="M10.268 21a2 2 0 0 0 3.464 0"></path><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"></path>'), 'Уведомления', 'notifications') ?>
    <?= $link('support', $cabinetUrl . 'support/', $icon('<circle cx="12" cy="12" r="10"></circle><path d="m4.93 4.93 4.24 4.24"></path><path d="m14.83 9.17 4.24-4.24"></path><path d="m14.83 14.83 4.24 4.24"></path><path d="m9.17 14.83-4.24 4.24"></path><circle cx="12" cy="12" r="4"></circle>'), 'Техподдержка', 'support') ?>
    <?= $link('chat', $cabinetUrl . 'chat/', $icon('<path d="M16 10a2 2 0 0 1-2 2H6.828a2 2 0 0 0-1.414.586l-2.202 2.202A.71.71 0 0 1 2 14.286V4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path><path d="M20 9a2 2 0 0 1 2 2v10.286a.71.71 0 0 1-1.212.502l-2.202-2.202A2 2 0 0 0 17.172 19H10a2 2 0 0 1-2-2v-1"></path>'), 'Чаты с клиентами') ?>
</nav>
