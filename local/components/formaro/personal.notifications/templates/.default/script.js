/* formaro:personal.notifications — действия с уведомлениями покупателя
   (POST /local/ajax/notifications.php):
   - клик по уведомлению (кроме флажка и «Удалить») — отметить прочитанным
     и перейти по ссылке (к заказу); переход — сразу, не дожидаясь ответа;
   - флажки: «Выбрать все», «Отметить прочитанными» и «Удалить» для
     выбранных (кнопки видны, когда что-то выбрано);
   - «Прочитать все»; «Удалить» у отдельного уведомления.
   Удаление — с подтверждением (FormaroConfirm, js/confirm.js). Счётчик
   непрочитанных в меню личного кабинета ([data-notify-count]) — из ответа.
   Bitrix подключает скрипт в <head> — запуск после загрузки разметки. */
document.addEventListener('DOMContentLoaded', function () {
    var root = document.getElementById('personal-notify');
    if (!root || !root.querySelector('.notify-list')) return;

    var list = root.querySelector('.notify-list');
    var selectAll = root.querySelector('.js-notify-select-all');
    var selectedActions = root.querySelector('.js-notify-selected');
    var readAll = root.querySelector('.js-notify-read-all');
    var error = root.querySelector('.js-notify-error');

    function items() {
        return Array.prototype.slice.call(list.querySelectorAll('.js-notify-item'));
    }

    function selectedIds() {
        return items().filter(function (item) {
            return item.querySelector('.js-notify-choose').checked;
        }).map(function (item) { return item.getAttribute('data-id'); });
    }

    function post(action, ids, onDone) {
        var body = 'sessid=' + encodeURIComponent(root.getAttribute('data-sessid')) + '&action=' + encodeURIComponent(action);
        (ids || []).forEach(function (id) { body += '&ids[]=' + encodeURIComponent(id); });
        error.textContent = '';
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/local/ajax/notifications.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            var data = null;
            try { data = JSON.parse(xhr.responseText); } catch (err) { /* пусто */ }
            if (xhr.status === 200 && data && !data.error) {
                setUnread(data.unread);
                if (onDone) onDone(data);
            } else {
                error.textContent = (data && data.error) || 'Не удалось выполнить действие, попробуйте ещё раз';
            }
        };
        xhr.onerror = function () { error.textContent = 'Нет связи с сервером, попробуйте ещё раз'; };
        xhr.send(body);
    }

    function setUnread(count) {
        document.querySelectorAll('[data-notify-count]').forEach(function (node) {
            node.textContent = count;
        });
        readAll.classList.toggle('d-none', !count);
    }

    function markRead(ids) {
        ids.forEach(function (id) {
            var item = list.querySelector('.js-notify-item[data-id="' + id + '"]');
            if (item) item.classList.remove('is-unread');
        });
    }

    function remove(ids) {
        ids.forEach(function (id) {
            var item = list.querySelector('.js-notify-item[data-id="' + id + '"]');
            if (item) item.remove();
        });
        if (!items().length) {
            root.querySelector('.notify-toolbar').remove();
            list.remove();
            root.querySelector('.notify-empty').classList.remove('d-none');
            return;
        }
        refreshSelection();
    }

    function refreshSelection() {
        var all = items();
        var n = selectedIds().length;
        selectedActions.classList.toggle('d-none', n === 0);
        selectAll.checked = n > 0 && n === all.length;
        selectAll.indeterminate = n > 0 && n < all.length;
    }

    function confirmDelete(ids) {
        window.FormaroConfirm({
            title: ids.length > 1 ? 'Удалить уведомления (' + ids.length + ')?' : 'Удалить уведомление?',
            message: 'Восстановить их будет нельзя.',
            okText: 'Удалить',
            cancelText: 'Не удалять',
            danger: true
        }).then(function (ok) {
            if (ok) post('delete', ids, function (data) { remove(data.ids.map(String)); });
        });
    }

    selectAll.addEventListener('change', function () {
        items().forEach(function (item) { item.querySelector('.js-notify-choose').checked = selectAll.checked; });
        refreshSelection();
    });

    root.querySelector('.js-notify-read-selected').addEventListener('click', function () {
        var ids = selectedIds();
        post('read', ids, function () { markRead(ids); });
    });

    root.querySelector('.js-notify-delete-selected').addEventListener('click', function () {
        confirmDelete(selectedIds());
    });

    readAll.addEventListener('click', function (e) {
        e.preventDefault();
        post('read_all', [], function () { markRead(items().map(function (item) { return item.getAttribute('data-id'); })); });
    });

    list.addEventListener('change', function (e) {
        if (e.target.classList.contains('js-notify-choose')) refreshSelection();
    });

    list.addEventListener('click', function (e) {
        var item = e.target.closest('.js-notify-item');
        if (!item) return;
        var id = item.getAttribute('data-id');

        if (e.target.closest('.js-notify-delete')) {
            confirmDelete([id]);
            return;
        }
        if (e.target.closest('.notify-item__check')) return;

        var link = item.getAttribute('data-link');
        var unread = item.classList.contains('is-unread');
        if (unread) {
            markRead([id]);
            // Переход не ждёт ответа: запрос уходит через sendBeacon и
            // доживает до новой страницы.
            var data = new FormData();
            data.append('sessid', root.getAttribute('data-sessid'));
            data.append('action', 'read');
            data.append('ids[]', id);
            if (link && navigator.sendBeacon) {
                navigator.sendBeacon('/local/ajax/notifications.php', data);
            } else {
                post('read', [id]);
            }
        }
        if (link) {
            e.preventDefault();
            window.location.href = link;
        }
    });
});
