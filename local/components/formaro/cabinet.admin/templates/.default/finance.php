<?php
/**
 * Финансы — сторона площадки: выплаты поставщикам по трек-номеру
 * (PayoutService), все операции и балансы партнёров, наценка.
 *
 * @var array $arResult
 */
$activeKey = 'finance';
$pageTitle = 'Финансы';
require __DIR__ . '/inc/layout_app_top.php';
$pager = static fn(string $key) => '<div class="table-footer-bar"><div class="tf-left"><span class="tf-count"></span></div><div class="tf-right"><div class="tf-pagesize">Показывать <select class="form-select tf-pagesize-select"><option value="20">20</option><option value="50">50</option><option value="100">100</option></select></div><div class="tf-pager"></div></div></div>';
?>
<div class="filter-bar d-flex flex-wrap gap-2 align-items-end">
    <div><label class="form-label small mb-1">Партнёр</label>
        <select class="form-select" id="partnerFilter" style="min-width:240px;"></select></div>
    <?php // Фильтры вкладок — видны только на своей вкладке (finance.js, data-tab). ?>
    <div data-tab="tabPayouts"><label class="form-label small mb-1" for="payoutStateFilter">Выплата</label>
        <select class="form-select" id="payoutStateFilter" style="min-width:200px;">
            <option value="due">К выплате сегодня</option>
            <option value="awaiting_track">Ждут трек-номер</option>
            <option value="paid">Выплачено</option>
            <option value="">Все оплаченные заказы</option>
        </select></div>
    <div data-tab="tabTransactions" class="d-none"><label class="form-label small mb-1" for="typeFilter">Операции</label>
        <select class="form-select" id="typeFilter" style="min-width:200px;">
            <option value="">Все операции</option>
            <option value="income">Оплаты заказов</option>
            <option value="refund">Возвраты</option>
            <option value="payout">Выплаты</option>
        </select></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="admin-tile"><span class="admin-tile__value" id="tileDueSum">—</span><span class="admin-tile__label">К выплате сегодня (<span id="tileDue">0</span>)</span></div></div>
    <div class="col-6 col-lg-3"><div class="admin-tile"><span class="admin-tile__value" id="tileAwaitingSum">—</span><span class="admin-tile__label">Ждут отгрузки (<span id="tileAwaiting">0</span>)</span></div></div>
    <div class="col-6 col-lg-3"><div class="admin-tile"><span class="admin-tile__value" id="tileBalance">—</span><span class="admin-tile__label">Текущий баланс поставщиков</span></div></div>
    <div class="col-6 col-lg-3"><div class="admin-tile"><span class="admin-tile__value" id="tileWithdrawn">—</span><span class="admin-tile__label">Выплачено всего</span></div></div>
</div>

<?php // Наценка площадки и минимальный заказ (MarkupService) — finance.js. ?>
<div class="card mb-3">
    <div class="card-header"><span>Наценка площадки и минимальный заказ</span></div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-3 align-items-end">
            <div><label class="form-label small mb-1" for="markupRate">Наценка на цену поставщика, %</label>
                <input type="number" class="form-control" id="markupRate" min="7" step="0.5" style="width:140px;"></div>
            <div><label class="form-label small mb-1" for="minOrder">Минимальный заказ у поставщика, руб.</label>
                <input type="number" class="form-control" id="minOrder" min="0" step="100" style="width:200px;"></div>
            <button type="button" class="btn btn-primary" id="saveMarkupBtn">Сохранить</button>
        </div>
        <div class="form-text" id="markupHint">Цена на сайте = цена поставщика + наценка, округляется вверх до рубля. Поставщик видит только свою цену. Скидки поставщика считаются от его цены — комиссия площадки не меняется. Если товаров одного поставщика в корзине меньше чем на минимальный заказ, оформить заказ нельзя.</div>
    </div>
</div>

<ul class="nav nav-tabs product-tabs mb-0">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabPayouts" type="button">Выплаты <span class="side-count" id="countDue" style="display:none;">0</span></button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabTransactions" type="button">Операции</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabBalances" type="button">Балансы партнёров</button></li>
</ul>
<div class="card" style="border-top-left-radius:0;">
    <div class="tab-content">
        <div class="tab-pane fade show active" id="tabPayouts">
            <div class="card border-0 rounded-0 shadow-none" id="payoutsTable"><div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead><tr><th>Заказ</th><th>Партнёр</th><th>Поставщику</th><th>Отгрузка</th><th>Реквизиты</th><th>Выплата</th><th class="text-end"></th></tr></thead>
                    <tbody id="payoutsBody"><tr><td colspan="7" class="text-center text-muted-2 py-4">Загрузка…</td></tr></tbody>
                </table></div>
                <?= $pager('payouts') ?>
            </div>
        </div>
        <div class="tab-pane fade" id="tabTransactions">
            <div class="card border-0 rounded-0 shadow-none" id="transactionsTable"><div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead><tr><th>Дата</th><th>Партнёр</th><th>Тип</th><th>Описание</th><th>Сумма</th><th>Статус</th></tr></thead>
                    <tbody id="transactionsBody"></tbody>
                </table></div>
                <?= $pager('transactions') ?>
            </div>
        </div>
        <div class="tab-pane fade" id="tabBalances">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead><tr><th>Партнёр</th><th>Оплачено клиентами</th><th>Возвраты</th><th>Выплачено</th><th>К выплате сегодня</th><th>Текущий баланс</th></tr></thead>
                    <tbody id="balancesBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php
$pageScripts = ['finance.js'];
require __DIR__ . '/inc/layout_app_bottom.php';
