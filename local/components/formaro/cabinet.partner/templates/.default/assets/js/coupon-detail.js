/* Порт cabinet-html/assets/js/coupon-detail.js.
   Изменения относительно прототипа:
   - id купона — из ROUTE_ID (путь /discounts/coupons/edit/#ID#/), не из ?id=;
   - купон теперь настоящая строка в БД (HL-блок CabinetCoupons) — сохранение/
     удаление напрямую через dsSaveOne/dsDeleteOne; проверку уникальности
     кода теперь делает сервер (CouponRepository::save), поэтому клиентский
     предварительный поиск дубликата по allCoupons убран — при совпадении
     сервер просто вернёт ошибку, она сама всплывёт через showResult
     (см. cabinetAjax в common.js). */
var CABINET_URL = window.CABINET_BOOTSTRAP.cabinetUrl;

var couponId = 0;
var couponDateFrom = null, couponDateTo = null;

$(function () {
    couponId = (ROUTE_ID && ROUTE_ID !== 'new') ? parseInt(ROUTE_ID, 10) : 0;

    couponDateFrom = initSingleDatePicker('#f_date_from');
    couponDateTo = initSingleDatePicker('#f_date_to');

    $('#f_discount_type').on('change', updateValueUnit);
    $('#f_code').on('input', function () {
        var pos = this.selectionStart;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(pos, pos);
    });
    $('#generateCodeBtn').on('click', function () {
        var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ0123456789';
        var code = '';
        for (var i = 0; i < 8; i++) code += chars.charAt(Math.floor(Math.random() * chars.length));
        $('#f_code').val(code).trigger('input');
    });

    if (couponId) {
        dsLoad('coupons').done(function (rows) {
            var c = ownOnly(rows).find(function (x) { return x.id === couponId; });
            if (!c) { showResult(false, 'Купон не найден'); return; }
            $('#formTitle').text('Редактирование: ' + c.code);
            $('#f_id').val(c.id);
            $('#f_code').val(c.code);
            $('#f_discount_type').val(c.discount_type);
            $('#f_value').val(c.value);
            $('#f_usage_type').val(c.usage_type);
            $('#f_status').val(c.status || 'active');
            if (c.date_from) couponDateFrom.setDate(c.date_from, true);
            if (c.date_to) couponDateTo.setDate(c.date_to, true);
            $('#usedCountWrap').show();
            $('#f_used_count_display').text((c.used_count || 0) + ' раз');
            $('#deleteBtn').removeClass('d-none');
            updateValueUnit();
        });
    } else {
        updateValueUnit();
    }

    $('#saveBtn').on('click', function () { doSave(true); });
    $('#applyBtn').on('click', function () { doSave(false); });
    $('#deleteBtn').on('click', doDelete);
});

function updateValueUnit() {
    $('#f_value_unit').text($('#f_discount_type').val() === 'percent' ? '%' : '₽');
}

function doSave(goBack) {
    var code = $('#f_code').val().trim().toUpperCase();
    if (!code) { showResult(false, 'Укажите код купона'); return; }
    if (!/^[A-Z0-9_-]+$/.test(code)) { showResult(false, 'Код купона: только латиница, цифры, тире и подчёркивание'); return; }

    var wasNew = !couponId;
    var payload = {
        id: couponId || undefined,
        code: code,
        discount_type: $('#f_discount_type').val(),
        value: parseFloat($('#f_value').val()) || 0,
        usage_type: $('#f_usage_type').val(),
        date_from: $('#f_date_from').val() ? isoFromRuDate($('#f_date_from').val()) : '',
        date_to: $('#f_date_to').val() ? isoFromRuDate($('#f_date_to').val()) : '',
        status: $('#f_status').val()
    };

    dsSaveOne('coupons', payload).done(function (saved) {
        couponId = saved.id;
        $('#f_id').val(couponId);
        $('#formTitle').text('Редактирование: ' + code);
        $('#deleteBtn').removeClass('d-none');
        $('#usedCountWrap').show();
        $('#f_used_count_display').text((saved.used_count || 0) + ' раз');
        showResult(true, wasNew ? 'Купон создан' : 'Купон сохранён');
        if (goBack) setTimeout(function () { window.location.href = CABINET_URL + 'discounts/?tab=coupons'; }, 700);
    });
}

function doDelete() {
    if (!couponId) return;
    var code = $('#f_code').val();
    showConfirm('Удалить купон «' + code + '»? Это действие необратимо.', function () {
        dsDeleteOne('coupons', couponId).done(function () {
            showResult(true, 'Купон удалён');
            setTimeout(function () { window.location.href = CABINET_URL + 'discounts/?tab=coupons'; }, 700);
        });
    }, {danger: true, okText: 'Удалить'});
}
