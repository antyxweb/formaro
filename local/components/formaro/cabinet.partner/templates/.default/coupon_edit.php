<?php
/** @var array $arResult */
$activeKey = 'discounts';
$routeId = $arResult['VARIABLES']['ID'] ?? 'new';
$pageTitle = 'Купон — Formaro Partner';
$needDatePicker = true;
require __DIR__ . '/inc/layout_app_top.php';
?>
<a href="<?= $arResult['SEF_FOLDER'] ?>discounts/?tab=coupons" class="btn btn-link px-0 mb-2"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"></path><path d="M19 12H5"></path></svg> К списку купонов</a>

<div class="card">
    <div class="card-header"><span id="formTitle">Новый купон</span></div>
    <div class="card-body">
        <form id="couponForm" onsubmit="return false;">
            <input type="hidden" id="f_id">

            <div class="mb-3">
                <label class="form-label">Код купона *</label>
                <div class="input-group" style="max-width:420px;">
                    <input type="text" class="form-control font-monospace text-uppercase" id="f_code" required maxlength="20" data-validate="slug" placeholder="НАПРИМЕР: SALE2026">
                    <button type="button" class="btn btn-outline-secondary" id="generateCodeBtn" title="Сгенерировать случайный код">
                        <svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"></path><path d="M21 3v5h-5"></path></svg>
                    </button>
                </div>
                <div class="form-text">Покупатель вводит этот код на витрине при оформлении заказа.</div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Тип скидки</label>
                    <select class="form-select" id="f_discount_type">
                        <option value="percent">Процент от заказа</option>
                        <option value="fixed">Фиксированная сумма</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Размер скидки</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="f_value" min="0" step="1" value="10">
                        <span class="input-group-text" id="f_value_unit">%</span>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Тип использования</label>
                    <select class="form-select" id="f_usage_type">
                        <option value="multiple">Многоразовый</option>
                        <option value="single">Одноразовый</option>
                    </select>
                </div>
            </div>

            <hr>
            <h6 class="mb-3">Период действия</h6>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Действует с</label>
                    <input type="text" class="form-control" id="f_date_from" placeholder="дд.мм.гггг" autocomplete="off">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Действует по</label>
                    <input type="text" class="form-control" id="f_date_to" placeholder="дд.мм.гггг" autocomplete="off">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Статус</label>
                    <select class="form-select" id="f_status"><option value="active">Активен</option><option value="hidden">Скрыт</option></select>
                </div>
            </div>

            <div class="mb-3" id="usedCountWrap" style="display:none;">
                <label class="form-label d-block mb-1">Использован раз</label>
                <span class="fw-bold" id="f_used_count_display">0</span>
            </div>
        </form>
    </div>
    <div class="detail-actionbar">
        <button type="button" class="btn btn-primary" id="saveBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"></path><path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"></path><path d="M7 3v4a1 1 0 0 0 1 1h7"></path></svg> Сохранить</button>
        <button type="button" class="btn btn-outline-primary" id="applyBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg> Применить</button>
        <a href="<?= $arResult['SEF_FOLDER'] ?>discounts/?tab=coupons" class="btn btn-light"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg> Отмена</a>
        <button type="button" class="btn btn-outline-danger d-none" id="deleteBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg> Удалить</button>
    </div>
</div>
<script>var ROUTE_ID = <?= json_encode($routeId) ?>;</script>
<?php
$pageScripts = ['coupon-detail.js'];
require __DIR__ . '/inc/layout_app_bottom.php';
