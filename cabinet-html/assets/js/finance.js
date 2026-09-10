var financeData = null;
var financeDateRange = null;
var refreshClearBtn = null;
var pager = null;

$(function () {
    loadPartials('finance', 'Финансы');
    financeDateRange = initDateRangePicker('#dateRange', renderTx);
    pager = initTablePager({root: '#financeTable', renderFn: renderPage, storageKey: 'pagesize_finance'});

    dsLoad('finance', 'data/finance.json').done(function (data) {
        financeData = data;
        $('#statAvailable').text(fmtMoney(data.available_balance));
        $('#statPending').text(fmtMoney(data.pending_balance));
        $('#statTotal').text(fmtMoney(data.total_earned));
        renderTx();
    });

    $('#findBtn').on('click', renderTx);
    $('#typeFilter').on('change', renderTx);
    refreshClearBtn = bindClearFilters('#clearBtn', '#typeFilter, #dateRange', function () {
        $('#typeFilter').val(''); financeDateRange.clear();
        renderTx();
    });

    $('#withdrawBtn').on('click', function () {
        $('#withdrawAmount').val(financeData ? financeData.available_balance : '');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('withdrawModal')).show();
    });
    $('#withdrawConfirmBtn').on('click', function () {
        var amount = parseInt($('#withdrawAmount').val(), 10) || 0;
        if (amount <= 0) { showResult(false, 'Укажите сумму вывода'); return; }
        if (amount > financeData.available_balance) { showResult(false, 'Сумма превышает доступный к выводу баланс'); return; }
        financeData.transactions.unshift({
            id: dsNextId(financeData.transactions), type: 'withdrawal', amount: amount, status: 'pending',
            description: 'Заявка на вывод средств (в обработке)', date: new Date().toISOString()
        });
        financeData.pending_balance += amount;
        financeData.available_balance -= amount;
        dsSave('finance', financeData);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('withdrawModal')).hide();
        showResult(true, 'Запрос на вывод ' + fmtMoney(amount) + ' отправлен');
        $('#statAvailable').text(fmtMoney(financeData.available_balance));
        $('#statPending').text(fmtMoney(financeData.pending_balance));
        renderTx();
    });

    $('#docsBtn').on('click', function () { bootstrap.Modal.getOrCreateInstance(document.getElementById('docsModal')).show(); });
    $('#docsConfirmBtn').on('click', function () {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('docsModal')).hide();
        showResult(true, 'Запрос документов отправлен владельцу площадки. Ответ поступит на почту компании.');
        $('#docComment').val('');
    });

    // переход из сайдбара по кнопке "Запросить вывод" (finance.html?action=withdraw) сразу открывает модалку
    if (getQueryParam('action') === 'withdraw') {
        dsLoad('finance', 'data/finance.json').done(function (data) {
            financeData = data;
            $('#withdrawAmount').val(data.available_balance);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('withdrawModal')).show();
        });
    }
});

function renderTx() {
    if (!financeData) return;
    if (refreshClearBtn) refreshClearBtn();
    var type = $('#typeFilter').val();
    var range = financeDateRange.getRange();

    var rows = financeData.transactions.filter(function (t) {
        if (type && t.type !== type) return false;
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
        var color = t.type === 'income' ? 'var(--green)' : (t.type === 'withdrawal' ? 'var(--brand)' : 'var(--red)');
        $t.append(
            '<tr><td class="text-muted-2 small">' + fmtDate(t.date) + '</td><td>' + statusPill(t.type) + '</td>' +
            '<td>' + esc(t.description) + '</td>' +
            '<td class="text-end" style="color:' + color + ';">' + sign + ' ' + fmtMoney(t.amount) + '</td>' +
            '<td>' + statusPill(t.status) + '</td></tr>'
        );
    });
}
