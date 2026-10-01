/* formaro:personal.orders — отмена нового заказа покупателем и «Сообщить
   об оплате». Подтверждение — окном сайта (FormaroConfirm, js/confirm.js).

   «Отменить заказ» → POST /local/ajax/order_cancel.php; после успеха бейдж
   статуса — «Отменён», кнопка отмены и ссылки оплаты (счёт, «Сообщить об
   оплате») убираются. Ошибка (например, продавец уже взял заказ в работу) —
   рядом с кнопками.

   «Сообщить об оплате» → POST /local/ajax/order_payment_notice.php; после —
   «Вы сообщили об оплате …» вместо ссылок на счёт и сообщение. */
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
            link.replaceWith(note);
        }, function (message) {
            link.removeAttribute('aria-disabled');
            error.textContent = message || 'Не удалось отправить, попробуйте ещё раз';
        });
    }

    document.addEventListener('click', function (e) {
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
