<?php
/**
 * Категории каталога: общие категории площадки и партнёрские — все сразу,
 * фильтр по владельцу (площадка / партнёр) и статусу. Администратор
 * редактирует любые, создаёт общие, допускает/отклоняет партнёрские.
 *
 * @var array $arResult
 */
$activeKey = 'categories';
$pageTitle = 'Категории';
require __DIR__ . '/inc/layout_app_top.php';
?>
<div class="filter-bar d-flex flex-wrap gap-2 align-items-end justify-content-between">
    <div class="d-flex flex-wrap gap-2 align-items-end">
        <div><label class="form-label small mb-1">Поиск по названию</label>
            <input type="text" class="form-control" id="qInput" placeholder="Например: Обувь" style="min-width:200px;"></div>
        <div><label class="form-label small mb-1">Владелец</label>
            <select class="form-select" id="partnerFilter" style="min-width:200px;"></select></div>
        <div><label class="form-label small mb-1">Статус</label>
            <select class="form-select" id="statusFilter">
                <option value="">Все</option>
                <option value="active">Активен</option>
                <option value="hidden">Скрыт</option>
                <option value="pending">На проверке</option>
                <option value="rejected">Отклонён</option>
            </select></div>
        <button class="btn btn-outline-secondary" id="findBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 21-4.34-4.34"></path><circle cx="11" cy="11" r="8"></circle></svg></button>
        <button class="btn btn-outline-secondary filter-clear-btn" id="clearBtn" title="Сбросить фильтры"><i class="bi bi-x-lg"></i></button>
    </div>
    <a href="<?= $arResult['SEF_FOLDER'] ?>categories/edit/new/" class="btn btn-success"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg> Добавить категорию</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle" id="categoriesTable">
                <thead><tr>
                    <th>Название</th><th>Родительский раздел</th><th>Владелец</th><th>Статус</th><th>Товаров</th><th class="text-end">Действия</th>
                </tr></thead>
                <tbody id="categoriesBody"><tr><td colspan="6" class="text-center text-muted-2 py-4">Загрузка…</td></tr></tbody>
            </table>
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
$pageScripts = ['categories.js'];
require __DIR__ . '/inc/layout_app_bottom.php';
