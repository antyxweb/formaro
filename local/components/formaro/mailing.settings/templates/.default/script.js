/* formaro:mailing.settings — «Управление рассылками». Переключатель категории
   сохраняет всю группу сразу (action=save), «Отписаться» у новости —
   action=unsubscribe, «Отписаться от всех рассылок» — action=unsubscribe_all
   (после подтверждения). POST /local/ajax/mailing.php; со страницы по ссылке
   из письма — с u и t из ссылки. */
(function () {
    function post(root, params, done) {
        var body = ['sessid=' + encodeURIComponent(root.getAttribute('data-sessid'))];
        if (root.getAttribute('data-u')) {
            body.push('u=' + encodeURIComponent(root.getAttribute('data-u')), 't=' + encodeURIComponent(root.getAttribute('data-t')));
        }
        Object.keys(params).forEach(function (key) {
            [].concat(params[key]).forEach(function (value) {
                body.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
            });
        });
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/local/ajax/mailing.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            var data = null;
            try { data = JSON.parse(xhr.responseText); } catch (err) { /* пусто */ }
            if (xhr.status !== 200 || !data || data.error) {
                done((data && data.error) || 'Не удалось сохранить, попробуйте ещё раз', null);
                return;
            }
            done(null, data);
        };
        xhr.onerror = function () { done('Нет связи с сервером, попробуйте ещё раз', null); };
        xhr.send(body.join('&'));
    }

    function show(status, text, ok) {
        if (!status) return;
        status.textContent = text;
        status.classList.toggle('text-success', !!ok);
        status.classList.toggle('text-danger', !ok);
    }

    /* Состояние с сервера → переключатели и список подписок. */
    function apply(root, state) {
        state.groups.forEach(function (group) {
            group.items.forEach(function (item) {
                var input = root.querySelector('.js-mailing-channel[value="' + item.channel + '"]');
                if (input) input.checked = item.enabled;
            });
        });
        var ids = state.newsletters.map(function (item) { return String(item.id); });
        Array.prototype.forEach.call(root.querySelectorAll('.js-mailing-newsletters li'), function (li) {
            if (ids.indexOf(li.getAttribute('data-id')) === -1) li.parentNode.removeChild(li);
        });
        var empty = root.querySelector('.js-mailing-newsletters-empty');
        if (empty) empty.classList.toggle('d-none', ids.length > 0);
    }

    function init(root) {
        root.addEventListener('change', function (e) {
            var input = e.target.closest('.js-mailing-channel');
            if (!input) return;
            var group = input.closest('.js-mailing-group');
            var status = group.querySelector('.js-mailing-status');
            var inputs = group.querySelectorAll('.js-mailing-channel');
            var enabled = [];
            Array.prototype.forEach.call(inputs, function (el) {
                el.disabled = true;
                if (el.checked) enabled.push(el.value);
            });
            show(status, '', true);
            post(root, {action: 'save', audience: group.getAttribute('data-audience'), 'channels[]': enabled}, function (error, data) {
                Array.prototype.forEach.call(inputs, function (el) { el.disabled = false; });
                if (error) {
                    input.checked = !input.checked;
                    show(status, error, false);
                    return;
                }
                apply(root, data.state);
                show(status, 'Сохранено', true);
            });
        });

        root.addEventListener('click', function (e) {
            var button = e.target.closest('.js-mailing-unsubscribe');
            if (button) {
                var li = button.closest('li');
                button.disabled = true;
                post(root, {action: 'unsubscribe', id: li.getAttribute('data-id')}, function (error, data) {
                    button.disabled = false;
                    if (error) {
                        show(root.querySelector('.js-mailing-all-status'), error, false);
                        return;
                    }
                    apply(root, data.state);
                });
                return;
            }

            var all = e.target.closest('.js-mailing-unsubscribe-all');
            if (!all) return;
            var status = root.querySelector('.js-mailing-all-status');
            var ask = window.FormaroConfirm
                ? window.FormaroConfirm({
                    title: 'Отписаться от всех рассылок?',
                    message: 'Перестанем присылать уведомления о заказах, сообщениях и новостях. Служебные письма — пароль, оформление заказа, счета — будут приходить.',
                    okText: 'Отписаться',
                    danger: true
                })
                : Promise.resolve(window.confirm('Отписаться от всех рассылок?'));
            ask.then(function (ok) {
                if (!ok) return;
                all.disabled = true;
                post(root, {action: 'unsubscribe_all'}, function (error, data) {
                    all.disabled = false;
                    if (error) {
                        show(status, error, false);
                        return;
                    }
                    apply(root, data.state);
                    show(status, 'Вы отписались от всех рассылок', true);
                });
            });
        });
    }

    function start() {
        var root = document.getElementById('mailing-settings');
        if (root) init(root);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
