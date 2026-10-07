<?php
/**
 * Заказ в кабинете администратора — страница по образцу заказа в кабинете
 * партнёра (order_detail.php там). Заказ ведёт партнёр, поэтому товары,
 * покупатель, статус заказа и доставка — только для просмотра; площадка
 * меняет статус оплаты (оплату подтверждает она) и пишет комментарии в
 * историю заказа.
 *
 * @var array $arResult
 */
$activeKey = 'orders';
$pageTitle = 'Заказ';
$routeId = (int)($arResult['VARIABLES']['ID'] ?? 0);
require __DIR__ . '/inc/layout_app_top.php';
?>
<a href="<?= $arResult['SEF_FOLDER'] ?>orders/" class="btn btn-link px-0 mb-2"><i class="bi bi-arrow-left"></i> К списку заказов</a>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header"><span>Заказ <span id="orderNumber">—</span> <span id="orderStatusPill"></span></span></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table order-items-table mb-0">
                        <thead><tr><th>Товар</th><th>Артикул</th><th>Цвет</th><th>Размер</th><th class="text-end">Кол-во</th><th class="text-end" title="Цена поставщика — без наценки площадки">Цена поставщика</th><th class="text-end">Цена</th><th class="text-end">Сумма</th></tr></thead>
                        <tbody id="itemsBody"><tr><td colspan="8" class="text-center text-muted-2 py-4">Загрузка…</td></tr></tbody>
                        <tfoot>
                            <tr><th colspan="7" class="text-end text-muted-2">Подытог:</th><th id="orderSubtotal" class="text-end text-muted-2 text-nowrap">—</th></tr>
                            <tr id="discountRow" class="d-none"><th colspan="7" class="text-end text-red" id="discountRowLabel">Скидка:</th><th id="orderDiscountAmount" class="text-end text-red text-nowrap">—</th></tr>
                            <tr><th colspan="7" class="text-end">Итого:</th><th id="orderTotal" class="text-end text-nowrap">—</th></tr>
                            <tr><td colspan="7" class="text-end text-muted-2 small">Поставщику / комиссия площадки:</td><td id="orderSplit" class="text-end text-muted-2 small text-nowrap">—</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php // Правка до выставления счёта — по согласованию с покупателем (order-detail.js). ?>
            <div class="card-body border-top d-none" id="itemsEditBar">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <button type="button" class="btn btn-outline-primary" id="saveItemsBtn" disabled><i class="bi bi-save"></i> Сохранить изменения состава</button>
                    <span class="text-muted-2 small">Количество 0 — убрать позицию. Меняется цена поставщика, цена для покупателя пересчитается с наценкой площадки. Меняйте состав и цены только по согласованию с покупателем и поставщиком, до выставления счёта.</span>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex align-items-center gap-2">Данные покупателя <span class="badge bg-success-subtle text-success d-none" id="custLinkedBadge" title="Заказ виден покупателю в «Ваших заказах» на сайте, уведомления о нём приходят покупателю">Покупатель сайта</span></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3"><div class="text-muted-2 small">Имя / название компании</div><div id="custName">—</div></div>
                    <div class="col-md-3 mb-3"><div class="text-muted-2 small">Телефон</div><div id="custPhone">—</div></div>
                    <div class="col-md-3 mb-3"><div class="text-muted-2 small">E-mail</div><div id="custEmail">—</div></div>
                </div>
                <div class="mb-3"><div class="text-muted-2 small">Адрес доставки</div><div id="custAddress">—</div></div>
                <div class="mb-0"><div class="text-muted-2 small">Комментарий клиента</div><div id="custComment" class="reason-note">—</div></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">История заказа</div>
            <div class="card-body">
                <div id="historyList" class="mb-3"></div>
                <div class="d-flex gap-2 align-items-end">
                    <div style="flex:1;">
                        <label class="form-label" for="historyComment">Комментарий площадки</label>
                        <input type="text" class="form-control" id="historyComment" placeholder="Например: оплата поступила, п/п № 15 от 04.10" maxlength="500">
                    </div>
                    <button type="button" class="btn btn-outline-primary" id="addHistoryBtn"><i class="bi bi-plus-lg"></i> Добавить</button>
                </div>
                <div class="form-text">Комментарий виден партнёру в истории заказа.</div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 sticky-side">
        <div class="card mb-3">
            <div class="card-header">Согласование и счёт</div>
            <div class="card-body">
                <div class="small mb-3" id="invoiceFlowState">—</div>
                <button type="button" class="btn btn-success w-100 d-none" id="issueInvoiceBtn"><i class="bi bi-receipt"></i> Выставить счёт</button>
                <a href="#" class="btn btn-outline-secondary w-100 mt-2 d-none" id="invoicePdfLink" target="_blank"><i class="bi bi-file-earmark-pdf"></i> Открыть счёт (PDF)</a>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header">Статус и оплата</div>
            <div class="card-body">
                <div class="text-muted-2 small">Партнёр</div>
                <div class="mb-3" id="orderPartner">—</div>
                <div class="text-muted-2 small">Статус заказа</div>
                <div class="mb-3" id="orderStatus">—</div>
                <div class="text-muted-2 small">Способ доставки</div>
                <div class="mb-3" id="orderDelivery">—</div>
                <div class="text-muted-2 small">Способ оплаты</div>
                <div class="mb-3" id="orderPaymentMethod">—</div>
                <label class="form-label" for="paymentStatusSelect">Статус оплаты</label>
                <select class="form-select mb-1" id="paymentStatusSelect" aria-describedby="paymentStatusHint">
                    <option value="awaiting">Ожидает оплаты</option>
                    <option value="paid">Оплачен</option>
                    <option value="refunded">Возврат</option>
                </select>
                <div class="text-muted-2 small mb-3" id="paymentStatusHint">Покупатель и партнёр получат уведомление, в историю заказа добавится запись.</div>
                <button type="button" class="btn btn-primary w-100" id="savePaymentBtn" disabled><i class="bi bi-save"></i> Сохранить статус оплаты</button>
                <div class="text-muted-2 small mt-2">Дата оформления: <span id="orderDate">—</span></div>
            </div>
        </div>
        <div class="text-muted-2 small">Товары, покупателя, статус и доставку заказа меняет партнёр в своём кабинете.</div>
    </div>
</div>
<script>var ROUTE_ID = <?= $routeId ?>;</script>
<?php
$pageScripts = ['order-detail.js'];
require __DIR__ . '/inc/layout_app_bottom.php';
