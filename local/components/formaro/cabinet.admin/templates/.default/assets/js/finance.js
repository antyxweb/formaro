/* Финансы в кабинете администратора (ТЗ «Финансы», этап 4 — PayoutService):
   выплаты поставщикам по трек-номеру («Выплачено»), все операции, балансы
   партнёров. Баланс — как TransactionRepository::totals(): оплаты
   заказов − возвраты (и прежняя комиссия) − выплаты (и прежние выводы).
   ?order=ID — подсветить заказ (переход из уведомления), ?partner=ID — фильтр. */
var allTransactions = [];
var allPayouts = [];
var payoutsPager = null;
var transactionsPager = null;
var highlightOrder = parseInt(getQueryParam('order'), 10) || 0;

$(function () {
    fillPartnerSelect('#partnerFilter');
    payoutsPager = initTablePager({root: '#payoutsTable', renderFn: renderPayouts, storageKey: 'admin_pagesize_payouts'});
    transactionsPager = initTablePager({root: '#transactionsTable', renderFn: renderTransactions, storageKey: 'admin_pagesize_transactions'});
    load();
    loadMarkup();
    $('#saveMarkupBtn').on('click', saveMarkup);
    $('#partnerFilter, #payoutStateFilter, #typeFilter').on('change', render);
    // Фильтр вкладки в шапке — только пока открыта его вкладка.
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
        var tab = $(this).data('bs-target').slice(1);
        $('.filter-bar [data-tab]').each(function () { $(this).toggleClass('d-none', $(this).data('tab') !== tab); });
    });
});

function load() {
    $.when(dsLoad('transactions'), dsLoad('payouts')).done(function (transactions, payouts) {
        allTransactions = transactions;
        allPayouts = payouts;
        if (highlightOrder) {
            var hl = payouts.find(function (p) { return p.order_id === highlightOrder; });
            $('#payoutStateFilter').val(hl ? hl.state : '');
        }
        render();
    });
}

function summary(rows) {
    var s = {earned: 0, refunded: 0, paidOut: 0};
    rows.forEach(function (t) {
        if (t.status !== 'completed') return;
        if (t.type === 'income') s.earned += t.amount;
        if (t.type === 'commission' || t.type === 'refund') s.refunded += t.amount;
        if (t.type === 'payout' || t.type === 'withdrawal') s.paidOut += t.amount;
    });
    s.balance = s.earned - s.refunded - s.paidOut;
    return s;
}

function sumBy(rows, state) {
    return rows.reduce(function (sum, r) { return sum + (r.state === state ? r.amount : 0); }, 0);
}

function render() {
    var partner = $('#partnerFilter').val();
    var tx = allTransactions.filter(function (t) { return matchPartner(t.partner_id, partner); });
    var payouts = allPayouts.filter(function (p) { return matchPartner(p.partner_id, partner); });
    var s = summary(tx);
    var due = payouts.filter(function (p) { return p.state === 'due'; });
    $('#tileDueSum').html(fmtMoneyHtml(sumBy(payouts, 'due')));
    $('#tileDue').text(due.length);
    $('#tileAwaitingSum').html(fmtMoneyHtml(sumBy(payouts, 'awaiting_track')));
    $('#tileAwaiting').text(payouts.filter(function (p) { return p.state === 'awaiting_track'; }).length);
    $('#tileBalance').html(fmtMoneyHtml(s.balance));
    $('#tileWithdrawn').html(fmtMoneyHtml(s.paidOut));
    $('#countDue').text(due.length).toggle(due.length > 0);

    var state = $('#payoutStateFilter').val();
    var order = {due: 0, awaiting_track: 1, paid: 2};
    payoutsPager.setRows(payouts.filter(function (p) { return !state || p.state === state; }).sort(function (a, b) {
        return (order[a.state] - order[b.state]) || (new Date(b.payout_at || b.shipped_at || b.created_at) - new Date(a.payout_at || a.shipped_at || a.created_at));
    }));

    var type = $('#typeFilter').val();
    transactionsPager.setRows(tx.filter(function (t) {
        if (!type) return true;
        if (type === 'payout') return t.type === 'payout' || t.type === 'withdrawal';
        if (type === 'refund') return t.type === 'refund' || t.type === 'commission';
        return t.type === type;
    }));

    renderBalances();
}

function txStatusPill(status) {
    var map = {completed: ['Проведена', 'pill-green'], pending: ['В обработке', 'pill-yellow'], rejected: ['Отменена', 'pill-red']};
    var m = map[status] || [status, 'pill-gray'];
    return '<span class="pill ' + m[1] + '">' + m[0] + '</span>';
}

function payoutStatePill(p) {
    if (p.state === 'paid') return '<span class="pill pill-green">Выплачено</span><div class="small text-muted-2 mt-1">' + fmtDate(p.payout_at, true) + (p.payout_comment ? ' · ' + esc(p.payout_comment) : '') + '</div>';
    if (p.state === 'due') return '<span class="pill pill-blue">К выплате сегодня</span>';
    return '<span class="pill pill-yellow">Ждём трек-номер</span>';
}

