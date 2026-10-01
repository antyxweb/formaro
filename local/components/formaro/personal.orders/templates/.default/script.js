/* formaro:personal.orders — отмена нового заказа покупателем и «Сообщить
   об оплате». Подтверждение — окном сайта (FormaroConfirm, js/confirm.js).

   «Отменить заказ» → POST /local/ajax/order_cancel.php; после успеха бейдж
   статуса — «Отменён», кнопка отмены и ссылки оплаты (счёт, «Сообщить об
   оплате») убираются. Ошибка (например, продавец уже взял заказ в работу) —
   рядом с кнопками.

   «Сообщить об оплате» → POST /local/ajax/order_payment_notice.php; после —
   «Вы сообщили об оплате …» вместо ссылок на счёт и сообщение.

   «Показать / Скрыть товары» — карусель товаров заказа (по умолчанию
   скрыта). Flickity запускается scripts.js на скрытой карусели и меряет
   нули — после показа пересчитываем размеры (resize).

   «История заказа» — во всплывающем окне (FormaroModal, js/confirm.js);
   записи — из <template> заказа, отмена и сообщение об оплате дописывают
   туда новую запись сверху. */
(function () {
    function post(url, id, onDone, onFail) {
        var root = document.getElementById('personal-orders');
        var xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            var data = null;
            try { data = JSON.parse(xhr.responseText); } catch (err) { /* пусто */ }
            if (xhr.status === 200 && data && !data.error) {
                onDone(data);
            } else {
                onFail((data && data.error) || '');
            }
        };
        xhr.onerror = function () { onFail('Нет связи с сервером, попробуйте ещё раз'); };
        xhr.send('sessid=' + encodeURIComponent(root.getAttribute('data-sessid')) + '&id=' + encodeURIComponent(id));
    }

    function cancelOrder(button) {
        var order = button.closest('.order-item');
        var error = button.closest('.js-order-actions').querySelector('.js-order-cancel-error');
        button.disabled = true;
        error.textContent = '';
        post('/local/ajax/order_cancel.php', button.getAttribute('data-order-id'), function () {
            var badge = order.querySelector('.js-order-status');
            badge.className = badge.className.replace(/\bbg-\S+/, 'bg-secondary');
            badge.textContent = 'Отменён';
            addHistory(order, 'Заказ отменён покупателем');
            button.remove();
            // Отменённый — без счёта и «Сообщить об оплате».
            var payment = order.querySelector('.js-order-payment');
            if (payment) payment.remove();
        }, function (message) {
            button.disabled = false;
            error.textContent = message || 'Не удалось отменить заказ, попробуйте ещё раз';
        });
    }

    function reportPayment(link) {
        var error = link.closest('.js-order-payment').querySelector('.js-order-payment-error');
        link.setAttribute('aria-disabled', 'true');
        error.textContent = '';
        post('/local/ajax/order_payment_notice.php', link.getAttribute('data-order-id'), function (data) {
            var note = document.createElement('small');
            note.className = 'text-muted js-order-payment-notice';
            note.textContent = 'Вы сообщили об оплате ' + data.date;
            // Счёт больше не нужен — ссылку на него убираем.
            var invoice = link.closest('.js-order-payment').querySelector('.js-order-invoice');
            if (invoice) invoice.remove();
            addHistory(link.closest('.order-item'), 'Вы сообщили об оплате');
            link.replaceWith(note);
        }, function (message) {
            link.removeAttribute('aria-disabled');
            error.textContent = message || 'Не удалось отправить, попробуйте ещё раз';
        });
    }

    // Новая запись истории сверху — после отмены и сообщения об оплате.
    function addHistory(order, text) {
        var list = order.querySelector('.js-order-history-list').content.querySelector('.order-history');
        var item = document.createElement('li');
        item.className = 'order-history__item';
        var textNode = document.createElement('div');
        textNode.className = 'order-history__text';
        textNode.textContent = text;
        var meta = document.createElement('small');
        meta.className = 'text-muted';
        meta.textContent = 'Вы · только что';
        item.appendChild(textNode);
        item.appendChild(meta);
        list.insertBefore(item, list.firstChild);
    }

    function showHistory(link) {
        var list = link.parentNode.querySelector('.js-order-history-list').content.cloneNode(true);
        window.FormaroModal({title: 'История заказа ' + link.getAttribute('data-order-number'), content: list});
    }

    function toggleItems(link) {
        var items = document.getElementById(link.getAttribute('aria-controls'));
        var open = items.hidden;
        items.hidden = !open;
        link.setAttribute('aria-expanded', open ? 'true' : 'false');
        link.querySelector('.js-order-items-toggle-text').textContent = open ? 'Скрыть товары' : 'Показать товары';
        if (open && window.jQuery) {
            var $carousel = window.jQuery(items).find('.product-carousel');
            if ($carousel.data('flickity')) $carousel.flickity('resize');
        }
    }

    document.addEventListener('click', function (e) {
        var history = e.target.closest && e.target.closest('.js-order-history');
        if (history) {
            e.preventDefault();
            showHistory(history);
            return;
        }

        var toggle = e.target.closest && e.target.closest('.js-order-items-toggle');
        if (toggle) {
            e.preventDefault();
            toggleItems(toggle);
            return;
        }

        var button = e.target.closest && e.target.closest('.js-order-cancel');
        if (button && !button.disabled) {
            window.FormaroConfirm({
                title: 'Отменить заказ ' + button.getAttribute('data-order-number') + '?',
                message: 'Продавец получит уведомление об отмене. Вернуть заказ будет нельзя.',
                okText: 'Отменить заказ',
                cancelText: 'Не отменять',
                danger: true
            }).then(function (ok) { if (ok) cancelOrder(button); });
            return;
        }

        var link = e.target.closest && e.target.closest('a.js-order-payment-notice');
        if (link) {
            e.preventDefault();
            if (link.getAttribute('aria-disabled') === 'true') return;
            window.FormaroConfirm({
                title: 'Сообщить об оплате?',
                message: 'Сообщите, когда оплатите счёт по заказу ' + link.getAttribute('data-order-number') + '. Маркетплейс проверит поступление и подтвердит оплату.',
                okText: 'Сообщить',
                cancelText: 'Не сейчас'
            }).then(function (ok) { if (ok) reportPayment(link); });
        }
    });
})();
