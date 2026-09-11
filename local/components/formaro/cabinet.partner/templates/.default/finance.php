<?php
/** @var array $arResult */
$activeKey = 'finance';
$pageTitle = 'Финансы — Formaro Partner';
$needDatePicker = true;
require __DIR__ . '/inc/layout_app_top.php';
?>
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="stat-tile grad-green"><svg class="stat-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"></path><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"></path></svg>
            <div><div class="stat-value" id="statAvailable">—</div><div class="stat-label">Доступно к выводу</div></div></div>
    </div>
    <div class="col-md-4">
        <div class="stat-tile grad-orange"><svg class="stat-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6h4"></path></svg>
            <div><div class="stat-value" id="statPending">—</div><div class="stat-label">В обработке</div></div></div>
    </div>
    <div class="col-md-4">
        <div class="stat-tile grad-blue"><svg class="stat-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 17V3"></path><path d="m6 11 6 6 6-6"></path><path d="M19 21H5"></path></svg>
            <div><div class="stat-value" id="statTotal">—</div><div class="stat-label">Заработано всего</div></div></div>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <button class="btn btn-primary" id="withdrawBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 17V3"></path><path d="m6 11 6 6 6-6"></path><path d="M19 21H5"></path></svg> Запросить вывод средств</button>
    <button class="btn btn-outline-secondary" id="docsBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"></path><path d="M14 2v5a1 1 0 0 0 1 1h5"></path><path d="M10 9H8"></path><path d="M16 13H8"></path><path d="M16 17H8"></path></svg> Запросить документы</button>
</div>

<div class="filter-bar d-flex flex-wrap gap-2 align-items-end">
    <div><label class="form-label small mb-1">Тип</label>
        <select class="form-select" id="typeFilter"><option value="">Все операции</option><option value="income">Поступление</option><option value="commission">Списание</option><option value="refund">Возврат</option><option value="withdrawal">Вывод</option></select></div>
    <div><label class="form-label small mb-1">Период</label><input type="text" class="form-control" id="dateRange" placeholder="Выберите даты" autocomplete="off" style="min-width:200px;"></div>
    <button class="btn btn-outline-secondary" id="findBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 21-4.34-4.34"></path><circle cx="11" cy="11" r="8"></circle></svg></button>
    <button class="btn btn-outline-secondary filter-clear-btn" id="clearBtn" title="Сбросить фильтры"><i class="bi bi-x-lg"></i></button>
</div>

<div class="card">
    <div class="card-header">История операций</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle" id="financeTable">
                <thead><tr><th>Дата</th><th>Тип</th><th>Описание</th><th class="text-end">Сумма</th><th>Статус</th></tr></thead>
                <tbody id="txBody"><tr><td colspan="5" class="text-center text-muted-2 py-4">Загрузка…</td></tr></tbody>
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

<div class="modal fade" id="withdrawModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Запрос на вывод средств</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <p class="text-muted-2 small">Средства будут переведены на расчётный счёт, указанный в реквизитах партнёра.</p>
    <label class="form-label">Сумма к выводу, ₽</label>
    <input type="number" class="form-control" id="withdrawAmount" min="1">
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button><button type="button" class="btn btn-primary" id="withdrawConfirmBtn">Отправить запрос</button></div>
</div></div></div>

<div class="modal fade" id="docsModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Запрос документов</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <label class="form-label">Тип документа</label>
    <select class="form-select mb-3" id="docType">
      <option>Акт сверки</option><option>Отчёт агента</option><option>Счёт-фактура</option><option>Иное</option>
    </select>
    <label class="form-label">Комментарий</label>
    <textarea class="form-control" id="docComment" rows="3" placeholder="За какой период нужны документы"></textarea>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button><button type="button" class="btn btn-primary" id="docsConfirmBtn">Отправить запрос</button></div>
</div></div></div>
<?php
$pageScripts = ['finance.js'];
require __DIR__ . '/inc/layout_app_bottom.php';
