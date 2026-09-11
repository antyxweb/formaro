<?php
/** @var array $arResult */
$activeKey = 'notifications';
$pageTitle = 'Уведомления — Formaro Partner';
require __DIR__ . '/inc/layout_app_top.php';
?>
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
<?php
$pageScripts = ['notifications.js'];
require __DIR__ . '/inc/layout_app_bottom.php';
