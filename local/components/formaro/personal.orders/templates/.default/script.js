/* formaro:personal.orders — отмена нового заказа покупателем.
   «Отменить заказ» → подтверждение → POST /local/ajax/order_cancel.php;
   после успеха бейдж статуса — «Отменён», кнопка убирается. Ошибка
   (например, продавец уже взял заказ в работу) — рядом с кнопкой. */
document.addEventListener('click', function (e) {
    var button = e.target.closest && e.target.closest('.js-order-cancel');
    if (!button || button.disabled) return;
    var root = document.getElementById('personal-orders');
    var wrap = button.closest('.js-order-cancel-wrap');
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
            wrap.remove();
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
