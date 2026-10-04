/* Чаты покупателей с партнёрами — просмотр для администратора (по образцу
   chat-clients.js кабинета партнёра, без ответа и отметки прочитанным).
   ?partner=ID — фильтр, ?thread=ID — открыть диалог, ?alerts=1 — только
   диалоги с нарушениями. Сообщения с контактами/оплатой/сделкой в обход
   площадки помечены (alert). Обновление — раз в 30 с. */
var allThreads = [];
var activeThreadId = 0;
var refreshClearBtn = null;

$(function () {
    fillPartnerSelect('#partnerFilter');
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
            if (t) renderMessages(t);
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
function threadSubject(t) {
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
              '<div class="small text-truncate"><i class="bi bi-shop"></i> ' + esc(partnerName(t.partner_id)) +
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
    renderThreads();
    renderMessages(t);
}

function renderMessages(t) {
    $('#threadHead').removeClass('d-none').html(
        '<b>' + esc(t.client_name) + '</b> ↔ ' + partnerCell(t.partner_id) + ' · ' + esc(threadSubject(t))
    );
    $('#readOnlyNote').removeClass('d-none');
    var $s = $('#messagesScroll').empty();
    t.messages.forEach(function (m) {
        var fromPartner = m.sender === 'partner';
        var html = '<div class="chat-bubble ' + (fromPartner ? 'out' : 'in') + (m.alert ? ' chat-alert' : '') + '">' +
            (m.alert ? '<div class="chat-alert-note"><i class="bi bi-exclamation-triangle"></i> ' + esc(m.alert) + '</div>' : '') +
            '<div class="small fw-semibold mb-1">' + esc(fromPartner ? partnerName(t.partner_id) : t.client_name) + '</div>' + esc(m.text || '');
        (m.attachments || []).forEach(function (att) { html += attachmentHtml(att); });
        html += '<span class="chat-time">' + fmtDate(m.date, true) + (m.is_read ? '' : ' · не прочитано') + '</span></div>';
        $s.append(html);
    });
    $s.scrollTop($s[0].scrollHeight);
}
