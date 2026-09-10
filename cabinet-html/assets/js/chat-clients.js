var allThreads = [];
var activeThreadId = null;
var refreshClearBtn = null;
var pendingAttachments = [];

$(function () {
    loadPartials('chat', 'Чат с клиентами');
    bindAutoHeight('#chatInput');

    dsLoad('chat', 'data/chat.json').done(function (threads) {
        allThreads = threads;
        renderThreads();
    });

    $('#findBtn').on('click', renderThreads);
    ['#fClient', '#fOrder', '#fProduct'].forEach(function (sel) {
        $(sel).on('keypress', function (e) { if (e.which === 13) renderThreads(); });
    });
    refreshClearBtn = bindClearFilters('#clearBtn', '#fClient, #fOrder, #fProduct', function () {
        $('#fClient, #fOrder, #fProduct').val('');
        renderThreads();
    });

    $('#attachBtn').on('click', function () { $('#chatFile').click(); });
    $('#chatFile').on('change', function () {
        var files = Array.prototype.slice.call(this.files);
        files.forEach(function (file) {
            attachmentFromFile(file, function (att) { pendingAttachments.push(att); renderAttachPreview(); });
        });
        $(this).val('');
    });

    $('#sendBtn').on('click', sendMessage);
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

function messageAttachments(m) {
    if (Array.isArray(m.attachments)) return m.attachments;
    return m.attachment ? [m.attachment] : [];
}

function unreadCount(t) {
    return t.messages.filter(function (m) { return m.sender === 'client' && m.is_read === false; }).length;
}

function filteredThreads() {
    if (refreshClearBtn) refreshClearBtn();
    var client = $('#fClient').val().trim().toLowerCase();
    var order = $('#fOrder').val().trim().toLowerCase();
    var product = $('#fProduct').val().trim().toLowerCase();

    return allThreads.filter(function (t) {
        if (client && t.client_name.toLowerCase().indexOf(client) === -1) return false;
        if (order && (t.order_id || '').toLowerCase().indexOf(order) === -1) return false;
        if (product && t.product_name.toLowerCase().indexOf(product) === -1) return false;
        return true;
    }).sort(function (a, b) {
        var au = unreadCount(a) > 0 ? 1 : 0, bu = unreadCount(b) > 0 ? 1 : 0;
        if (au !== bu) return bu - au;
        var al = a.messages[a.messages.length - 1], bl = b.messages[b.messages.length - 1];
        return new Date(bl ? bl.date : 0) - new Date(al ? al.date : 0);
    });
}

/** Полностью перерисовывает список диалогов — активный элемент подсвечивается
    здесь же, в одном месте, по текущему activeThreadId (без отдельной
    ручной правки классов в других функциях — раньше это давало гонку,
    когда подсветка сразу же стиралась). */
function renderThreads() {
    var threads = filteredThreads();
    var $l = $('#threadsList').empty();
    if (threads.length === 0) { $l.html('<div class="empty-state"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 10a2 2 0 0 1-2 2H6.828a2 2 0 0 0-1.414.586l-2.202 2.202A.71.71 0 0 1 2 14.286V4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path><path d="M20 9a2 2 0 0 1 2 2v10.286a.71.71 0 0 1-1.212.502l-2.202-2.202A2 2 0 0 0 17.172 19H10a2 2 0 0 1-2-2v-1"></path></svg>Ничего не найдено.</div>'); return; }
    threads.forEach(function (t) {
        var last = t.messages[t.messages.length - 1];
        var active = t.thread_id === activeThreadId ? ' active' : '';
        var unread = unreadCount(t);
        $l.append(
            '<div class="chat-thread-item' + active + (unread > 0 ? ' has-unread' : '') + '" onclick="openThread(\'' + t.thread_id + '\')">' +
              '<div class="d-flex justify-content-between align-items-center">' +
                '<span class="thread-name">' + esc(t.client_name) + '</span>' +
                (unread > 0 ? '<span class="thread-unread-badge">' + unread + '</span>' : '<span class="text-muted-2" style="font-size:.7rem;">' + esc(t.order_id) + '</span>') +
              '</div>' +
              '<div class="text-muted-2 small text-truncate">' + esc(t.product_name) + '</div>' +
              '<div class="text-muted-2 small text-truncate">' + esc(last ? last.text : '') + '</div>' +
            '</div>'
        );
    });
}

function openThread(threadId) {
    activeThreadId = threadId;
    var t = allThreads.find(function (x) { return x.thread_id === threadId; });
    if (!t) return;

    var hadUnread = unreadCount(t) > 0;
    allThreads = allThreads.map(function (x) {
        if (x.thread_id !== threadId) return x;
        return Object.assign({}, x, {messages: x.messages.map(function (m) {
            return (m.sender === 'client' && m.is_read === false) ? Object.assign({}, m, {is_read: true}) : m;
        })});
    });
    if (hadUnread) dsSave('chat', allThreads);
    t = allThreads.find(function (x) { return x.thread_id === threadId; });

    renderThreads();
    $('#composeWrap').show();
    renderMessages(t);
    // поле было скрыто (display:none), scrollHeight на тот момент был 0 —
    // пересчитываем высоту заново теперь, когда блок реально виден
    autoHeightResize('#chatInput');
}

function renderMessages(t) {
    var $s = $('#messagesScroll').empty();
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
        $s.append(html);
    });
    $s.scrollTop($s[0].scrollHeight);
}

function deleteMessage(idx) {
    showConfirm('Удалить это сообщение из переписки?', function () {
        allThreads = allThreads.map(function (t) {
            if (t.thread_id !== activeThreadId) return t;
            var messages = t.messages.slice();
            messages.splice(idx, 1);
            return Object.assign({}, t, {messages: messages});
        });
        dsSave('chat', allThreads);
        renderMessages(allThreads.find(function (x) { return x.thread_id === activeThreadId; }));
    }, {danger: true, okText: 'Удалить'});
}

function sendMessage() {
    var text = $('#chatInput').val().trim();
    if ((!text && pendingAttachments.length === 0) || !activeThreadId) return;
    var msg = {sender: 'partner', text: text, date: new Date().toISOString(), is_read: true};
    if (pendingAttachments.length) msg.attachments = pendingAttachments.slice();
    allThreads = allThreads.map(function (t) {
        if (t.thread_id !== activeThreadId) return t;
        return Object.assign({}, t, {messages: t.messages.concat([msg])});
    });
    dsSave('chat', allThreads);
    $('#chatInput').val('').trigger('input');
    pendingAttachments = [];
    $('#chatFile').val('');
    renderAttachPreview();
    renderMessages(allThreads.find(function (x) { return x.thread_id === activeThreadId; }));
}
