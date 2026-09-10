var ticketsData = null;
var activeTicketId = 0;
var pendingAttachments = []; // [{name, type, data}] — выбранные, но ещё не отправленные файлы

$(function () {
    loadPartials('support', 'Обращение');
    activeTicketId = parseInt(getQueryParam('id'), 10) || 0;

    bindAutoHeight('#replyInput');

    dsLoad('tickets', 'data/tickets.json').done(function (data) {
        ticketsData = data;
        var t = ticketsData.tickets.find(function (x) { return x.id === activeTicketId; });
        if (!t) { showResult(false, 'Обращение не найдено'); return; }
        renderTicket(t);
    });

    $('#attachBtn').on('click', function () { $('#replyFile').click(); });
    $('#replyFile').on('change', function () {
        var files = Array.prototype.slice.call(this.files);
        files.forEach(function (file) {
            attachmentFromFile(file, function (att) {
                pendingAttachments.push(att);
                renderAttachPreview();
            });
        });
        $(this).val('');
    });

    $('#replySendBtn').on('click', function () {
        var text = $('#replyInput').val().trim();
        if (!text && pendingAttachments.length === 0) return;
        sendReply(text, pendingAttachments.slice());
    });
});

function renderAttachPreview() {
    var $w = $('#attachPreviewWrap').empty();
    if (pendingAttachments.length === 0) return;
    var html = '<div class="attach-preview-list">';
    pendingAttachments.forEach(function (a, idx) {
        var thumb = a.type === 'image' ? bgThumbHtml(a.data, 'attach-thumb') : '<i class="bi bi-file-earmark-text fs-4"></i>';
        html += '<div class="attach-preview">' + thumb + '<span>' + esc(a.name) + '</span>' +
            '<span class="attach-remove" onclick="removePendingAttachment(' + idx + ')"><i class="bi bi-x-lg"></i></span></div>';
    });
    html += '</div>';
    $w.html(html);
}
function removePendingAttachment(idx) { pendingAttachments.splice(idx, 1); renderAttachPreview(); }

/** У старых сообщений может быть одно вложение в поле attachment,
    у новых — массив attachments. Приводим к единому массиву для рендера. */
function messageAttachments(m) {
    if (Array.isArray(m.attachments)) return m.attachments;
    return m.attachment ? [m.attachment] : [];
}

function renderTicket(t) {
    $('#ticketSubject').text(t.subject);
    $('#ticketStatusPill').html(statusPill(t.status));
    var $m = $('#ticketMessages').empty();
    t.messages.forEach(function (m, idx) {
        var cls = m.sender === 'partner' ? 'out' : 'in';
        var html = '<div class="chat-bubble ' + cls + '">';
        if (m.sender === 'partner') {
            html += '<span class="msg-delete" title="Удалить сообщение" onclick="deleteMessage(' + idx + ')"><i class="bi bi-x-lg"></i></span>';
        }
        html += esc(m.text || '');
        messageAttachments(m).forEach(function (att) {
            if (att.type === 'image' && att.data) {
                html += bgThumbHtml(att.data, 'msg-image');
            } else {
                html += '<div class="chat-file">' + (att.type === 'image' ? '<i class="bi bi-image"></i>' : '<i class="bi bi-file-earmark-text"></i>') + ' ' + esc(att.name) + '</div>';
            }
        });
        html += '<span class="chat-time">' + fmtDate(m.date) + '</span></div>';
        $m.append(html);
    });
    $m.scrollTop($m[0].scrollHeight);
}

function deleteMessage(idx) {
    showConfirm('Удалить это сообщение из переписки?', function () {
        ticketsData.tickets = ticketsData.tickets.map(function (t) {
            if (t.id !== activeTicketId) return t;
            var messages = t.messages.slice();
            messages.splice(idx, 1);
            return Object.assign({}, t, {messages: messages});
        });
        dsSave('tickets', ticketsData);
        renderTicket(ticketsData.tickets.find(function (x) { return x.id === activeTicketId; }));
    }, {danger: true, okText: 'Удалить'});
}

function sendReply(text, attachments) {
    var msg = {sender: 'partner', text: text, date: new Date().toISOString()};
    if (attachments && attachments.length) msg.attachments = attachments;
    ticketsData.tickets = ticketsData.tickets.map(function (t) {
        if (t.id !== activeTicketId) return t;
        return Object.assign({}, t, {messages: t.messages.concat([msg]), status: t.status === 'closed' ? 'open' : t.status});
    });
    dsSave('tickets', ticketsData);
    $('#replyInput').val('').trigger('input');
    pendingAttachments = [];
    $('#replyFile').val('');
    renderAttachPreview();
    renderTicket(ticketsData.tickets.find(function (x) { return x.id === activeTicketId; }));
}
