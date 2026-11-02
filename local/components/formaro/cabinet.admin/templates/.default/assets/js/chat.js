/* Чаты покупателей с партнёрами — просмотр для администратора (по образцу
   chat-clients.js кабинета партнёра, без ответа и отметки прочитанным).
   ?partner=ID — фильтр, ?thread=ID — открыть диалог, ?alerts=1 — только
   диалоги с нарушениями. Сообщения с контактами/оплатой/сделкой в обход
   площадки помечены (alert). Обновление — раз в 30 с.
   Чаты покупателей с площадкой (partner_id = 0, «Сообщения» на сайте) —
   с ответом (reply_chat) и отметкой прочитанным (read_chat); пустые
   (покупатель открыл страницу, но не написал) не показываются. */
var allThreads = [];
var activeThreadId = 0;
var refreshClearBtn = null;
var pendingAttachments = []; // [{name, type, data}]
var PLATFORM_LABEL = 'Площадка';

$(function () {
    fillPartnerSelect('#partnerFilter', {siteLabel: PLATFORM_LABEL + ' (вопросы покупателей)'});
    bindAutoHeight('#replyInput');
    $('#attachBtn').on('click', function () { $('#replyFile').click(); });
    $('#replyFile').on('change', function () {
        Array.prototype.slice.call(this.files).forEach(function (file) {
            attachmentFromFile(file, function (att) { pendingAttachments.push(att); renderAttachPreview(); });
        });
        $(this).val('');
    });
    $('#replySendBtn').on('click', sendReply);
    if (getQueryParam('alerts')) $('#alertsOnly').prop('checked', true);
    dsLoad('chat').done(function (threads) {
        allThreads = threads;
        renderThreads();
        var fromUrl = parseInt(getQueryParam('thread'), 10);
        if (fromUrl) openThread(fromUrl);
    });
    $('#partnerFilter, #alertsOnly').on('change', renderThreads);
    $('#fClient, #fOrder, #fProduct').on('input', debounce(renderThreads, 200));
    refreshClearBtn = bindClearFilters('#clearBtn', '#partnerFilter, #fClient, #fOrder, #fProduct', function () {
        $('#partnerFilter, #fClient, #fOrder, #fProduct').val('');
        $('#alertsOnly').prop('checked', false);
        renderThreads();
    });
    setInterval(function () {
        if (document.hidden) return;
        dsLoadSilent('chat').done(function (threads) {
            if (JSON.stringify(threads) === JSON.stringify(allThreads)) return;
            allThreads = threads;
            renderThreads();
            var t = allThreads.find(function (x) { return x.thread_id === activeThreadId; });
            if (t) { renderMessages(t); markPlatformRead(t); }
        });
    }, 30000);
});

function lastTime(t) {
    var last = t.messages[t.messages.length - 1];
    return new Date(last ? last.date : t.created_at).getTime();
}
function alertCount(t) {
    return t.messages.filter(function (m) { return m.alert; }).length;
}
function isPlatform(t) { return !t.partner_id; }
function threadSubject(t) {
    if (isPlatform(t)) return 'Вопрос площадке';
    if (t.order_id) return 'Заказ ' + t.order_id;
    if (t.product_name) return 'Товар: ' + t.product_name;
    return 'Общий вопрос';
}

function renderThreads() {
    if (refreshClearBtn) refreshClearBtn();
    var partner = $('#partnerFilter').val();
    var client = $('#fClient').val().trim().toLowerCase();
    var order = $('#fOrder').val().trim().toLowerCase();
    var product = $('#fProduct').val().trim().toLowerCase();
    var threads = allThreads.filter(function (t) {
        if (!t.messages.length) return false;
        if (!matchPartner(t.partner_id, partner)) return false;
        if (client && t.client_name.toLowerCase().indexOf(client) === -1) return false;
        if (order && (t.order_id || '').toLowerCase().indexOf(order) === -1) return false;
        if ($('#alertsOnly').is(':checked') && !alertCount(t)) return false;
        return !product || (t.product_name || '').toLowerCase().indexOf(product) !== -1;
    }).sort(function (a, b) { return lastTime(b) - lastTime(a); });

    var $l = $('#threadsList').empty();
    if (!threads.length) { $l.html('<div class="empty-state"><i class="bi bi-chat-dots"></i>Ничего не найдено.</div>'); return; }
    threads.forEach(function (t) {
        var last = t.messages[t.messages.length - 1];
        $l.append(
            '<div class="chat-thread-item' + (t.thread_id === activeThreadId ? ' active' : '') + '" onclick="openThread(' + t.thread_id + ')">' +
              '<div class="d-flex justify-content-between align-items-center gap-2"><span class="thread-name">' + esc(t.client_name) + '</span>' +
                '<span class="small text-muted-2 text-nowrap">' + (last ? fmtDate(last.date, false) : '') + '</span></div>' +
              '<div class="small text-truncate">' + (isPlatform(t)
                  ? '<span class="pill pill-blue">' + PLATFORM_LABEL + '</span>' + (platformUnread(t) ? ' <span class="pill pill-red" title="Не прочитано">' + platformUnread(t) + ' новых</span>' : '')
                  : '<i class="bi bi-shop"></i> ' + esc(partnerName(t.partner_id))) +
                (alertCount(t) ? ' <span class="pill pill-red" title="Сообщения с контактами или оплатой в обход площадки"><i class="bi bi-exclamation-triangle"></i> ' + alertCount(t) + '</span>' : '') + '</div>' +
              '<div class="thread-subject small text-truncate">' + esc(threadSubject(t)) + '</div>' +
              '<div class="text-muted-2 small text-truncate">' + esc(last ? (last.text || 'вложение') : '') + '</div>' +
            '</div>'
        );
    });
}

