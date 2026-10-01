/* formaro:partner.register — регистрация партнёра (POST
   /local/ajax/partner_register.php): форма гостя или, у вошедшего
   покупателя, кнопка «Создать кабинет партнёра» с окном подтверждения
   (FormaroConfirm) — данные берутся из профиля. Успех — переход в профиль
   кабинета партнёра; ошибка — рядом с кнопкой. Скрипт подключается в
   <head> — запуск после загрузки разметки. */
function initCreate() {
    var block = document.querySelector('.js-partner-create');
    if (!block) return;
    var button = block.querySelector('.js-partner-create-submit');
    var error = block.querySelector('.js-partner-create-error');

    button.addEventListener('click', function () {
        window.FormaroConfirm({
            title: 'Создать кабинет партнёра?',
            message: 'Данные вашего профиля перенесутся в данные партнёра. Входить в кабинет партнёра — с тем же e-mail и паролем.',
            okText: 'Да, подтверждаю',
            cancelText: 'Отмена'
        }).then(function (ok) {
            if (!ok) return;
            error.textContent = '';
            button.disabled = true;
            var body = new FormData();
            body.append('sessid', block.getAttribute('data-sessid'));
            body.append('action', 'from_profile');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '/local/ajax/partner_register.php', true);
            xhr.onload = function () {
                var data = null;
                try { data = JSON.parse(xhr.responseText); } catch (err) { /* пусто */ }
                if (xhr.status === 200 && data && data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                button.disabled = false;
                error.textContent = (data && data.error) || 'Не удалось создать кабинет, попробуйте ещё раз';
            };
            xhr.onerror = function () {
                button.disabled = false;
                error.textContent = 'Нет связи с сервером, попробуйте ещё раз';
            };
            xhr.send(body);
        });
    });
}

document.addEventListener('DOMContentLoaded', function () {
    initCreate();
    var form = document.querySelector('.js-partner-register');
    if (!form) return;
    var button = form.querySelector('.js-partner-register-submit');
    var error = form.querySelector('.js-partner-register-error');

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        error.textContent = '';
        // Сначала — встроенная проверка браузера (обязательные поля, e-mail, ИНН).
        if (!form.checkValidity()) {
            var invalid = form.querySelector(':invalid');
            if (invalid) {
                invalid.focus();
                error.textContent = invalid.type === 'checkbox'
                    ? 'Нужно согласие на обработку персональных данных'
                    : 'Проверьте поле «' + (form.querySelector('label[for="' + invalid.id + '"]') || {}).textContent.replace(/\s*\*.*$/, '') + '»';
            }
            return;
        }
        var password = form.querySelector('[name="password"]');
        if (password && password.value !== form.querySelector('[name="password_repeat"]').value) {
            error.textContent = 'Пароли не совпадают';
            return;
        }

        var body = new FormData(form);
        body.append('sessid', form.getAttribute('data-sessid'));
        button.disabled = true;
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/local/ajax/partner_register.php', true);
        xhr.onload = function () {
            var data = null;
            try { data = JSON.parse(xhr.responseText); } catch (err) { /* пусто */ }
            if (xhr.status === 200 && data && data.redirect) {
                window.location.href = data.redirect;
                return;
            }
            button.disabled = false;
            error.textContent = (data && data.error) || 'Не удалось отправить заявку, попробуйте ещё раз';
        };
        xhr.onerror = function () {
            button.disabled = false;
            error.textContent = 'Нет связи с сервером, попробуйте ещё раз';
        };
        xhr.send(body);
    });
});
