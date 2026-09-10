var couponId = 0;
var allCoupons = [];
var couponDateFrom = null, couponDateTo = null;

$(function () {
    loadPartials('discounts', 'Купон');

    var idParam = getQueryParam('id');
    couponId = (idParam && idParam !== 'new') ? parseInt(idParam, 10) : 0;

    couponDateFrom = initSingleDatePicker('#f_date_from');
    couponDateTo = initSingleDatePicker('#f_date_to');

    $('#f_discount_type').on('change', updateValueUnit);
    // Код купона всегда в верхнем регистре, только латиница/цифры (та же
    // валидация "slug", что и у поля Код в других формах)
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

    dsLoad('discounts', 'data/discounts.json').done(function (data) {
        allCoupons = ownOnly(data.coupons || []);

        if (couponId) {
            var c = allCoupons.find(function (x) { return x.id === couponId; });
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
        } else {
            updateValueUnit();
        }
    });

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

    var duplicate = allCoupons.some(function (c) { return c.code === code && c.id !== couponId; });
    if (duplicate) { showResult(false, 'Такой код купона уже существует'); return; }

    var wasNew = !couponId;
    var payload = {
        code: code,
        discount_type: $('#f_discount_type').val(),
        value: parseFloat($('#f_value').val()) || 0,
        usage_type: $('#f_usage_type').val(),
        date_from: $('#f_date_from').val() ? isoFromRuDate($('#f_date_from').val()) : '',
        date_to: $('#f_date_to').val() ? isoFromRuDate($('#f_date_to').val()) : '',
        status: $('#f_status').val(),
        partner_id: CURRENT_PARTNER_ID
    };

    if (couponId) {
        allCoupons = allCoupons.map(function (c) { return c.id === couponId ? Object.assign({}, c, payload) : c; });
    } else {
        payload.id = dsNextId(allCoupons);
        payload.used_count = 0;
        payload.created_at = new Date().toISOString();
        allCoupons.push(payload);
        couponId = payload.id;
        $('#f_id').val(couponId);
        $('#formTitle').text('Редактирование: ' + code);
        $('#deleteBtn').removeClass('d-none');
        $('#usedCountWrap').show();
        $('#f_used_count_display').text('0 раз');
    }

    dsLoad('discounts', 'data/discounts.json').done(function (full) {
        var foreignCoupons = (full.coupons || []).filter(function (c) { return c.partner_id !== CURRENT_PARTNER_ID; });
        dsSave('discounts', {discounts: full.discounts || [], coupons: foreignCoupons.concat(allCoupons)});
        showResult(true, wasNew ? 'Купон создан' : 'Купон сохранён');
        if (goBack) setTimeout(function () { window.location.href = 'discounts.html?tab=coupons'; }, 700);
    });
}

function doDelete() {
    if (!couponId) return;
    var c = allCoupons.find(function (x) { return x.id === couponId; });
    showConfirm('Удалить купон «' + (c ? c.code : '') + '»? Это действие необратимо.', function () {
        allCoupons = allCoupons.filter(function (x) { return x.id !== couponId; });
        dsLoad('discounts', 'data/discounts.json').done(function (full) {
            var foreignCoupons = (full.coupons || []).filter(function (x) { return x.partner_id !== CURRENT_PARTNER_ID; });
            dsSave('discounts', {discounts: full.discounts || [], coupons: foreignCoupons.concat(allCoupons)});
            showResult(true, 'Купон удалён');
            setTimeout(function () { window.location.href = 'discounts.html?tab=coupons'; }, 700);
        });
    }, {danger: true, okText: 'Удалить'});
}
