/* Порт cabinet-html/assets/js/support-detail.js.
   Изменения относительно прототипа:
   - id обращения — из ROUTE_ID (путь /support/#ID#/), не из ?id=;
   - сообщения переписки теперь настоящие строки HL-блока
     CabinetTicketMessages с реальным id — deleteMessage(id) вместо
     deleteMessage(indexInArray), удаление идёт через отдельный
     AJAX-экшен delete_ticket_message;
   - ответ отправляется через reply_ticket вместо dsSave(whole-object) —
     сервер сам решает, переоткрывать ли закрытый тикет. */
var ticketId = 0;
var currentTicket = null;
var pendingAttachments = []; // [{name, type, data}] — выбранные, но ещё не отправленные файлы

$(function () {
    ticketId = parseInt(ROUTE_ID, 10) || 0;

    bindAutoHeight('#replyInput');

    dsLoad('tickets').done(function (rows) {
        var t = rows.find(function (x) { return x.id === ticketId; });
        if (!t) { showResult(false, 'Обращение не найдено'); return; }
        currentTicket = t;
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

function renderTicket(t) {
    $('#ticketSubject').text(t.subject);
    $('#ticketStatusPill').html(statusPill(t.status));
    var $m = $('#ticketMessages').empty();
    t.messages.forEach(function (m) {
        var cls = m.sender === 'partner' ? 'out' : 'in';
        var html = '<div class="chat-bubble ' + cls + '">';
        if (m.sender === 'partner') {
            html += '<span class="msg-delete" title="Удалить сообщение" onclick="deleteMessage(' + m.id + ')"><i class="bi bi-x-lg"></i></span>';
        }
        html += esc(m.text || '');
        (m.attachments || []).forEach(function (att) {
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

function deleteMessage(messageId) {
    showConfirm('Удалить это сообщение из переписки?', function () {
        cabinetAjax({ajax_action: 'delete_ticket_message', ticket_id: ticketId, message_id: messageId}).done(function (t) {
            currentTicket = t;
            renderTicket(t);
        });
    }, {danger: true, okText: 'Удалить'});
}

function sendReply(text, attachments) {
    cabinetAjax({
        ajax_action: 'reply_ticket',
        ticket_id: ticketId,
        text: text,
        attachments: JSON.stringify(attachments || [])
    }).done(function (t) {
        currentTicket = t;
        $('#replyInput').val('').trigger('input');
        pendingAttachments = [];
        $('#replyFile').val('');
        renderAttachPreview();
        renderTicket(t);
    });
}
