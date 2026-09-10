var ticketsData = null;
var refreshClearBtn = null;
var pager = null;

$(function () {
    loadPartials('support', 'Техподдержка');
    pager = initTablePager({root: '#ticketsTable', renderFn: renderPage, storageKey: 'pagesize_tickets'});

    dsLoad('tickets', 'data/tickets.json').done(function (data) {
        ticketsData = data;
        renderContacts(data.marketplace_contacts);
        renderTickets();
    });

    $('#findBtn').on('click', renderTickets);
    $('#qInput').on('keypress', function (e) { if (e.which === 13) renderTickets(); });
    $('#statusFilter').on('change', renderTickets);
    refreshClearBtn = bindClearFilters('#clearBtn', '#qInput, #statusFilter', function () {
        $('#qInput').val(''); $('#statusFilter').val('');
        renderTickets();
    });

    $('#newTicketBtn').on('click', function () {
        $('#newTicketSubject, #newTicketMessage').val('');
        $('#newTicketFile').val('');
        $('#newTicketFilesPreview').empty();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('newTicketModal')).show();
    });
    $('#newTicketFile').on('change', function () {
        var files = Array.prototype.slice.call(this.files);
        var $prev = $('#newTicketFilesPreview').empty();
        if (files.length) {
            $prev.html('<svg class="ic-inline" style="color:var(--green);width:14px;height:14px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="m9 12 2 2 4-4"></path></svg> Выбрано файлов: ' + files.length + ' — ' + files.map(function (f) { return esc(f.name); }).join(', '));
        }
    });
    $('#newTicketConfirmBtn').on('click', function () {
        var subject = $('#newTicketSubject').val().trim();
        var message = $('#newTicketMessage').val().trim();
        if (!subject || !message) { showResult(false, 'Заполните тему и сообщение'); return; }
        var files = Array.prototype.slice.call($('#newTicketFile')[0].files);
        finishCreateTicket(subject, message, files);
    });
});

function finishCreateTicket(subject, message, files) {
    var attachments = [];
    var remaining = files.length;
    function done() {
        var msg = {sender: 'partner', text: message, date: new Date().toISOString()};
        if (attachments.length) msg.attachments = attachments;
        var newTicket = {
            id: dsNextId(ticketsData.tickets),
            subject: subject, status: 'open',
            created_at: new Date().toISOString(),
            messages: [msg]
        };
        ticketsData.tickets.unshift(newTicket);
        dsSave('tickets', ticketsData);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('newTicketModal')).hide();
        window.location.href = 'support-detail.html?id=' + newTicket.id;
    }
    if (remaining === 0) { done(); return; }
    files.forEach(function (file) {
        attachmentFromFile(file, function (att) {
            if (att) attachments.push(att);
            remaining--;
            if (remaining === 0) done();
        });
    });
}

function renderTickets() {
    if (refreshClearBtn) refreshClearBtn();
    var q = $('#qInput').val().trim().toLowerCase();
    var status = $('#statusFilter').val();
    var rows = ticketsData.tickets.filter(function (t) {
        if (status && t.status !== status) return false;
        if (q && t.subject.toLowerCase().indexOf(q) === -1) return false;
        return true;
    }).sort(function (a, b) { return new Date(b.created_at) - new Date(a.created_at); });
    pager.setRows(rows);
}

function renderPage(rows) {
    var $t = $('#ticketsBody').empty();
    if (rows.length === 0) { $t.html('<tr><td colspan="5"><div class="empty-state"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="m4.93 4.93 4.24 4.24"></path><path d="m14.83 9.17 4.24-4.24"></path><path d="m14.83 14.83 4.24 4.24"></path><path d="m9.17 14.83-4.24 4.24"></path><circle cx="12" cy="12" r="4"></circle></svg>Обращений не найдено.</div></td></tr>'); return; }
    rows.forEach(function (t) {
        $t.append(
            '<tr>' +
            '<td><a href="support-detail.html?id=' + t.id + '">' + esc(t.subject) + '</a></td>' +
            '<td>' + statusPill(t.status) + '</td>' +
            '<td>' + t.messages.length + '</td>' +
            '<td class="text-muted-2 small">' + fmtDate(t.created_at) + '</td>' +
            '<td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="support-detail.html?id=' + t.id + '"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"></path><circle cx="12" cy="12" r="3"></circle></svg> Открыть</a></td>' +
            '</tr>'
        );
    });
}

function renderContacts(c) {
    var phoneIcon = '<svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"></path></svg>';
    var mailIcon = '<svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"></path><rect x="2" y="4" width="20" height="16" rx="2"></rect></svg>';
    var globeIcon = '<svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path><path d="M2 12h20"></path></svg>';
    var landmarkIcon = '<svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 18v-7"></path><path d="M11.119 2.205a2 2 0 0 1 1.762 0l7.84 3.846A.5.5 0 0 1 20.5 7h-17a.5.5 0 0 1-.22-.949z"></path><path d="M14 18v-7"></path><path d="M18 18v-7"></path><path d="M3 22h18"></path><path d="M6 18v-7"></path></svg>';

    function contactRow(icon, value, note) {
        return '<div class="support-contact-row">' +
            '<div class="support-contact-icon">' + icon + '</div>' +
            '<div><div class="support-contact-value">' + esc(value) + '</div><div class="text-muted-2 small">' + esc(note) + '</div></div>' +
            '</div>';
    }
    function reqRow(label, value) {
        return '<div class="support-req-row"><div class="text-muted-2 small">' + esc(label) + '</div><div>' + esc(value) + '</div></div>';
    }

    $('#marketplaceContacts').html(
        '<div class="card mb-3"><div class="card-body">' +
            '<h6 class="mb-1">Будем рады помочь</h6>' +
            '<p class="text-muted-2 small mb-3">' + esc(c.work_hours) + '</p>' +
            contactRow(phoneIcon, c.support_phone, c.phone_note) +
            contactRow(mailIcon, c.support_email, c.email_note) +
            contactRow(globeIcon, c.help_url, c.help_note) +
        '</div></div>' +
        '<div class="card"><div class="card-body">' +
            '<h6 class="mb-3 d-flex align-items-center gap-2">' + landmarkIcon + ' Реквизиты</h6>' +
            reqRow('Оператор', c.legal_name) +
            reqRow('ИНН / КПП', c.inn + ' / ' + c.kpp) +
            reqRow('ОГРН', c.ogrn) +
            reqRow('Юр. адрес', c.legal_address) +
            reqRow('Банк', c.bank_name) +
            reqRow('Р/с', c.account) +
        '</div></div>'
    );
}
