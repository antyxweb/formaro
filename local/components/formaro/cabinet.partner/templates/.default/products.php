<?php
/** @var array $arResult */
$activeKey = 'products';
$pageTitle = 'Товары — Formaro Partner';
require __DIR__ . '/inc/layout_app_top.php';
?>
<div class="filter-bar d-flex flex-wrap gap-2 align-items-end justify-content-between">
    <div class="d-flex flex-wrap gap-2 align-items-end">
        <div><label class="form-label small mb-1">Поиск</label>
            <input type="text" class="form-control" id="qInput" placeholder="Название или артикул" style="min-width:200px;"></div>
        <div><label class="form-label small mb-1">Категория</label>
            <select class="form-select" id="catFilter" style="min-width:180px;"><option value="0">Все категории</option></select></div>
        <div><label class="form-label small mb-1">Статус</label>
            <select class="form-select" id="statusFilter"><option value="">Все</option><option value="active">Активен</option><option value="hidden">Скрыт</option></select></div>
        <button class="btn btn-outline-secondary" id="findBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 21-4.34-4.34"></path><circle cx="11" cy="11" r="8"></circle></svg></button>
        <button class="btn btn-outline-secondary filter-clear-btn" id="clearBtn" title="Сбросить фильтры"><i class="bi bi-x-lg"></i></button>
    </div>
    <a href="<?= $arResult['SEF_FOLDER'] ?>products/edit/new/" class="btn btn-success"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg> Добавить товар</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle" id="productsTable">
                <thead><tr>
                    <th class="row-check"><input type="checkbox" class="form-check-input" id="checkAll"></th>
                    <th>Название</th><th>Артикул</th><th>Категория</th><th>Цена</th><th>Остаток</th><th>Статус</th><th class="text-end">Действия</th>
                </tr></thead>
                <tbody id="productsBody"><tr><td colspan="8" class="text-center text-muted-2 py-4">Загрузка…</td></tr></tbody>
            </table>
        </div>
        <div class="table-footer-bar">
            <div class="tf-left">
                <button class="btn btn-outline-danger btn-sm d-none" id="bulkDeleteBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg> Удалить выбранные</button>
                <span class="tf-count"></span>
            </div>
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
$pageScripts = ['products.js'];
require __DIR__ . '/inc/layout_app_bottom.php';
