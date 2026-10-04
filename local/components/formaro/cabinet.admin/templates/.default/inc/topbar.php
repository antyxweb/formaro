<?php
/** @var string $cabinetUrl
 *  @var string $adminUserName */
$svg = static fn(string $paths, string $extra = '') => '<svg class="ic-inline"' . $extra . ' xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg>';
?>
<button class="topbar-burger" id="sidebarToggle"><?= $svg('<path d="M4 5h16"></path><path d="M4 12h16"></path><path d="M4 19h16"></path>') ?></button>
<h1 class="topbar-title" id="topbarTitle">&nbsp;</h1>

<div class="topbar-search d-none d-md-block">
    <?= $svg('<circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path>') ?>
    <input type="text" placeholder="Поиск по партнёрам, категориям, заказам, новостям, обращениям…" id="globalSearch" autocomplete="off">
    <div class="gsearch-dropdown" id="globalSearchResults"></div>
</div>

<div class="topbar-actions">
    <button type="button" class="icon-btn theme-toggle-btn" id="themeToggle" title="Сменить тему">
        <?= $svg('<rect width="20" height="14" x="2" y="3" rx="2"></rect><line x1="8" x2="16" y1="21" y2="21"></line><line x1="12" x2="12" y1="17" y2="21"></line>', ' id="themeIcon"') ?>
    </button>
    <a class="icon-btn" href="<?= $cabinetUrl ?>moderation/" title="Модерация">
        <?= $svg('<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path><path d="m9 12 2 2 4-4"></path>') ?>
        <span class="dot-count" id="moderationDot" style="display:none;">0</span>
    </a>
    <a class="icon-btn" href="<?= $cabinetUrl ?>notifications/" title="Уведомления">
        <?= $svg('<path d="M10.268 21a2 2 0 0 0 3.464 0"></path><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"></path>') ?>
        <span class="dot-count" id="notifDot" style="display:none;">0</span>
    </a>
    <a class="icon-btn" href="<?= $cabinetUrl ?>support/" title="Техподдержка">
        <?= $svg('<circle cx="12" cy="12" r="10"></circle><path d="m4.93 4.93 4.24 4.24"></path><path d="m14.83 9.17 4.24-4.24"></path><path d="m14.83 14.83 4.24 4.24"></path><path d="m9.17 14.83-4.24 4.24"></path><circle cx="12" cy="12" r="4"></circle>') ?>
        <span class="dot-count" id="supportDot" style="display:none;">0</span>
    </a>

    <div class="account-menu">
        <button class="account-btn" id="accountBtn">
            <div class="avatar-sm bg-thumb avatar-initials" id="accountAvatar"><?= htmlspecialcharsbx(mb_strtoupper(mb_substr($adminUserName, 0, 2))) ?></div>
            <span class="acc-name">
                <span class="acc-name-row"><span id="accNameText"><?= htmlspecialcharsbx($adminUserName) ?></span></span>
                <small>Администратор</small>
            </span>
            <?= $svg('<path d="m6 9 6 6 6-6"></path>', ' style="width:14px;height:14px;color:var(--muted);"') ?>
        </button>
        <div class="account-dropdown" id="accountDropdown">
            <a href="/"><?= $svg('<path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"></path><path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>') ?> На сайт</a>
            <a href="/bitrix/admin/"><?= $svg('<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle>') ?> Админка Битрикса</a>
            <hr>
            <a href="<?= $cabinetUrl ?>logout/"><?= $svg('<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" x2="9" y1="12" y2="12"></line>') ?> Выйти</a>
        </div>
    </div>
</div>
