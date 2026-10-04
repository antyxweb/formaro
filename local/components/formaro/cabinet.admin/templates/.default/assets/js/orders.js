/* Заказы всех партнёров в кабинете администратора — список с фильтрами и
   просмотр заказа в окне (?id=ID — открыть сразу, например с главной). */
var allOrders = [];
var pager = null;
var refreshClearBtn = null;
var PAYMENT_LABELS = {awaiting: ['Ожидает оплаты', 'pill-yellow'], paid: ['Оплачен', 'pill-green'], refunded: ['Возврат', 'pill-red']};

function paymentPill(s) {
    var m = PAYMENT_LABELS[s] || PAYMENT_LABELS.awaiting;
    return '<span class="pill ' + m[1] + '">' + m[0] + '</span>';
}

$(function () {
    fillPartnerSelect('#partnerFilter');
    pager = initTablePager({root: '#ordersTable', renderFn: renderPage, storageKey: 'admin_pagesize_orders'});
    dsLoad('orders').done(function (rows) {
        allOrders = rows;
        render();
        var id = parseInt(getQueryParam('id'), 10);
        if (id) openOrder(id);
    });
    $('#qInput').on('input', debounce(render, 200));
    $('#partnerFilter, #statusFilter, #paymentFilter').on('change', render);
    refreshClearBtn = bindClearFilters('#clearBtn', '#qInput, #partnerFilter, #statusFilter, #paymentFilter', function () {
        $('#qInput, #partnerFilter, #statusFilter, #paymentFilter').val('');
        render();
    });
});

function render() {
    if (refreshClearBtn) refreshClearBtn();
    var q = $('#qInput').val().trim().toLowerCase();
    var partner = $('#partnerFilter').val();
    var status = $('#statusFilter').val();
    var payment = $('#paymentFilter').val();
    var rows = allOrders.filter(function (o) {
        if (!matchPartner(o.partner_id, partner)) return false;
        if (status && o.status !== status) return false;
        if (payment && (o.payment_status || 'awaiting') !== payment) return false;
        if (q) {
            var c = o.customer || {};
            if ((o.order_number + ' ' + c.name + ' ' + c.phone + ' ' + c.email).toLowerCase().indexOf(q) === -1) return false;
        }
        return true;
    });
    var sum = rows.reduce(function (s, o) { return s + (o.status === 'cancelled' ? 0 : o.total); }, 0);
    $('#ordersTotal').html(rows.length ? 'на сумму ' + fmtMoneyHtml(sum) + ' (без отменённых)' : '');
    pager.setRows(rows);
}

function renderPage(rows) {
    var $t = $('#ordersBody').empty();
    if (!rows.length) { $t.html(emptyRow(7, 'Заказов не найдено.')); return; }
    rows.forEach(function (o) {
        $t.append('<tr class="cursor-pointer" onclick="openOrder(' + o.id + ')">' +
            '<td class="fw-semibold">' + esc(o.order_number) + '</td>' +
            '<td class="small text-muted-2">' + fmtDate(o.created_at, true) + '</td>' +
            '<td>' + partnerCell(o.partner_id) + '</td>' +
            '<td><span class="truncate-cell">' + esc(o.customer.name) + '</span></td>' +
            '<td>' + fmtMoney(o.total) + '</td>' +
            '<td>' + paymentPill(o.payment_status) + '</td>' +
            '<td>' + statusPill(o.status) + '</td>' +
            '</tr>');
    });
}

function openOrder(id) {
    var o = allOrders.find(function (x) { return x.id === id; });
    if (!o) return;
    var c = o.customer || {};
    var items = (o.items || []).map(function (it) {
        return '<tr><td>' + esc(it.name) + (it.sku ? ' <span class="text-muted-2 small">· ' + esc(it.sku) + '</span>' : '') + '</td><td class="text-end text-nowrap">' + it.qty + '</td><td class="text-end text-nowrap">' + fmtMoney(it.price) + '</td><td class="text-end text-nowrap">' + fmtMoney(it.qty * it.price) + '</td></tr>';
    }).join('');
    var history = (o.history || []).slice().reverse().map(function (h) {
        return '<div class="mb-2"><div>' + esc(h.text) + '</div><div class="small text-muted-2">' + esc(h.author || '') + ' · ' + fmtDate(h.date, true) + '</div></div>';
    }).join('') || '<div class="text-muted-2">—</div>';

    $('#orderModalTitle').html('Заказ ' + esc(o.order_number) + ' ' + statusPill(o.status));
    $('#orderModalBody').html(
        '<div class="row g-3 mb-3">' +
            '<div class="col-md-6"><div class="text-muted-2 small">Партнёр</div>' + partnerCell(o.partner_id) + '</div>' +
            '<div class="col-md-6"><div class="text-muted-2 small">Дата</div>' + fmtDate(o.created_at, true) + '</div>' +
            '<div class="col-md-6"><div class="text-muted-2 small">Покупатель</div>' + esc(c.name || '—') + '<div class="small">' + esc([c.phone, c.email].filter(Boolean).join(' · ')) + '</div><div class="small text-muted-2">' + esc(c.address || '') + '</div></div>' +
            '<div class="col-md-6"><div class="text-muted-2 small">Доставка и оплата</div>' + esc(o.delivery_method || '—') + ' · ' + esc(o.payment_method || '—') + '<div class="mt-1">' + paymentPill(o.payment_status) + '</div></div>' +
            (o.customer_comment ? '<div class="col-12"><div class="text-muted-2 small">Комментарий покупателя</div>' + esc(o.customer_comment) + '</div>' : '') +
        '</div>' +
        '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Товар</th><th class="text-end">Кол-во</th><th class="text-end">Цена</th><th class="text-end">Сумма</th></tr></thead><tbody>' + items + '</tbody>' +
        '<tfoot>' +
            (o.discount_amount ? '<tr><td colspan="3" class="text-end">Скидка' + (o.discount_name ? ' «' + esc(o.discount_name) + '»' : '') + (o.coupon_code ? ' (купон ' + esc(o.coupon_code) + ')' : '') + '</td><td class="text-end text-nowrap">−' + fmtMoney(o.discount_amount) + '</td></tr>' : '') +
            '<tr><th colspan="3" class="text-end">Итого</th><th class="text-end text-nowrap">' + fmtMoney(o.total) + '</th></tr>' +
        '</tfoot></table></div>' +
        '<h6 class="mt-3">История</h6>' + history
    );
    bootstrap.Modal.getOrCreateInstance($('#orderModal')[0]).show();
}
