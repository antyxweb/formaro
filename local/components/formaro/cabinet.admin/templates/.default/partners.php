<?php
/**
 * Партнёры площадки: статус проверки, показ на сайте, присланные на
 * проверку изменения. Карточка партнёра — модерация (partners/edit/ID/).
 *
 * @var array $arResult
 */
$activeKey = 'partners';
$pageTitle = 'Партнёры';
require __DIR__ . '/inc/layout_app_top.php';
?>
<div class="filter-bar d-flex flex-wrap gap-2 align-items-end">
    <div><label class="form-label small mb-1">Поиск</label>
        <input type="text" class="form-control" id="qInput" placeholder="Название или ИНН" style="min-width:220px;"></div>
    <div><label class="form-label small mb-1">Проверка</label>
        <select class="form-select" id="statusFilter">
            <option value="">Все</option>
            <option value="moderation">Ждут проверки</option>
            <option value="not_verified">Не проверен</option>
            <option value="pending">На проверке</option>
            <option value="verified">Проверен</option>
            <option value="rejected">Отклонён</option>
        </select></div>
    <div><label class="form-label small mb-1">На сайте</label>
        <select class="form-select" id="activeFilter">
            <option value="">Все</option>
            <option value="Y">Показывается</option>
            <option value="N">Скрыт</option>
        </select></div>
    <button class="btn btn-outline-secondary filter-clear-btn" id="clearBtn" title="Сбросить фильтры"><i class="bi bi-x-lg"></i></button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle" id="partnersTable">
                <thead><tr><th>Партнёр</th><th>ИНН</th><th>Проверка</th><th>На сайте</th><th>Зарегистрирован</th><th class="text-end">Действия</th></tr></thead>
                <tbody id="partnersBody"><tr><td colspan="6" class="text-center text-muted-2 py-4">Загрузка…</td></tr></tbody>
            </table>
        </div>
        <div class="table-footer-bar">
            <div class="tf-left"><span class="tf-count"></span></div>
            <div class="tf-right">
                <div class="tf-pagesize">Показывать
                    <select class="form-select tf-pagesize-select">
                        <option value="20">20</option><option value="50">50</option><option value="100">100</option>
                    </select>
                </div>
                <div class="tf-pager"></div>
            </div>
        </div>
    </div>
</div>
<?php
$pageScripts = ['partners.js'];
require __DIR__ . '/inc/layout_app_bottom.php';
