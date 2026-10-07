<?php
/** @var array $arResult */
$activeKey = 'finance';
$pageTitle = 'Финансы';
$needDatePicker = true;
require __DIR__ . '/inc/layout_app_top.php';
?>
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="stat-tile grad-green"><svg class="stat-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"></path><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"></path></svg>
            <div><div class="stat-value" id="statBalance">—</div><div class="stat-label">Текущий баланс</div></div></div>
    </div>
    <div class="col-md-4">
        <div class="stat-tile grad-orange"><svg class="stat-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"></path><path d="M15 18H9"></path><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"></path><circle cx="17" cy="18" r="2"></circle><circle cx="7" cy="18" r="2"></circle></svg>
            <div><div class="stat-value" id="statDue">—</div><div class="stat-label">К выплате сегодня</div></div></div>
    </div>
    <div class="col-md-4">
        <div class="stat-tile grad-blue"><svg class="stat-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 17V3"></path><path d="m6 11 6 6 6-6"></path><path d="M19 21H5"></path></svg>
            <div><div class="stat-value" id="statPaidOut">—</div><div class="stat-label">Выплачено всего</div></div></div>
    </div>
</div>

<?php // Выплата по факту отгрузки (ТЗ «Финансы») — заявок на вывод нет, выплаты инициирует площадка (PayoutService). ?>
<div class="alert alert-info d-flex gap-2 align-items-start mb-3">
    <i class="bi bi-info-circle mt-1"></i>
    <div class="small"><b>Текущий баланс</b> — всё, что клиенты оплатили и что причитается вам: деньги ждут на счёте площадки.<br>
        <b>Выплата по факту отгрузки:</b> соберите заказ, передайте его в транспортную компанию и укажите трек-номер в заказе — в тот же день площадка переведёт сумму заказа на ваш расчётный счёт.</div>
</div>

<div class="card mb-3" id="payoutsTable">
    <div class="card-header">Выплаты по заказам</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead><tr><th>Заказ</th><th class="text-end">Сумма</th><th>Отгрузка</th><th>Выплата</th></tr></thead>
                <tbody id="payoutsBody"><tr><td colspan="4" class="text-center text-muted-2 py-4">Загрузка…</td></tr></tbody>
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

<div class="d-flex flex-wrap gap-2 mb-3">
    <button class="btn btn-outline-secondary" id="docsBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"></path><path d="M14 2v5a1 1 0 0 0 1 1h5"></path><path d="M10 9H8"></path><path d="M16 13H8"></path><path d="M16 17H8"></path></svg> Запросить документы</button>
</div>

<div class="filter-bar d-flex flex-wrap gap-2 align-items-end">
    <div><label class="form-label small mb-1">Тип</label>
        <select class="form-select" id="typeFilter"><option value="">Все операции</option><option value="income">Оплата заказа</option><option value="refund">Возврат</option><option value="payout">Выплата</option></select></div>
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
