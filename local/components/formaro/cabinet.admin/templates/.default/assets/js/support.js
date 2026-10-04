/* Обращения в техподдержку всех партнёров: «Открыт» — ждёт ответа
   площадки, сверху; дальше по последнему сообщению. */
var allTickets = [];
var pager = null;
var refreshClearBtn = null;
var STATUS_ORDER = {open: 0, answered: 1, closed: 2};

$(function () {
    fillPartnerSelect('#partnerFilter');
    var status = getQueryParam('status');
    if (status) $('#statusFilter').val(status);
    pager = initTablePager({root: '#ticketsTable', renderFn: renderPage, storageKey: 'admin_pagesize_tickets'});
    dsLoad('tickets').done(function (rows) { allTickets = rows; render(); });
    $('#qInput').on('input', debounce(render, 200));
    $('#partnerFilter, #statusFilter').on('change', render);
    refreshClearBtn = bindClearFilters('#clearBtn', '#qInput, #partnerFilter, #statusFilter', function () {
        $('#qInput, #partnerFilter, #statusFilter').val('');
        render();
    });
});

function lastMessage(t) { return t.messages[t.messages.length - 1] || null; }
function lastTime(t) { var m = lastMessage(t); return new Date(m ? m.date : t.created_at).getTime(); }

function render() {
    if (refreshClearBtn) refreshClearBtn();
    var q = $('#qInput').val().trim().toLowerCase();
    var partner = $('#partnerFilter').val();
    var status = $('#statusFilter').val();
    pager.setRows(allTickets.filter(function (t) {
        if (q && t.subject.toLowerCase().indexOf(q) === -1) return false;
        if (!matchPartner(t.partner_id, partner)) return false;
        return !status || t.status === status;
    }).sort(function (a, b) {
        return (STATUS_ORDER[a.status] - STATUS_ORDER[b.status]) || (lastTime(b) - lastTime(a));
    }));
}

function renderPage(rows) {
    var $t = $('#ticketsBody').empty();
    if (!rows.length) { $t.html(emptyRow(6, 'Обращений не найдено.')); return; }
    rows.forEach(function (t) {
        var last = lastMessage(t);
        var who = last ? (last.sender === 'support' ? 'Поддержка' : 'Партнёр') : '';
        $t.append('<tr class="cursor-pointer" onclick="location.href=\'' + CABINET_URL + 'support/' + t.id + '/\'">' +
            '<td><a href="' + CABINET_URL + 'support/' + t.id + '/">' + esc(t.subject) + '</a></td>' +
            '<td>' + partnerCell(t.partner_id) + '</td>' +
            '<td>' + (t.status === 'open' ? '<span class="pill pill-blue">Ждёт ответа</span>' : statusPill(t.status)) + '</td>' +
            '<td>' + t.messages.length + '</td>' +
            '<td class="small"><span class="truncate-cell">' + (last ? '<span class="text-muted-2">' + who + ', ' + fmtDate(last.date, true) + ':</span> ' + esc(last.text || 'вложение') : '—') + '</span></td>' +
            '<td class="small text-muted-2">' + fmtDate(t.created_at) + '</td>' +
            '</tr>');
    });
}
