/* Порт cabinet-html/assets/js/dashboard.js — ссылки на *.html заменены на
   SEF-роуты, dsLoad('orders', 'data/orders.json') → dsLoad('orders')
   (сервер уже отдаёт только свои заказы партнёра). Логика периодов/
   агрегации не менялась. */
var CABINET_URL = window.CABINET_BOOTSTRAP.cabinetUrl;

var allOrders = [];
var referenceNow = new Date();
var currentRange = 'week';
var currentStatus = '';

$(function () {
    dsLoad('orders').done(function (rows) {
        allOrders = rows;
        // "Сегодня" отсчитывается от даты самого свежего заказа, чтобы
        // фильтр "Топ товаров" оставался осмысленным даже без заказов "сегодня"
        var maxDate = rows.reduce(function (max, o) {
            var d = new Date(o.created_at);
            return d > max ? d : max;
        }, new Date(0));
        referenceNow = maxDate;
        renderStats(allOrders);
        renderRecentOrders(getRecentOrders());
        renderTopProducts(getTopProductsOrders());
    });

    $('#dateRangeSelect').on('change', function () {
        currentRange = $(this).val();
        renderTopProducts(getTopProductsOrders());
    });
    $('#statusFilter').on('change', function () {
        currentStatus = $(this).val();
        renderRecentOrders(getRecentOrders());
    });
});

/** Последние 10 заказов — с учётом статуса (если выбран), но без учёта периода дат:
    период — это настройка именно для "Топ товаров", а статус — сквозной фильтр */
function getRecentOrders() {
    var rows = currentStatus ? allOrders.filter(function (o) { return o.status === currentStatus; }) : allOrders;
    return rows.slice().sort(function (a, b) { return new Date(b.created_at) - new Date(a.created_at); }).slice(0, 10);
}

function isSameDay(a, b) {
    return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
}

/** Заказы, отфильтрованные по периоду — используются только виджетом "Топ товаров" */
function getTopProductsOrders() {
    var yesterday = new Date(referenceNow); yesterday.setDate(yesterday.getDate() - 1);
    var weekAgo = new Date(referenceNow); weekAgo.setDate(weekAgo.getDate() - 7);
    var monthAgo = new Date(referenceNow); monthAgo.setMonth(monthAgo.getMonth() - 1);
    var yearAgo = new Date(referenceNow); yearAgo.setFullYear(yearAgo.getFullYear() - 1);

    return allOrders.filter(function (o) {
        var d = new Date(o.created_at);
        if (currentRange === 'today') return isSameDay(d, referenceNow);
        if (currentRange === 'yesterday') return isSameDay(d, yesterday);
        if (currentRange === 'week') return d >= weekAgo && d <= referenceNow;
        if (currentRange === 'month') return d >= monthAgo && d <= referenceNow;
        if (currentRange === 'year') return d >= yearAgo && d <= referenceNow;
        return true; // 'all' -> без ограничения по дате
    });
}

function renderStats(rows) {
    var total = rows.reduce(function (s, o) { return s + o.total; }, 0);
    var newCount = rows.filter(function (o) { return o.status === 'new'; }).length;
    var avg = rows.length ? Math.round(total / rows.length) : 0;
    $('#statOrders').text(rows.length);
    $('#statRevenue').text(fmtMoney(total));
    $('#statNew').text(newCount);
    $('#statAvg').text(fmtMoney(avg));
}

function renderTopProducts(rows) {
    var agg = {};
    rows.forEach(function (o) {
        if (o.status === 'cancelled') return;
        o.items.forEach(function (it) {
            if (!agg[it.name]) agg[it.name] = {qty: 0, revenue: 0, product_id: it.product_id};
            agg[it.name].qty += it.qty;
            agg[it.name].revenue += it.qty * it.price;
        });
    });
    var list = Object.keys(agg).map(function (name) { return {name: name, qty: agg[name].qty, revenue: agg[name].revenue, product_id: agg[name].product_id}; });
    list.sort(function (a, b) { return b.qty - a.qty; });
    list = list.slice(0, 10);

    var $t = $('#topProductsBody').empty();
    if (list.length === 0) {
        $t.html('<tr><td colspan="3" class="text-center py-4"><div class="empty-state"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"></path><path d="M18 17V9"></path><path d="M13 17V5"></path><path d="M8 17v-3"></path></svg>Нет данных за выбранный период</div></td></tr>');
        return;
    }
    list.forEach(function (p) {
        var rowAttrs = p.product_id ? ' class="cursor-pointer" onclick="location.href=\'' + CABINET_URL + 'products/edit/' + p.product_id + '/\'"' : '';
        $t.append('<tr' + rowAttrs + '><td class="fixed-row-h"><span class="truncate-cell" title="' + esc(p.name) + '">' + esc(p.name) + '</span></td><td class="fixed-row-h">' + p.qty + '</td><td class="fixed-row-h">' + fmtMoney(p.revenue) + '</td></tr>');
    });
}

function renderRecentOrders(rows) {
    var $t = $('#recentOrdersBody').empty();
    if (rows.length === 0) {
        $t.html('<tr><td colspan="4" class="text-center py-4"><div class="empty-state"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2.05 2.05 1.099-.028a1 1 0 0 1 1.008.815l2.69 14.347A1 1 0 0 0 7.83 18H18"></path><path d="M4.563 5h16.435a1 1 0 0 1 .981 1.204l-1.026 6.226A2 2 0 0 1 18.962 14H6.25"></path><circle cx="18" cy="20" r="2"></circle><circle cx="8" cy="20" r="2"></circle></svg>Заказов не найдено</div></td></tr>');
        return;
    }
    rows.forEach(function (o) {
        $t.append(
            '<tr class="cursor-pointer" onclick="location.href=\'' + CABINET_URL + 'orders/edit/' + o.id + '/\'">' +
            '<td class="fixed-row-h">' + esc(o.order_number) + '</td>' +
            '<td class="fixed-row-h"><span class="truncate-cell" title="' + esc(o.customer.name) + '">' + esc(o.customer.name) + '</span></td>' +
            '<td class="fixed-row-h">' + fmtMoney(o.total) + '</td>' +
            '<td class="fixed-row-h">' + statusPill(o.status) + '</td>' +
            '</tr>'
        );
    });
}
