<?php
/** @var array $arResult */
$activeKey = 'notifications';
$pageTitle = 'Уведомления';
require __DIR__ . '/inc/layout_app_top.php';
?>
<?php // Вкладки — как в «Скидках»: лента уведомлений и «Рассылки» (какие из них приходят письмом, подписки на новости). ?>
<ul class="nav nav-tabs product-tabs mb-3" id="mainTabs">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabFeed" type="button">Уведомления</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabMailing" type="button">Рассылки</button></li>
</ul>

<div class="tab-content">
<div class="tab-pane fade show active" id="tabFeed">
<div class="filter-bar d-flex justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <select class="form-select" id="typeFilter" style="min-width:160px;">
            <option value="">Все типы</option>
            <option value="order">Заказы</option>
            <option value="chat">Чат</option>
            <option value="product">Товары</option>
            <option value="finance">Финансы</option>
            <option value="support">Техподдержка</option>
            <option value="system">Система</option>
        </select>
        <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" id="unreadOnlyToggle">
            <label class="form-check-label" for="unreadOnlyToggle">Только непрочитанные</label>
        </div>
    </div>
    <button class="btn btn-light" id="markAllBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 7 17l-5-5"></path><path d="m22 10-7.5 7.5L13 16"></path></svg> Прочитать все <span id="unreadCountLabel"></span></button>
</div>

<div class="card" id="notifCard">
    <div class="card-body p-0">
        <?php // Групповые действия — с отмеченными на текущей странице. ?>
        <div class="notif-toolbar">
            <div class="form-check mb-0">
                <input type="checkbox" class="form-check-input" id="checkAllNotif">
                <label class="form-check-label" for="checkAllNotif">Выбрать все</label>
            </div>
            <span class="text-muted-2 small d-none" id="selectedCountLabel"></span>
            <button type="button" class="btn btn-light btn-sm d-none" id="bulkReadBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg> Прочитать</button>
            <button type="button" class="btn btn-outline-danger btn-sm d-none" id="bulkDeleteBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg> Удалить</button>
        </div>
        <div id="listBody">
            <div class="text-center text-muted-2 py-4">Загрузка…</div>
        </div>
        <div class="table-footer-bar">
            <div class="tf-left"><span class="tf-count"></span></div>
            <div class="tf-right">
                <div class="tf-pagesize">Показывать
                    <select class="form-select tf-pagesize-select">
                        <option value="20">20</option><option value="50">50</option><option value="100">100</option><option value="500">500</option>
                    </select>
                </div>
                <div class="tf-pager"></div>
            </div>
        </div>
    </div>
</div>
</div>

<div class="tab-pane fade" id="tabMailing">
    <div class="card mb-3" id="mailingCard">
        <div class="card-header">Письма на e-mail</div>
        <div class="card-body">
            <p class="small text-muted-2 mb-3">Письма приходят на <b id="mailingEmail"></b> — e-mail из вкладки «Авторизация» профиля. Выберите, о каких событиях сообщать письмом; в ленте «Уведомления» они появляются в любом случае. Служебные письма (восстановление пароля, счета по заказам) приходят всегда.</p>
            <div id="mailingChannels"><div class="text-muted-2">Загрузка…</div></div>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-header">Новости и акции</div>
        <div class="card-body p-0">
            <div id="mailingNewsletters"></div>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <button type="button" class="btn btn-outline-danger" id="mailingUnsubscribeAllBtn">Отписаться от всех рассылок</button>
        <span class="small text-muted-2">Уведомления покупателя (о ваших покупках) — в <a href="/personal/profile/" target="_blank">профиле на сайте</a>.</span>
    </div>
</div>
</div>
<?php
$pageScripts = ['notifications.js', 'mailing.js'];
require __DIR__ . '/inc/layout_app_bottom.php';
