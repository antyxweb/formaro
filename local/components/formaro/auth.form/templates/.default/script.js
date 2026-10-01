/* formaro:auth.form — вход, регистрация, восстановление и смена пароля
   (POST /local/ajax/auth.php). Вид переключается без перезагрузки; после
   входа/регистрации — переход по data-backurl или перезагрузка страницы
   (без параметров смены пароля). Скрипт подключается в <head> — запуск
   после загрузки разметки. */
document.addEventListener('DOMContentLoaded', function () {
    var root = document.getElementById('auth-form');
    if (!root) return;

    function show(view) {
        root.querySelectorAll('.js-auth-view').forEach(function (form) {
            form.hidden = form.getAttribute('data-view') !== view;
        });
        root.querySelector('.js-auth-done').hidden = true;
        root.querySelectorAll('.auth-form__tab').forEach(function (tab) {
            tab.setAttribute('aria-selected', tab.getAttribute('data-view') === view ? 'true' : 'false');
        });
        var first = root.querySelector('.js-auth-view[data-view="' + view + '"] input:not([type="hidden"])');
        if (first) first.focus();
    }

    function done(text) {
        root.querySelectorAll('.js-auth-view').forEach(function (form) { form.hidden = true; });
        root.querySelector('.js-auth-done-text').textContent = text;
        root.querySelector('.js-auth-done').hidden = false;
    }

    function goOn() {
        var back = root.getAttribute('data-backurl');
        if (back) {
            window.location.href = back;
        } else {
            window.location.href = window.location.pathname;
        }
    }

    root.addEventListener('click', function (e) {
        var tab = e.target.closest('.js-auth-tab');
        if (!tab) return;
        e.preventDefault();
        show(tab.getAttribute('data-view'));
    });

    root.querySelectorAll('.js-auth-view').forEach(function (form) {
        var error = form.querySelector('.js-auth-error');
        var button = form.querySelector('.js-auth-submit');
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            error.textContent = '';
            if (!form.checkValidity()) {
                var invalid = form.querySelector(':invalid');
                invalid.focus();
                var label = form.querySelector('label[for="' + invalid.id + '"]');
                error.textContent = invalid.type === 'checkbox'
                    ? 'Нужно согласие на обработку персональных данных'
                    : 'Проверьте поле «' + (label ? label.textContent.replace(/\s*\*.*$/, '') : '') + '»';
                return;
            }
            var repeat = form.querySelector('[name="password_repeat"]');
            if (repeat && repeat.value !== form.querySelector('[name="password"]').value) {
                error.textContent = 'Пароли не совпадают';
                return;
            }

            var body = new FormData(form);
            body.append('sessid', root.getAttribute('data-sessid'));
            body.append('action', form.getAttribute('data-action'));
            button.disabled = true;
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '/local/ajax/auth.php', true);
            xhr.onload = function () {
                var data = null;
                try { data = JSON.parse(xhr.responseText); } catch (err) { /* пусто */ }
                button.disabled = false;
                if (xhr.status !== 200 || !data || data.error) {
                    error.textContent = (data && data.error) || 'Не удалось выполнить действие, попробуйте ещё раз';
                    return;
                }
                if (data.ok) {
                    goOn();
                } else if (data.message) {
                    done(data.message);
                }
            };
            xhr.onerror = function () {
                button.disabled = false;
                error.textContent = 'Нет связи с сервером, попробуйте ещё раз';
            };
            xhr.send(body);
        });
    });
});
