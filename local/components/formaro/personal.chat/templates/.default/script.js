/* formaro:personal.chat — «Чаты и сообщения» покупателя.

   Данные — window.FORMARO_CHAT (class.php): диалоги, открытый диалог
   (active), продавец для нового диалога (newPartner — диалога с ним ещё
   нет, создастся первым сообщением), контекст (заказ/товар).
   Запросы — POST /local/ajax/chat.php: send, read, list (опрос раз в 10 с,
   пока вкладка видна). Ответы продавца в открытом диалоге отмечаются
   прочитанными; счётчик в меню кабинета — [data-messages-count].
   Файлы — data-URL (как в чате кабинета партнёра), до 5 по 10 МБ.
   Bitrix подключает скрипт в <head> — запуск после загрузки разметки. */
document.addEventListener('DOMContentLoaded', function () {
    var root = document.getElementById('personal-chat');
    if (!root || !window.FORMARO_CHAT) return;

    var data = window.FORMARO_CHAT;
    var threads = data.threads || [];
    var activeId = data.active || null;
    var newPartner = activeId ? null : data.newPartner;
    var context = data.context || {};
    var files = [];
    var sending = false;

    var listEl = root.querySelector('.js-chat-threads');
    var headEl = root.querySelector('.js-chat-head');
    var messagesEl = root.querySelector('.js-chat-messages');
    var form = root.querySelector('.js-chat-compose');
    var textEl = root.querySelector('.js-chat-text');
    var fileInput = root.querySelector('.js-chat-file');
    var filesEl = root.querySelector('.js-chat-files');
    var contextEl = root.querySelector('.js-chat-context');
    var errorEl = root.querySelector('.js-chat-error');
    var sendBtn = root.querySelector('.js-chat-send');

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
        });
    }

    // Время — как на сайте (часовой пояс сервера из ISO-строки), а не
    // пересчитанное в пояс браузера: так же, как даты в заказах и уведомлениях.
    var MONTHS = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
    function parts(iso) {
        var m = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/.exec(iso || '');
        return m ? {y: +m[1], mo: +m[2], d: +m[3], time: m[4] + ':' + m[5], key: m[1] + m[2] + m[3]} : null;
    }
    function dayKey(date) {
        return '' + date.getFullYear() + ('0' + (date.getMonth() + 1)).slice(-2) + ('0' + date.getDate()).slice(-2);
    }
    function time(iso) {
        var p = parts(iso);
        return p ? p.time : '';
    }
    function day(iso) {
        var p = parts(iso);
        if (!p) return '';
        var today = new Date();
        var yesterday = new Date(); yesterday.setDate(today.getDate() - 1);
        if (p.key === dayKey(today)) return 'Сегодня';
        if (p.key === dayKey(yesterday)) return 'Вчера';
        return p.d + ' ' + MONTHS[p.mo - 1] + (p.y !== today.getFullYear() ? ' ' + p.y : '');
    }
    /** В списке диалогов: сегодня — время, раньше — дата. */
    function shortDate(iso) {
        var p = parts(iso);
        if (!p) return '';
        return p.key === dayKey(new Date()) ? p.time : ('0' + p.d).slice(-2) + '.' + ('0' + p.mo).slice(-2);
    }

    function avatar(partner) {
        return partner.logo
            ? '<span class="chat__avatar" style="background-image:url(\'' + esc(partner.logo) + '\')" aria-hidden="true"></span>'
            : '<span class="chat__avatar chat__avatar--letter" aria-hidden="true">' + esc((partner.name || '?').charAt(0).toUpperCase()) + '</span>';
    }

    function activeThread() {
        return threads.filter(function (t) { return t.id === activeId; })[0] || null;
    }

    function post(params, onDone, onFail) {
        var body = new FormData();
        body.append('sessid', root.getAttribute('data-sessid'));
        Object.keys(params).forEach(function (k) { body.append(k, params[k]); });
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/local/ajax/chat.php', true);
        xhr.onload = function () {
            var res = null;
            try { res = JSON.parse(xhr.responseText); } catch (err) { /* пусто */ }
            if (xhr.status === 200 && res && !res.error) {
                if (typeof res.unread === 'number') setUnread(res.unread);
                onDone(res);
            } else if (onFail) {
                onFail((res && res.error) || 'Не удалось отправить, попробуйте ещё раз');
            }
        };
        xhr.onerror = function () { if (onFail) onFail('Нет связи с сервером, попробуйте ещё раз'); };
        xhr.send(body);
    }

    function setUnread(count) {
        document.querySelectorAll('[data-messages-count]').forEach(function (node) { node.textContent = count; });
    }

    /* ---------- Список диалогов ---------- */

    function renderList() {
        var html = '';
        if (newPartner) {
            html += threadItem({id: 'new', partner: newPartner, messages: [], unread: 0, last_date: ''}, true);
        }
        threads.forEach(function (t) { html += threadItem(t, t.id === activeId); });
        if (!html) {
            html = '<div class="chat__empty-list">Диалогов пока нет. Написать продавцу можно из заказа или карточки товара — кнопка «Чат с продавцом».</div>';
        }
        listEl.innerHTML = html;
    }

    function threadItem(t, active) {
        var last = t.messages[t.messages.length - 1];
        var preview = last
            ? (last.mine ? 'Вы: ' : '') + (last.text || (last.attachments.length ? 'Файл: ' + last.attachments[0].name : ''))
            : 'Новый диалог';
        return '<button type="button" class="chat__thread' + (active ? ' is-active' : '') + (t.unread ? ' has-unread' : '') + '" role="listitem" data-thread="' + t.id + '">' +
            avatar(t.partner) +
            '<span class="chat__thread-body">' +
                '<span class="chat__thread-top"><span class="chat__thread-name">' + esc(t.partner.name) + '</span>' +
                '<span class="chat__thread-date">' + esc(t.last_date && last ? shortDate(t.last_date) : '') + '</span></span>' +
                '<span class="chat__thread-bottom"><span class="chat__thread-preview">' + esc(preview) + '</span>' +
                (t.unread ? '<span class="chat__badge" aria-label="Непрочитанных: ' + t.unread + '">' + t.unread + '</span>' : '') + '</span>' +
            '</span>' +
        '</button>';
    }

    /* ---------- Переписка ---------- */

    function renderDialog(keepScroll) {
        var thread = activeThread();
        var partner = thread ? thread.partner : newPartner;
        root.classList.toggle('is-dialog-open', !!partner);
        if (!partner) {
            headEl.innerHTML = '';
            messagesEl.innerHTML = '<div class="chat__placeholder">' +
                (threads.length ? 'Выберите диалог слева' : 'Здесь будет переписка с продавцами') + '</div>';
            form.classList.add('d-none');
            return;
        }

        headEl.innerHTML =
            '<button type="button" class="chat__back js-chat-back" aria-label="К списку диалогов">' +
                '<svg width="20" height="20" aria-hidden="true"><use xlink:href="#icon-arrow-left"></use></svg></button>' +
            avatar(partner) +
            '<div class="chat__head-body"><div class="chat__head-name">' + esc(partner.name) + '</div>' +
            (thread && (thread.order_number || thread.product_name)
                ? '<small class="text-muted">' + esc(thread.order_number ? 'Заказ ' + thread.order_number : thread.product_name) + '</small>'
                : '<small class="text-muted">Продавец</small>') +
            '</div>' +
            (partner.url ? '<a href="' + esc(partner.url) + '" class="chat__head-link">Страница продавца</a>' : '');

        var wasAtBottom = messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight < 40;
        var html = '';
        var lastDay = '';
        (thread ? thread.messages : []).forEach(function (m) {
            var d = day(m.date);
            if (d !== lastDay) {
                html += '<div class="chat__day">' + esc(d) + '</div>';
                lastDay = d;
            }
            html += '<div class="chat__msg' + (m.mine ? ' chat__msg--mine' : '') + '">';
            if (m.text) html += '<div class="chat__msg-text">' + esc(m.text) + '</div>';
            (m.attachments || []).forEach(function (a) {
                html += a.type === 'image'
                    ? '<a href="' + esc(a.data) + '" target="_blank" rel="noopener" class="chat__msg-image"><img src="' + esc(a.data) + '" alt="' + esc(a.name) + '" loading="lazy"></a>'
                    : '<a href="' + esc(a.data) + '" target="_blank" rel="noopener" class="chat__msg-file" download>' + esc(a.name) + '</a>';
            });
            html += '<span class="chat__msg-meta">' + time(m.date) +
                (m.mine ? ' <span class="chat__msg-status" title="' + (m.is_read ? 'Прочитано' : 'Доставлено') + '">' + (m.is_read ? '✓✓' : '✓') + '</span>' : '') +
                '</span></div>';
        });
        if (!html) {
            html = '<div class="chat__placeholder">Напишите продавцу — он ответит здесь. Ответ придёт и в уведомления.</div>';
        }
        messagesEl.innerHTML = html;
        if (!keepScroll || wasAtBottom) messagesEl.scrollTop = messagesEl.scrollHeight;

        form.classList.remove('d-none');
        contextEl.textContent = context.label || '';
        contextEl.classList.toggle('d-none', !context.label);
    }

    function open(id) {
        activeId = id;
        if (id !== null) newPartner = null;
        context = {};
        errorEl.textContent = '';
        renderList();
        renderDialog(false);
        markRead();
        var url = id ? '/personal/messages/?thread=' + id : '/personal/messages/';
        if (window.history && history.replaceState) history.replaceState(null, '', url);
    }

    function markRead() {
        var thread = activeThread();
        if (!thread || !thread.unread || document.hidden) return;
        thread.unread = 0;
        thread.messages.forEach(function (m) { if (!m.mine) m.is_read = true; });
        renderList();
        post({action: 'read', thread_id: thread.id}, function () {});
    }

    /* ---------- Отправка ---------- */

    function renderFiles() {
        filesEl.innerHTML = files.map(function (f, i) {
            return '<span class="chat__file">' + esc(f.name) +
                '<button type="button" class="chat__file-remove" data-file="' + i + '" aria-label="Убрать файл ' + esc(f.name) + '">&times;</button></span>';
        }).join('');
    }

    function addFiles(list) {
        errorEl.textContent = '';
        Array.prototype.forEach.call(list, function (file) {
            if (files.length >= data.maxFiles) {
                errorEl.textContent = 'Не больше ' + data.maxFiles + ' файлов в сообщении';
                return;
            }
            if (file.size > data.maxFileSize) {
                errorEl.textContent = 'Файл «' + file.name + '» больше 10 МБ';
                return;
            }
            var reader = new FileReader();
            reader.onload = function () {
                files.push({name: file.name, type: file.type.indexOf('image/') === 0 ? 'image' : 'file', data: reader.result});
                renderFiles();
            };
            reader.readAsDataURL(file);
        });
    }

    function send() {
        var text = textEl.value.trim();
        var thread = activeThread();
        var partner = thread ? thread.partner : newPartner;
        if (sending || !partner || (!text && !files.length)) return;
        sending = true;
        sendBtn.disabled = true;
        errorEl.textContent = '';
        post({
            action: 'send',
            partner_id: partner.id,
            text: text,
            attachments: JSON.stringify(files),
            order: context.order || '',
            product: context.product || ''
        }, function (res) {
            sending = false;
            sendBtn.disabled = false;
            textEl.value = '';
            autosize();
            files = [];
            renderFiles();
            threads = [res.thread].concat(threads.filter(function (t) { return t.id !== res.thread.id; }));
            newPartner = null;
            context = {};
            activeId = res.thread.id;
            if (window.history && history.replaceState) history.replaceState(null, '', '/personal/messages/?thread=' + activeId);
            renderList();
            renderDialog(false);
            textEl.focus();
        }, function (message) {
            sending = false;
            sendBtn.disabled = false;
            errorEl.textContent = message;
        });
    }

    function autosize() {
        textEl.style.height = 'auto';
        textEl.style.height = Math.min(textEl.scrollHeight + 2, 160) + 'px';
    }

    /* ---------- Обновление ---------- */

    function refresh() {
        if (document.hidden || sending) return;
        post({action: 'list'}, function (res) {
            var before = JSON.stringify(threads);
            threads = res.threads || [];
            if (JSON.stringify(threads) === before) return;
            renderList();
            renderDialog(true);
            markRead();
        });
    }

    /* ---------- События ---------- */

    listEl.addEventListener('click', function (e) {
        var item = e.target.closest('[data-thread]');
        if (!item) return;
        var id = item.getAttribute('data-thread');
        if (id === 'new') {
            root.classList.add('is-dialog-open');
            return;
        }
        open(parseInt(id, 10));
    });

    headEl.addEventListener('click', function (e) {
        if (e.target.closest('.js-chat-back')) root.classList.remove('is-dialog-open');
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        send();
    });
    textEl.addEventListener('keydown', function (e) {
        // Enter — отправить, Shift+Enter — новая строка.
        if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
            e.preventDefault();
            send();
        }
    });
    textEl.addEventListener('input', autosize);

    root.querySelector('.js-chat-attach').addEventListener('click', function () { fileInput.click(); });
    fileInput.addEventListener('change', function () {
        addFiles(fileInput.files);
        fileInput.value = '';
    });
    filesEl.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-file]');
        if (!btn) return;
        files.splice(parseInt(btn.getAttribute('data-file'), 10), 1);
        renderFiles();
    });

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            refresh();
            markRead();
        }
    });

    renderList();
    renderDialog(false);
    markRead();
    if (activeId || newPartner) textEl.focus({preventScroll: true});
    setInterval(refresh, 10000);
});