function openThread(threadId) {
    var t = allThreads.find(function (x) { return x.thread_id === threadId; });
    if (!t) return;
    activeThreadId = threadId;
    pendingAttachments = [];
    renderAttachPreview();
    renderThreads();
    renderMessages(t);
    markPlatformRead(t);
}

/** Чат с площадкой открыт — сообщения покупателя прочитаны. */
function markPlatformRead(t) {
    if (!isPlatform(t) || !platformUnread(t) || document.hidden) return;
    cabinetAjax({ajax_action: 'read_chat', thread_id: t.thread_id}).done(function (fresh) {
        replaceThread(fresh);
        refreshSidebarCounts();
    });
}
function replaceThread(fresh) {
    allThreads = allThreads.map(function (x) { return x.thread_id === fresh.thread_id ? fresh : x; });
    renderThreads();
    if (fresh.thread_id === activeThreadId) renderMessages(fresh);
}

function renderAttachPreview() {
    var $w = $('#attachPreviewWrap').empty();
    if (!pendingAttachments.length) return;
    var html = '<div class="attach-preview-list">';
    pendingAttachments.forEach(function (a, idx) {
        var thumb = a.type === 'image' ? bgThumbHtml(a.data, 'attach-thumb') : '<i class="bi bi-file-earmark-text fs-4"></i>';
        html += '<div class="attach-preview">' + thumb + '<span>' + esc(a.name) + '</span>' +
            '<span class="attach-remove" onclick="removePendingAttachment(' + idx + ')"><i class="bi bi-x-lg"></i></span></div>';
    });
    $w.html(html + '</div>');
}
function removePendingAttachment(idx) { pendingAttachments.splice(idx, 1); renderAttachPreview(); }

function sendReply() {
    var text = $('#replyInput').val().trim();
    if (!activeThreadId || (!text && !pendingAttachments.length)) return;
    cabinetAjax({
        ajax_action: 'reply_chat',
        thread_id: activeThreadId,
        text: text,
        attachments: JSON.stringify(pendingAttachments)
    }).done(function (fresh) {
        $('#replyInput').val('').trigger('input');
        pendingAttachments = [];
        renderAttachPreview();
        replaceThread(fresh);
        refreshSidebarCounts();
    });
}

function renderMessages(t) {
    var platform = isPlatform(t);
    $('#threadHead').removeClass('d-none').html(
        '<b>' + esc(t.client_name) + '</b> ↔ ' + partnerCell(t.partner_id, PLATFORM_LABEL) + ' · ' + esc(threadSubject(t))
    );
    $('#readOnlyNote').toggleClass('d-none', platform);
    $('#replyBox').toggleClass('d-none', !platform);
    var $s = $('#messagesScroll').empty();
    t.messages.forEach(function (m) {
        var fromPartner = m.sender === 'partner';
        var html = '<div class="chat-bubble ' + (fromPartner ? 'out' : 'in') + (m.alert ? ' chat-alert' : '') + '">' +
            (m.alert ? '<div class="chat-alert-note"><i class="bi bi-exclamation-triangle"></i> ' + esc(m.alert) + '</div>' : '') +
            '<div class="small fw-semibold mb-1">' + esc(fromPartner ? (platform ? PLATFORM_LABEL : partnerName(t.partner_id)) : t.client_name) + '</div>' + esc(m.text || '');
        (m.attachments || []).forEach(function (att) { html += attachmentHtml(att); });
        html += '<span class="chat-time">' + fmtDate(m.date, true) + (m.is_read ? '' : ' · не прочитано') + '</span></div>';
        $s.append(html);
    });
    $s.scrollTop($s[0].scrollHeight);
}