function renderPayouts(rows) {
    var $t = $('#payoutsBody').empty();
    if (!rows.length) { $t.html(emptyRow(7, 'Заказов нет.')); return; }
    rows.forEach(function (p) {
        var ship = p.track_number
            ? esc(p.track_carrier ? p.track_carrier + ': ' : '') + '<b>' + esc(p.track_number) + '</b><div class="small text-muted-2">' + fmtDate(p.shipped_at, true) + '</div>'
            : '<span class="text-muted-2 small">ещё не отгружен</span>';
        var action = p.state === 'due'
            ? '<button type="button" class="btn btn-sm btn-success" onclick="markPayout(' + p.order_id + ')"><i class="bi bi-check-lg"></i> Выплачено</button>'
            : '';
        $t.append('<tr' + (p.order_id === highlightOrder ? ' class="table-warning"' : '') + '>' +
            '<td><a href="' + CABINET_URL + 'orders/edit/' + p.order_id + '/">' + esc(p.order_number) + '</a><div class="small text-muted-2">оплачено клиентом ' + fmtMoney(p.buyer_total) + '</div></td>' +
            '<td>' + partnerCell(p.partner_id) + '</td>' +
            '<td class="fw-semibold text-nowrap">' + fmtMoney(p.amount) + '</td>' +
            '<td>' + ship + '</td>' +
            '<td class="small"><a href="' + CABINET_URL + 'partners/edit/' + p.partner_id + '/">Реквизиты в карточке</a></td>' +
            '<td>' + payoutStatePill(p) + '</td>' +
            '<td class="text-end text-nowrap">' + action + '</td>' +
            '</tr>');
    });
}

function renderTransactions(rows) {
    var $t = $('#transactionsBody').empty();
    if (!rows.length) { $t.html(emptyRow(6, 'Операций нет.')); return; }
    rows.forEach(function (t) {
        var sign = t.type === 'income' ? '+' : '−';
        var desc = esc(t.description || '');
        if (t.order_id) desc = '<a href="' + CABINET_URL + 'orders/edit/' + t.order_id + '/">' + desc + '</a>';
        $t.append('<tr>' +
            '<td class="small text-muted-2">' + fmtDate(t.date, true) + '</td>' +
            '<td>' + partnerCell(t.partner_id) + '</td>' +
            '<td>' + statusPill(t.type) + '</td>' +
            '<td class="small">' + desc + '</td>' +
            '<td class="text-nowrap">' + sign + ' ' + fmtMoney(t.amount) + '</td>' +
            '<td>' + txStatusPill(t.status) + '</td>' +
            '</tr>');
    });
}

function renderBalances() {
    var partner = $('#partnerFilter').val();
    var $t = $('#balancesBody').empty();
    var list = ADMIN_PARTNERS.filter(function (p) { return matchPartner(p.id, partner); });
    if (!list.length) { $t.html(emptyRow(6, 'Партнёров нет.')); return; }
    list.forEach(function (p) {
        var s = summary(allTransactions.filter(function (t) { return t.partner_id === p.id; }));
        var due = sumBy(allPayouts.filter(function (r) { return r.partner_id === p.id; }), 'due');
        $t.append('<tr>' +
            '<td>' + partnerCell(p.id) + '</td>' +
            '<td>' + fmtMoney(s.earned) + '</td>' +
            '<td>' + fmtMoney(s.refunded) + '</td>' +
            '<td>' + fmtMoney(s.paidOut) + '</td>' +
            '<td>' + (due ? '<b>' + fmtMoney(due) + '</b>' : fmtMoney(0)) + '</td>' +
            '<td class="fw-semibold">' + fmtMoney(s.balance) + '</td>' +
            '</tr>');
    });
}

/* «Выплачено» — деньги поставщику переведены; комментарий, например номер п/п. */
function markPayout(orderId) {
    var p = allPayouts.find(function (r) { return r.order_id === orderId; });
    adminAskReason({
        title: 'Выплата ' + fmtMoney(p.amount) + ' — ' + partnerName(p.partner_id) + ', заказ ' + p.order_number,
        label: 'Комментарий для поставщика (необязательно), например номер платёжного поручения',
        okText: 'Подтвердить выплату',
        primary: true,
        optional: true
    }, function (comment) {
        cabinetAjax({ajax_action: 'mark_payout', id: orderId, comment: comment}).done(function () {
            showResult(true, 'Выплата отмечена, поставщик получил уведомление');
            load();
            refreshSidebarCounts();
        });
    });
}

/* Наценка площадки и минимальный заказ (MarkupService). Смена ставки
   пересчитывает цены сайта всех товаров. */
var markupMinRate = 7;
function loadMarkup() {
    dsLoad('markup').done(function (m) {
        markupMinRate = m.min_rate;
        $('#markupRate').val(m.rate).attr('min', m.min_rate);
        $('#minOrder').val(m.min_order);
    });
}
function saveMarkup() {
    var rate = Number($('#markupRate').val());
    if (!(rate >= markupMinRate)) { showResult(false, 'Наценка не может быть меньше ' + markupMinRate + '%'); return; }
    showConfirm('Сохранить наценку ' + rate + '% и минимальный заказ ' + fmtMoney(Number($('#minOrder').val())) + '? Если ставка изменилась, цены на сайте пересчитаются для всех товаров.', function () {
        cabinetAjax({ajax_action: 'save_markup', rate: rate, min_order: Number($('#minOrder').val())}).done(function (m) {
            $('#markupRate').val(m.rate);
            $('#minOrder').val(m.min_order);
            showResult(true, m.recalculated ? 'Сохранено, цены пересчитаны: ' + m.recalculated + ' товаров' : 'Сохранено');
        });
    }, {okText: 'Сохранить'});
}
