/* Страница заказа в кабинете администратора: просмотр заказа (как в
   кабинете партнёра), статус оплаты — set_payment_status (уведомления
   покупателю и партнёру, запись в историю), комментарий площадки в
   историю — add_order_comment. */
var order = null;
var PAYMENT_LABELS = {awaiting: 'Ожидает оплаты', paid: 'Оплачен', refunded: 'Возврат'};

$(function () {
    dsLoad('orders').done(function (rows) {
        var o = rows.find(function (x) { return x.id === ROUTE_ID; });
        if (!o) { showResult(false, 'Заказ не найден'); return; }
        render(o);
    });

    $('#paymentStatusSelect').on('change', function () {
        $('#savePaymentBtn').prop('disabled', !order || $(this).val() === order.payment_status);
    });
    $('#savePaymentBtn').on('click', savePayment);
    $('#addHistoryBtn').on('click', addComment);
    $('#historyComment').on('keypress', function (e) { if (e.which === 13) addComment(); });
});

function text(sel, value) { $(sel).text(value || '—'); }

function render(o) {
    order = o;
    document.title = 'Заказ ' + o.order_number;
    $('#topbarTitle').text('Заказ ' + o.order_number);
    $('#orderNumber').text(o.order_number);
    $('#orderStatusPill').html(statusPill(o.status));
    $('#orderStatus').html(statusPill(o.status));
    $('#orderPartner').html(partnerCell(o.partner_id));
    text('#orderDelivery', o.delivery_method);
    text('#orderPaymentMethod', o.payment_method);
    $('#orderDate').text(fmtDate(o.created_at, true));
    $('#paymentStatusSelect').val(o.payment_status || 'awaiting');
    $('#savePaymentBtn').prop('disabled', true);

    var c = o.customer || {};
    text('#custName', c.name);
    text('#custPhone', c.phone);
    text('#custEmail', c.email);
    text('#custAddress', c.address);
    text('#custComment', o.customer_comment);
    $('#custLinkedBadge').toggleClass('d-none', !o.user_id);

    var $b = $('#itemsBody').empty();
    if (!(o.items || []).length) $b.html(emptyRow(7, 'В заказе нет товаров.'));
    (o.items || []).forEach(function (it) {
        $b.append('<tr>' +
            '<td>' + esc(it.name) + '</td>' +
            '<td class="small text-muted-2">' + esc(it.sku || '—') + '</td>' +
            '<td class="small">' + esc(it.color || '—') + '</td>' +
            '<td class="small">' + esc(it.size || '—') + '</td>' +
            '<td class="text-end">' + it.qty + '</td>' +
            '<td class="text-end text-nowrap">' + fmtMoney(it.price) + '</td>' +
            '<td class="text-end text-nowrap">' + fmtMoney(it.qty * it.price) + '</td>' +
            '</tr>');
    });
    $('#orderSubtotal').text(fmtMoney(o.subtotal || (o.items || []).reduce(function (s, it) { return s + it.qty * it.price; }, 0)));
    $('#discountRow').toggleClass('d-none', !o.discount_amount);
    $('#discountRowLabel').text('Скидка' + (o.discount_name ? ' «' + o.discount_name + '»' : '') + (o.coupon_code ? ' (купон ' + o.coupon_code + ')' : '') + ':');
    $('#orderDiscountAmount').text('−' + fmtMoney(o.discount_amount));
    $('#orderTotal').text(fmtMoney(o.total));

    var $h = $('#historyList').empty();
    var history = (o.history || []).slice().sort(function (a, b) { return new Date(b.date) - new Date(a.date); });
    if (!history.length) $h.html('<div class="text-muted-2">Записей нет</div>');
    history.forEach(function (h) {
        $h.append('<div class="order-history-item"><div>' + esc(h.text) + '</div>' +
            '<div class="order-history-time">' + esc(h.author || '') + ' · ' + fmtDate(h.date, true) + '</div></div>');
    });
}

function savePayment() {
    var status = $('#paymentStatusSelect').val();
    var msg = status === 'paid'
        ? 'Отметить заказ ' + order.order_number + ' оплаченным?'
        : (status === 'refunded' ? 'Отметить возврат оплаты по заказу ' + order.order_number + '?' : 'Снять отметку об оплате заказа ' + order.order_number + '?');
    showConfirm(msg + ' Покупатель и партнёр получат уведомление.', function () {
        cabinetAjax({ajax_action: 'set_payment_status', id: order.id, status: status}).done(function (saved) {
            render(saved);
            showResult(true, 'Статус оплаты: ' + PAYMENT_LABELS[status]);
        });
    }, {okText: 'Сохранить', danger: status === 'refunded'});
}

function addComment() {
    var t = $('#historyComment').val().trim();
    if (!t) return;
    cabinetAjax({ajax_action: 'add_order_comment', id: order.id, text: t}).done(function (saved) {
        $('#historyComment').val('');
        render(saved);
    });
}
