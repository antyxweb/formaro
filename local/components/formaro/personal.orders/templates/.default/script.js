/* formaro:personal.orders — отмена нового заказа покупателем и «Сообщить
   об оплате».

   «Отменить заказ» → подтверждение → POST /local/ajax/order_cancel.php;
   после успеха бейдж статуса — «Отменён», кнопки отмены и счёта
   убираются. Ошибка (например, продавец уже взял заказ в работу) — рядом
   с кнопками. */
document.addEventListener('click', function (e) {
    var button = e.target.closest && e.target.closest('.js-order-cancel');
    if (!button || button.disabled) return;
    var root = document.getElementById('personal-orders');
    var wrap = button.closest('.js-order-actions');
    var error = wrap.querySelector('.js-order-cancel-error');
    if (!window.confirm('Отменить заказ ' + button.getAttribute('data-order-number') + '?')) return;

    button.disabled = true;
    error.textContent = '';
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/local/ajax/order_cancel.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
        var data = null;
        try { data = JSON.parse(xhr.responseText); } catch (err) { /* пусто */ }
        if (xhr.status === 200 && data && data.order) {
            var badge = button.closest('.order-item').querySelector('.js-order-status');
            badge.className = badge.className.replace(/\bbadge-\S+/, 'badge-secondary');
            badge.textContent = 'Отменён';
            button.remove();
            // Отменённый — без счёта и «Сообщить об оплате».
            var payment = button.closest('.order-item').querySelector('.js-order-payment');
            if (payment) payment.remove();
            return;
        }
        button.disabled = false;
        error.textContent = (data && data.error) || 'Не удалось отменить заказ, попробуйте ещё раз';
    };
    xhr.onerror = function () {
        button.disabled = false;
        error.textContent = 'Нет связи с сервером, попробуйте ещё раз';
    };
    xhr.send('sessid=' + encodeURIComponent(root.getAttribute('data-sessid')) + '&id=' + encodeURIComponent(button.getAttribute('data-order-id')));
});

/* «Сообщить об оплате» → подтверждение → POST
   /local/ajax/order_payment_notice.php; после — «Вы сообщили об оплате …». */
document.addEventListener('click', function (e) {
    var link = e.target.closest && e.target.closest('a.js-order-payment-notice');
    if (!link) return;
    e.preventDefault();
    if (link.getAttribute('aria-disabled') === 'true') return;
    var root = document.getElementById('personal-orders');
    var error = link.closest('.js-order-payment').querySelector('.js-order-payment-error');
    if (!window.confirm('Сообщить продавцу, что заказ ' + link.getAttribute('data-order-number') + ' оплачен?')) return;

    link.setAttribute('aria-disabled', 'true');
    error.textContent = '';
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/local/ajax/order_payment_notice.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
        var data = null;
        try { data = JSON.parse(xhr.responseText); } catch (err) { /* пусто */ }
        if (xhr.status === 200 && data && data.date) {
            var note = document.createElement('small');
            note.className = 'text-muted js-order-payment-notice';
            note.textContent = 'Вы сообщили об оплате ' + data.date;
            link.replaceWith(note);
            return;
        }
        link.removeAttribute('aria-disabled');
        error.textContent = (data && data.error) || 'Не удалось отправить, попробуйте ещё раз';
    };
    xhr.onerror = function () {
        link.removeAttribute('aria-disabled');
        error.textContent = 'Нет связи с сервером, попробуйте ещё раз';
    };
    xhr.send('sessid=' + encodeURIComponent(root.getAttribute('data-sessid')) + '&id=' + encodeURIComponent(link.getAttribute('data-order-id')));
});
