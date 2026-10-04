<?php
/**
 * Техподдержка — сторона площадки: обращения всех партнёров. Сначала те,
 * что ждут ответа (статус «Открыт»).
 *
 * @var array $arResult
 */
$activeKey = 'support';
$pageTitle = 'Техподдержка';
require __DIR__ . '/inc/layout_app_top.php';
?>
<div class="filter-bar d-flex flex-wrap gap-2 align-items-end">
    <div><label class="form-label small mb-1">Поиск по теме</label>
        <input type="text" class="form-control" id="qInput" placeholder="Например: комиссия" style="min-width:220px;"></div>
    <div><label class="form-label small mb-1">Партнёр</label>
        <select class="form-select" id="partnerFilter" style="min-width:200px;"></select></div>
    <div><label class="form-label small mb-1">Статус</label>
        <select class="form-select" id="statusFilter">
            <option value="">Все статусы</option>
            <option value="open">Ждут ответа</option>
            <option value="answered">Отвечен</option>
            <option value="closed">Закрыт</option>
        </select></div>
    <button class="btn btn-outline-secondary filter-clear-btn" id="clearBtn" title="Сбросить фильтры"><i class="bi bi-x-lg"></i></button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle" id="ticketsTable">
                <thead><tr><th>Тема</th><th>Партнёр</th><th>Статус</th><th>Сообщений</th><th>Последнее сообщение</th><th>Создано</th></tr></thead>
                <tbody id="ticketsBody"><tr><td colspan="6" class="text-center text-muted-2 py-4">Загрузка…</td></tr></tbody>
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
$pageScripts = ['support.js'];
require __DIR__ . '/inc/layout_app_bottom.php';
