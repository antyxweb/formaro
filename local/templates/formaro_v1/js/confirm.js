/* Окно подтверждения действия в стиле сайта — вместо window.confirm.

   FormaroConfirm({title, message, okText, cancelText, danger}) → Promise<boolean>:
   true — нажали кнопку действия, false — «Отмена», Esc, клик мимо окна.
   danger — красная кнопка действия (удалить, отменить), фокус тогда — на
   «Отмене», иначе — на кнопке действия; из окна не уходит (Tab), после
   закрытия возвращается туда, где был. Стили — css/confirm.css. */
(function () {
    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text) node.textContent = text;
        return node;
    }

    window.FormaroConfirm = function (options) {
        options = options || {};
        return new Promise(function (resolve) {
            var previous = document.activeElement;
            var overlay = el('div', 'f-confirm');
            var dialog = el('div', 'f-confirm__dialog');
            dialog.setAttribute('role', 'alertdialog');
            dialog.setAttribute('aria-modal', 'true');

            var title = el('h4', 'f-confirm__title', options.title || 'Подтвердите действие');
            title.id = 'f-confirm-title-' + Date.now();
            dialog.setAttribute('aria-labelledby', title.id);
            dialog.appendChild(title);
            if (options.message) {
                var message = el('p', 'f-confirm__message', options.message);
                message.id = title.id + '-message';
                dialog.setAttribute('aria-describedby', message.id);
                dialog.appendChild(message);
            }

            var actions = el('div', 'f-confirm__actions');
            var ok = el('button', 'f-button ' + (options.danger ? 'c-danger' : 'c-primary'), options.okText || 'Подтвердить');
            var cancel = el('button', 'f-button c-gray text-secondary', options.cancelText || 'Отмена');
            ok.type = cancel.type = 'button';
            actions.appendChild(ok);
            actions.appendChild(cancel);
            dialog.appendChild(actions);
            overlay.appendChild(dialog);

            var close = function (result) {
                document.removeEventListener('keydown', onKey, true);
                overlay.classList.remove('is-open');
                document.body.classList.remove('f-confirm-open');
                setTimeout(function () { overlay.remove(); }, 200);
                if (previous && previous.focus) previous.focus({preventScroll: true});
                resolve(result);
            };
            var onKey = function (e) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    close(false);
                } else if (e.key === 'Tab') {
                    // Фокус ходит только между двумя кнопками окна.
                    e.preventDefault();
                    (document.activeElement === ok ? cancel : ok).focus();
                }
            };

            ok.addEventListener('click', function () { close(true); });
            cancel.addEventListener('click', function () { close(false); });
            overlay.addEventListener('click', function (e) { if (e.target === overlay) close(false); });
            document.addEventListener('keydown', onKey, true);

            document.body.appendChild(overlay);
            document.body.classList.add('f-confirm-open');
            window.requestAnimationFrame(function () { overlay.classList.add('is-open'); });
            // У опасного действия фокус — на «Отмене»: случайный Enter ничего не сломает.
            (options.danger ? cancel : ok).focus();
        });
    };
})();
