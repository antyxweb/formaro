<?php
/**
 * Модерация — общая очередь: категории партнёров на проверке и партнёры,
 * ждущие проверки (прислали данные или новые после регистрации).
 *
 * @var array $arResult
 */
$activeKey = 'moderation';
$pageTitle = 'Модерация';
require __DIR__ . '/inc/layout_app_top.php';
?>
<div class="filter-bar d-flex flex-wrap gap-2 align-items-end">
    <div><label class="form-label small mb-1">Партнёр</label>
        <select class="form-select" id="partnerFilter" style="min-width:240px;"></select></div>
</div>

<ul class="nav nav-tabs product-tabs mb-0" id="moderationTabs">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabCategories" type="button">Категории <span class="side-count" id="countCategories" style="display:none;">0</span></button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabPartners" type="button">Партнёры <span class="side-count" id="countPartners" style="display:none;">0</span></button></li>
</ul>
<div class="card" style="border-top-left-radius:0;">
    <div class="tab-content">
        <div class="tab-pane fade show active" id="tabCategories">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead><tr><th>Категория</th><th>Родительский раздел</th><th>Партнёр</th><th>Описание</th><th class="text-end">Решение</th></tr></thead>
                    <tbody id="categoriesBody"><tr><td colspan="5" class="text-center text-muted-2 py-4">Загрузка…</td></tr></tbody>
                </table>
            </div>
        </div>
        <div class="tab-pane fade" id="tabPartners">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead><tr><th>Партнёр</th><th>ИНН</th><th>Что проверить</th><th class="text-end"></th></tr></thead>
                    <tbody id="partnersBody"><tr><td colspan="4" class="text-center text-muted-2 py-4">Загрузка…</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php
$pageScripts = ['moderation.js'];
require __DIR__ . '/inc/layout_app_bottom.php';
