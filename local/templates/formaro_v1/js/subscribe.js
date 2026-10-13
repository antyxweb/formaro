/* Подписка на рассылку — формы .js-subscribe: «Будьте в курсе»
   (data-form="news") и «Будь всегда в форме!» в подвале (data-form="footer").
   POST /local/ajax/subscribe.php (HL-блок «Подписки на рассылку»); ключ
   сессии — BX.bitrix_sessid(): формы выводятся кэшируемыми компонентами, в
   их вёрстку сессию не положить. Итог — под кнопкой.

   «Отписаться от рассылки» (.js-subscribe-mode) переключает форму в режим
   отписки: тема и согласие (.js-subscribe-only) скрываются и не уходят,
   запрос — с mode=unsubscribe. */
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
        if (form.classList.contains('is-unsubscribe')) body.append('mode', 'unsubscribe');
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

    /* Подпись кнопки ↔ data-unsubscribe-text: исходная хранится в data-subscribe-text. */
    function swapText(button, unsubscribe) {
        if (!button.hasAttribute('data-subscribe-text')) button.setAttribute('data-subscribe-text', button.textContent);
        button.textContent = button.getAttribute(unsubscribe ? 'data-unsubscribe-text' : 'data-subscribe-text');
    }

    document.addEventListener('click', function (e) {
        var toggle = e.target.closest && e.target.closest('.js-subscribe-mode');
        if (!toggle) return;
        var form = toggle.closest('.js-subscribe');
        var unsubscribe = !form.classList.contains('is-unsubscribe');
        form.classList.toggle('is-unsubscribe', unsubscribe);
        Array.prototype.forEach.call(form.querySelectorAll('.js-subscribe-only'), function (block) {
            block.classList.toggle('d-none', unsubscribe);
            Array.prototype.forEach.call(block.querySelectorAll('input, select'), function (input) { input.disabled = unsubscribe; });
        });
        swapText(form.querySelector('.js-subscribe-submit'), unsubscribe);
        swapText(toggle, unsubscribe);
        var status = form.querySelector('.js-subscribe-status');
        status.textContent = '';
        status.classList.remove('is-error', 'is-success');
        form.querySelector('[name="email"]').focus();
    });

    document.addEventListener('submit', function (e) {
        var form = e.target.closest && e.target.closest('.js-subscribe');
        if (!form) return;
        e.preventDefault();
        send(form);
    });
})();
