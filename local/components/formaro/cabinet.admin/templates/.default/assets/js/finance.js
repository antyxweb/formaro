/* Финансы в кабинете администратора: заявки на вывод (выплатить /
   отклонить), все операции, балансы партнёров. Баланс считается так же,
   как TransactionRepository::getSummary(): поступления − комиссия −
   возвраты − выплаченное − заявки в обработке. ?id=ID — подсветить заявку
   (переход из уведомления), ?partner=ID — фильтр. */
var allTransactions = [];
var requestsPager = null;
var transactionsPager = null;
var highlightId = parseInt(getQueryParam('id'), 10) || 0;

$(function () {
    fillPartnerSelect('#partnerFilter');
    requestsPager = initTablePager({root: '#requestsTable', renderFn: renderRequests, storageKey: 'admin_pagesize_requests'});
    transactionsPager = initTablePager({root: '#transactionsTable', renderFn: renderTransactions, storageKey: 'admin_pagesize_transactions'});
    if (highlightId) $('#requestStatusFilter').val('');
    load();
    $('#partnerFilter, #requestStatusFilter, #typeFilter').on('change', render);
});

function load() {
    dsLoad('transactions').done(function (rows) {
        allTransactions = rows;
        render();
    });
}

function byPartner() {
    var partner = $('#partnerFilter').val();
    return allTransactions.filter(function (t) { return matchPartner(t.partner_id, partner); });
}

function summary(rows) {
    var s = {earned: 0, commission: 0, refunded: 0, withdrawn: 0, pending: 0};
    rows.forEach(function (t) {
        if (t.type === 'withdrawal' && t.status === 'pending') { s.pending += t.amount; return; }
        if (t.status !== 'completed') return;
        if (t.type === 'income') s.earned += t.amount;
        if (t.type === 'commission') s.commission += t.amount;
        if (t.type === 'refund') s.refunded += t.amount;
        if (t.type === 'withdrawal') s.withdrawn += t.amount;
    });
    s.available = s.earned - s.commission - s.refunded - s.withdrawn - s.pending;
    return s;
}

function render() {
    var rows = byPartner();
    var s = summary(rows);
    var pendingRows = rows.filter(function (t) { return t.type === 'withdrawal' && t.status === 'pending'; });
    $('#tilePending').text(pendingRows.length);
    $('#tilePendingSum').html(fmtMoneyHtml(s.pending));
    $('#tileBalance').html(fmtMoneyHtml(s.available));
    $('#tileWithdrawn').html(fmtMoneyHtml(s.withdrawn));
    $('#countRequests').text(pendingRows.length).toggle(pendingRows.length > 0);

    var reqStatus = $('#requestStatusFilter').val();
    requestsPager.setRows(rows.filter(function (t) { return t.type === 'withdrawal' && (!reqStatus || t.status === reqStatus); }));

    var type = $('#typeFilter').val();
    transactionsPager.setRows(rows.filter(function (t) { return !type || t.type === type; }));

    renderBalances();
}

function txStatusPill(status) {
    var map = {completed: ['Проведена', 'pill-green'], pending: ['В обработке', 'pill-yellow'], rejected: ['Отклонена', 'pill-red']};
    var m = map[status] || [status, 'pill-gray'];
    return '<span class="pill ' + m[1] + '">' + m[0] + '</span>';
}

function renderRequests(rows) {
    var $t = $('#requestsBody').empty();
    if (!rows.length) { $t.html(emptyRow(6, 'Заявок нет.')); return; }
    rows.forEach(function (t) {
        var actions = t.status === 'pending'
            ? '<button type="button" class="btn btn-sm btn-success" onclick="approveRequest(' + t.id + ')"><i class="bi bi-check-lg"></i> Выплачено</button> ' +
              '<button type="button" class="btn btn-sm btn-outline-danger" onclick="rejectRequest(' + t.id + ')"><i class="bi bi-slash-circle"></i> Отклонить</button>'
            : '<span class="small text-muted-2">' + esc(t.description || '') + '</span>';
        $t.append('<tr' + (t.id === highlightId ? ' class="table-warning"' : '') + '>' +
            '<td class="small text-muted-2">' + fmtDate(t.date, true) + '</td>' +
            '<td>' + partnerCell(t.partner_id) + '</td>' +
            '<td class="fw-semibold">' + fmtMoney(t.amount) + '</td>' +
            '<td class="small"><a href="' + CABINET_URL + 'partners/edit/' + t.partner_id + '/">Реквизиты в карточке</a></td>' +
            '<td>' + txStatusPill(t.status) + '</td>' +
            '<td class="text-end text-nowrap">' + actions + '</td>' +
            '</tr>');
    });
}

function renderTransactions(rows) {
    var $t = $('#transactionsBody').empty();
    if (!rows.length) { $t.html(emptyRow(6, 'Операций нет.')); return; }
    rows.forEach(function (t) {
        var sign = t.type === 'income' ? '+' : '−';
        $t.append('<tr>' +
            '<td class="small text-muted-2">' + fmtDate(t.date, true) + '</td>' +
            '<td>' + partnerCell(t.partner_id) + '</td>' +
            '<td>' + statusPill(t.type) + '</td>' +
            '<td class="small">' + esc(t.description || '') + '</td>' +
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
        $t.append('<tr>' +
            '<td>' + partnerCell(p.id) + '</td>' +
            '<td>' + fmtMoney(s.earned) + '</td>' +
            '<td>' + fmtMoney(s.commission + s.refunded) + '</td>' +
            '<td>' + fmtMoney(s.withdrawn) + '</td>' +
            '<td>' + fmtMoney(s.pending) + '</td>' +
            '<td class="fw-semibold">' + fmtMoney(s.available) + '</td>' +
            '</tr>');
    });
}

function requestById(id) { return allTransactions.find(function (t) { return t.id === id; }); }

function approveRequest(id) {
    var t = requestById(id);
    adminAskReason({
        title: 'Выплата ' + fmtMoney(t.amount) + ' — ' + partnerName(t.partner_id),
        label: 'Комментарий для партнёра (необязательно), например номер платёжного поручения',
        okText: 'Подтвердить выплату',
        primary: true,
        optional: true
    }, function (comment) {
        cabinetAjax({ajax_action: 'process_withdrawal', id: id, decision: 'approve', comment: comment}).done(function () {
            showResult(true, 'Выплата отмечена, партнёр получил уведомление');
            load();
            refreshSidebarCounts();
        });
    });
}

function rejectRequest(id) {
    adminAskReason({title: 'Отклонить заявку на вывод'}, function (reason) {
        cabinetAjax({ajax_action: 'process_withdrawal', id: id, decision: 'reject', comment: reason}).done(function () {
            showResult(true, 'Заявка отклонена, сумма вернулась в доступный баланс партнёра');
            load();
            refreshSidebarCounts();
        });
    });
}
