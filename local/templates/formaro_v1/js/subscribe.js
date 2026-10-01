/* Подписка на рассылку — формы .js-subscribe: «Будьте в курсе»
   (data-form="news") и «Будь всегда в форме!» в подвале (data-form="footer").
   POST /local/ajax/subscribe.php (HL-блок «Подписки на рассылку»); ключ
   сессии — BX.bitrix_sessid(): формы выводятся кэшируемыми компонентами, в
   их вёрстку сессию не положить. Итог — под кнопкой. */
(function () {
    function send(form) {
        var status = form.querySelector('.js-subscribe-status');
        var button = form.querySelector('.js-subscribe-submit');
        var show = function (text, ok) {
            status.textContent = text;
            status.classList.toggle('is-error', !ok);
            status.classList.toggle('is-success', !!ok);
        };

        if (!form.checkValidity()) {
            var invalid = form.querySelector(':invalid');
            invalid.focus();
            show(invalid.type === 'checkbox' ? 'Нужно согласие с политикой обработки персональных данных' : 'Проверьте e-mail', false);
            return;
        }

        var body = new FormData(form);
        body.append('form', form.getAttribute('data-form'));
        body.append('page', window.location.pathname);
        body.append('sessid', window.BX && BX.bitrix_sessid ? BX.bitrix_sessid() : '');
        button.disabled = true;
        show('', true);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/local/ajax/subscribe.php', true);
        xhr.onload = function () {
            var data = null;
            try { data = JSON.parse(xhr.responseText); } catch (err) { /* пусто */ }
            button.disabled = false;
            if (xhr.status === 200 && data && data.message) {
                show(data.message, true);
                form.querySelector('[name="email"]').value = '';
            } else {
                show((data && data.error) || 'Не удалось оформить подписку, попробуйте ещё раз', false);
            }
        };
        xhr.onerror = function () {
            button.disabled = false;
            show('Нет связи с сервером, попробуйте ещё раз', false);
        };
        xhr.send(body);
    }

    document.addEventListener('submit', function (e) {
        var form = e.target.closest && e.target.closest('.js-subscribe');
        if (!form) return;
        e.preventDefault();
        send(form);
    });
})();
