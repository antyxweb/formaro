/* Финансы поставщика (ТЗ «Финансы», этап 4 — PayoutService).
   dsLoad('finance'): {balance, total_earned, total_paid_out, due_total,
   awaiting_total, payouts[], transactions[]}. Заявок на вывод нет —
   выплату по заказу площадка проводит в день, когда поставщик указал
   трек-номер в заказе. */
var CABINET_URL = window.CABINET_BOOTSTRAP.cabinetUrl;
var financeData = null;
var financeDateRange = null;
var refreshClearBtn = null;
var pager = null;
var payoutsPager = null;

$(function () {
    financeDateRange = initDateRangePicker('#dateRange', renderTx);
    pager = initTablePager({root: '#financeTable', renderFn: renderPage, storageKey: 'pagesize_finance'});
    payoutsPager = initTablePager({root: '#payoutsTable', renderFn: renderPayouts, storageKey: 'pagesize_payouts'});

    loadFinance();

    $('#findBtn').on('click', renderTx);
    $('#typeFilter').on('change', renderTx);
    refreshClearBtn = bindClearFilters('#clearBtn', '#typeFilter, #dateRange', function () {
        $('#typeFilter').val(''); financeDateRange.clear();
        renderTx();
    });

    $('#docsBtn').on('click', function () { bootstrap.Modal.getOrCreateInstance(document.getElementById('docsModal')).show(); });
    $('#docsConfirmBtn').on('click', function () {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('docsModal')).hide();
        showResult(true, 'Запрос документов отправлен владельцу площадки. Ответ поступит на почту компании.');
        $('#docComment').val('');
    });
});

function loadFinance() {
    dsLoad('finance').done(function (data) {
        financeData = data;
        updateStatTiles();
        // Сначала ждущие трек-номера и к выплате, затем выплаченные — свежие сверху.
        var order = {awaiting_track: 0, due: 1, paid: 2};
        payoutsPager.setRows(data.payouts.slice().sort(function (a, b) {
            return (order[a.state] - order[b.state]) || (new Date(b.payout_at || b.shipped_at || b.created_at) - new Date(a.payout_at || a.shipped_at || a.created_at));
        }));
        renderTx();
    });
}
function updateStatTiles() {
    $('#statBalance').html(fmtMoneyHtml(financeData.balance));
    $('#statDue').html(fmtMoneyHtml(financeData.due_total));
    $('#statPaidOut').html(fmtMoneyHtml(financeData.total_paid_out));
}

function payoutStatePill(r) {
    if (r.state === 'paid') return '<span class="pill pill-green">Выплачено</span><div class="small text-muted-2 mt-1">' + fmtDate(r.payout_at) + (r.payout_comment ? ' · ' + esc(r.payout_comment) : '') + '</div>';
    if (r.state === 'due') return '<span class="pill pill-blue">К выплате сегодня</span>';
    return '<span class="pill pill-yellow">Ждём трек-номер</span>';
}

function renderPayouts(rows) {
    var $t = $('#payoutsBody').empty();
    if (!rows.length) { $t.html('<tr><td colspan="4"><div class="empty-state">Оплаченных заказов пока нет.</div></td></tr>'); return; }
    rows.forEach(function (r) {
        var ship = r.track_number
            ? esc(r.track_carrier ? r.track_carrier + ': ' : '') + '<b>' + esc(r.track_number) + '</b><div class="small text-muted-2">' + fmtDate(r.shipped_at) + '</div>'
            : '<a href="' + CABINET_URL + 'orders/edit/' + r.order_id + '/#payoutCard" class="btn btn-sm btn-primary">Указать трек-номер</a>';
        $t.append('<tr>' +
            '<td><a href="' + CABINET_URL + 'orders/edit/' + r.order_id + '/">' + esc(r.order_number) + '</a></td>' +
            '<td class="text-end text-nowrap fw-semibold">' + fmtMoney(r.amount) + '</td>' +
            '<td>' + ship + '</td>' +
            '<td>' + payoutStatePill(r) + '</td>' +
            '</tr>');
    });
}

/* Тип в фильтре «Выплата» — и выплаты по заказам, и прежние выводы средств. */
function txTypeMatch(t, type) {
    if (!type) return true;
    if (type === 'payout') return t.type === 'payout' || t.type === 'withdrawal';
    if (type === 'refund') return t.type === 'refund' || t.type === 'commission';
    return t.type === type;
}

function renderTx() {
    if (!financeData) return;
    if (refreshClearBtn) refreshClearBtn();
    var type = $('#typeFilter').val();
    var range = financeDateRange.getRange();

    var rows = financeData.transactions.filter(function (t) {
        if (!txTypeMatch(t, type)) return false;
        var d = new Date(t.date);
        if (range.from && d < range.from) return false;
        if (range.to && d > range.to) return false;
        return true;
    }).sort(function (a, b) { return new Date(b.date) - new Date(a.date); });

    pager.setRows(rows);
}

function renderPage(rows) {
    var $t = $('#txBody').empty();
    if (rows.length === 0) { $t.html('<tr><td colspan="5"><div class="empty-state"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"></path><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"></path></svg>Операций не найдено.</div></td></tr>'); return; }
    rows.forEach(function (t) {
        var sign = t.type === 'income' ? '+' : '−';
        var color = t.type === 'income' ? 'var(--green)' : (t.type === 'payout' || t.type === 'withdrawal' ? 'var(--brand)' : 'var(--red)');
        var desc = esc(t.description);
        if (t.order_id) desc = '<a href="' + CABINET_URL + 'orders/edit/' + t.order_id + '/">' + desc + '</a>';
        $t.append(
            '<tr><td class="text-muted-2 small">' + fmtDate(t.date) + '</td><td>' + statusPill(t.type) + '</td>' +
            '<td>' + desc + '</td>' +
            '<td class="text-end" style="color:' + color + ';">' + sign + ' ' + fmtMoney(t.amount) + '</td>' +
            '<td>' + statusPill(t.status) + '</td></tr>'
        );
    });
}
