<?php
/**
 * Заказы всех партнёров — просмотр с фильтрами (партнёр, статус, оплата,
 * поиск). Заказ ведёт партнёр в своём кабинете; здесь — карточка заказа
 * в окне (?id=ID — открыть сразу).
 *
 * @var array $arResult
 */
$activeKey = 'orders';
$pageTitle = 'Заказы';
require __DIR__ . '/inc/layout_app_top.php';
?>
<div class="filter-bar d-flex flex-wrap gap-2 align-items-end">
    <div><label class="form-label small mb-1">Поиск</label>
        <input type="text" class="form-control" id="qInput" placeholder="№ заказа, клиент, телефон" style="min-width:220px;"></div>
    <div><label class="form-label small mb-1">Партнёр</label>
        <select class="form-select" id="partnerFilter" style="min-width:200px;"></select></div>
    <div><label class="form-label small mb-1">Статус</label>
        <select class="form-select" id="statusFilter">
            <option value="">Все</option>
            <option value="new">Новый</option>
            <option value="processing">В обработке</option>
            <option value="confirmed">Подтверждён</option>
            <option value="shipped">Отправлен</option>
            <option value="completed">Выполнен</option>
            <option value="cancelled">Отменён</option>
        </select></div>
    <div><label class="form-label small mb-1">Оплата</label>
        <select class="form-select" id="paymentFilter">
            <option value="">Все</option>
            <option value="awaiting">Ожидает оплаты</option>
            <option value="paid">Оплачен</option>
            <option value="refunded">Возврат</option>
        </select></div>
    <button class="btn btn-outline-secondary filter-clear-btn" id="clearBtn" title="Сбросить фильтры"><i class="bi bi-x-lg"></i></button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle" id="ordersTable">
                <thead><tr><th>№</th><th>Дата</th><th>Партнёр</th><th>Клиент</th><th>Сумма</th><th>Оплата</th><th>Статус</th></tr></thead>
                <tbody id="ordersBody"><tr><td colspan="7" class="text-center text-muted-2 py-4">Загрузка…</td></tr></tbody>
            </table>
        </div>
        <div class="table-footer-bar">
            <div class="tf-left"><span class="tf-count"></span> <span class="small text-muted-2 ms-2" id="ordersTotal"></span></div>
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

<div class="modal fade" id="orderModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="orderModalTitle">Заказ</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="orderModalBody"></div>
</div></div></div>
<?php
$pageScripts = ['orders.js'];
require __DIR__ . '/inc/layout_app_bottom.php';
