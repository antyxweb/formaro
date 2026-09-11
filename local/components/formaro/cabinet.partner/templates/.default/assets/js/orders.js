/* Порт cabinet-html/assets/js/orders.js — ссылки на *.html заменены на
   SEF-роуты, dsSave(whole-array) заменён на saveOwnRows (diff-aware,
   реальные запросы к серверу вместо localStorage — см. common.js). Заказы
   партнёра сервер уже отдаёт отфильтрованными (UF_PARTNER_ID), ownOnly()
   внутри saveOwnRows — не более чем безопасная подстраховка. */
var CABINET_URL = window.CABINET_BOOTSTRAP.cabinetUrl;

var allOrders = [];
var ordersDateRange = null;
var refreshClearBtn = null;
var pager = null;

$(function () {
    bindSelectAll('#checkAll', '.row-check-order');
    ordersDateRange = initDateRangePicker('#dateRange', renderOrders);
    pager = initTablePager({root: '#ordersTable', renderFn: renderPage, storageKey: 'pagesize_orders'});

    dsLoad('orders').done(function (rows) {
        allOrders = rows;
        renderOrders();
    });

    $('#findBtn').on('click', renderOrders);
    $('#qInput').on('keypress', function (e) { if (e.which === 13) renderOrders(); });
    $('#statusFilter').on('change', renderOrders);
    refreshClearBtn = bindClearFilters('#clearBtn', '#qInput, #statusFilter, #dateRange', function () {
        $('#qInput').val(''); $('#statusFilter').val(''); ordersDateRange.clear();
        renderOrders();
    });
    $(document).on('change', '.row-check-order', function () {
        $('#bulkDeleteBtn').toggleClass('d-none', getSelectedIds('.row-check-order').length === 0);
    });
    $('#bulkDeleteBtn').on('click', function () {
        var ids = getSelectedIds('.row-check-order').map(Number);
        if (!ids.length) return;
        showConfirm('Удалить выбранные заказы (' + ids.length + ')? Обычно заказы не удаляют, а отменяют — используйте это с осторожностью.', function () {
            allOrders = allOrders.filter(function (o) { return ids.indexOf(o.id) === -1; });
            saveOwnRows('orders', null, allOrders, function () {
                showResult(true, 'Заказы удалены');
                renderOrders();
            });
        }, {danger: true, okText: 'Удалить'});
    });
});

function renderOrders() {
    if (refreshClearBtn) refreshClearBtn();
    var q = $('#qInput').val().trim().toLowerCase();
    var status = $('#statusFilter').val();
    var range = ordersDateRange.getRange();

    var rows = allOrders.filter(function (o) {
        if (status && o.status !== status) return false;
        var d = new Date(o.created_at);
        if (range.from && d < range.from) return false;
        if (range.to && d > range.to) return false;
        if (q && (o.order_number + ' ' + o.customer.name).toLowerCase().indexOf(q) === -1) return false;
        return true;
    }).sort(function (a, b) { return new Date(b.created_at) - new Date(a.created_at); });

    pager.setRows(rows);
}

function renderPage(rows) {
    var $t = $('#ordersBody').empty();
    if (rows.length === 0) {
        $t.html('<tr><td colspan="7"><div class="empty-state"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2.05 2.05 1.099-.028a1 1 0 0 1 1.008.815l2.69 14.347A1 1 0 0 0 7.83 18H18"></path><path d="M4.563 5h16.435a1 1 0 0 1 .981 1.204l-1.026 6.226A2 2 0 0 1 18.962 14H6.25"></path><circle cx="18" cy="20" r="2"></circle><circle cx="8" cy="20" r="2"></circle></svg>Заказов не найдено.</div></td></tr>');
        $('#bulkDeleteBtn').addClass('d-none');
        return;
    }
    rows.forEach(function (o) {
        $t.append(
            '<tr>' +
            '<td><input type="checkbox" class="form-check-input row-check-order" value="' + o.id + '"></td>' +
            '<td><a href="' + CABINET_URL + 'orders/edit/' + o.id + '/">' + esc(o.order_number) + '</a></td>' +
            '<td>' + esc(o.customer.name) + '</td>' +
            '<td>' + fmtMoney(o.total) + '</td>' +
            '<td>' + statusPill(o.status) + '</td>' +
            '<td class="text-muted-2 small">' + fmtDate(o.created_at) + '</td>' +
            '<td class="text-end">' +
              '<a class="btn btn-sm btn-outline-secondary" href="' + CABINET_URL + 'orders/edit/' + o.id + '/" title="Подробнее"><i class="bi bi-eye"></i></a> ' +
              '<button class="btn btn-sm btn-outline-danger" onclick="deleteOrder(' + o.id + ')" title="Удалить"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>' +
            '</td>' +
            '</tr>'
        );
    });
}

function deleteOrder(id) {
    var o = allOrders.find(function (x) { return x.id === id; });
    showConfirm('Удалить заказ «' + (o ? o.order_number : '') + '»?', function () {
        allOrders = allOrders.filter(function (x) { return x.id !== id; });
        saveOwnRows('orders', null, allOrders, function () {
            showResult(true, 'Заказ удалён');
            renderOrders();
        });
    }, {danger: true, okText: 'Удалить'});
}
