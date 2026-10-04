/* Заказы всех партнёров в кабинете администратора — список с фильтрами;
   строка — страница заказа (orders/edit/ID/, статус оплаты там). */
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
        $t.append('<tr class="cursor-pointer" onclick="location.href=\'' + CABINET_URL + 'orders/edit/' + o.id + '/\'">' +
            '<td class="fw-semibold"><a href="' + CABINET_URL + 'orders/edit/' + o.id + '/">' + esc(o.order_number) + '</a></td>' +
            '<td class="small text-muted-2">' + fmtDate(o.created_at, true) + '</td>' +
            '<td>' + partnerCell(o.partner_id) + '</td>' +
            '<td><span class="truncate-cell">' + esc(o.customer.name) + '</span></td>' +
            '<td>' + fmtMoney(o.total) + '</td>' +
            '<td>' + paymentPill(o.payment_status) + '</td>' +
            '<td>' + statusPill(o.status) + '</td>' +
            '</tr>');
    });
}
