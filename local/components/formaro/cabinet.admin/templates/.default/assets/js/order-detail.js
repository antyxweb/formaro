/* Страница заказа в кабинете администратора: просмотр заказа (как в
   кабинете партнёра), статус оплаты — set_payment_status (уведомления
   покупателю и партнёру, запись в историю), комментарий площадки в
   историю — add_order_comment.
   Согласование и счёт (ТЗ «Финансы»): до выставления счёта — правка
   количества и цен по согласованию с покупателем (update_order_items),
   «Выставить счёт» (issue_invoice) — счёт действителен 3 рабочих дня. */
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
    $('#issueInvoiceBtn').on('click', issueInvoice);
    $('#saveItemsBtn').on('click', saveItems);
    $('#itemsBody').on('input', '.js-item-qty, .js-item-price', function () { $('#saveItemsBtn').prop('disabled', false); });
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

    var editable = canEditOrder(o);
    $('#itemsEditBar').toggleClass('d-none', !editable);
    $('#saveItemsBtn').prop('disabled', true);
    var $b = $('#itemsBody').empty();
    if (!(o.items || []).length) $b.html(emptyRow(7, 'В заказе нет товаров.'));
    (o.items || []).forEach(function (it, i) {
        $b.append('<tr>' +
            '<td>' + esc(it.name) + '</td>' +
            '<td class="small text-muted-2">' + esc(it.sku || '—') + '</td>' +
            '<td class="small">' + esc(it.color || '—') + '</td>' +
            '<td class="small">' + esc(it.size || '—') + '</td>' +
            (editable
                ? '<td class="text-end"><input type="number" min="0" step="1" class="form-control form-control-sm text-end js-item-qty" data-index="' + i + '" value="' + it.qty + '" style="width:80px;margin-left:auto;"></td>' +
                  '<td class="text-end"><input type="number" min="0" step="0.01" class="form-control form-control-sm text-end js-item-price" data-index="' + i + '" value="' + it.price + '" style="width:110px;margin-left:auto;"></td>'
                : '<td class="text-end">' + it.qty + '</td><td class="text-end text-nowrap">' + fmtMoney(it.price) + '</td>') +
            '<td class="text-end text-nowrap">' + fmtMoney(it.qty * it.price) + '</td>' +
            '</tr>');
    });
    renderInvoiceFlow(o);
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

/** Менеджер может менять состав и выставлять счёт: заказ не закрыт, не оплачен, счёт не действует. */
function canEditOrder(o) {
    return ['completed', 'cancelled'].indexOf(o.status) === -1 && o.payment_status !== 'paid' && o.invoice_status !== 'issued';
}

/** Карточка «Согласование и счёт»: подтверждение поставщика и состояние счёта. */
function renderInvoiceFlow(o) {
    var lines = [];
    lines.push(o.supplier_confirmed_at
        ? '<i class="bi bi-check-circle text-success"></i> Поставщик подтвердил наличие ' + fmtDate(o.supplier_confirmed_at) + '. Срок отгрузки: <b>' + esc(o.supplier_lead_time) + '</b>'
        : '<i class="bi bi-hourglass-split text-warning"></i> Поставщик ещё не подтвердил наличие и срок отгрузки.');
    if (o.payment_status === 'paid') {
        lines.push('<i class="bi bi-cash-coin text-success"></i> Заказ оплачен.');
    } else if (o.invoice_status === 'issued') {
        lines.push('<i class="bi bi-receipt"></i> Счёт выставлен ' + fmtDate(o.invoice_date) + ', действителен до <b>' + fmtDate(o.invoice_due, false) + '</b>.');
    } else if (o.invoice_status === 'expired') {
        lines.push('<i class="bi bi-x-circle text-danger"></i> Счёт аннулирован: не оплачен в срок. Можно выставить новый.');
    } else {
        lines.push('<i class="bi bi-receipt"></i> Счёт не выставлен — покупатель пока не может его скачать.');
    }
    $('#invoiceFlowState').html(lines.join('<br>'));
    var canIssue = canEditOrder(o);
    $('#issueInvoiceBtn').toggleClass('d-none', !canIssue).html('<i class="bi bi-receipt"></i> ' + (o.invoice_status === 'expired' ? 'Выставить счёт заново' : 'Выставить счёт'));
    $('#invoicePdfLink').toggleClass('d-none', !o.invoice_status && !canIssue)
        .attr('href', '/local/ajax/order_invoice.php?id=' + o.id + '&inline=1')
        .html('<i class="bi bi-file-earmark-pdf"></i> ' + (o.invoice_status ? 'Открыть счёт (PDF)' : 'Посмотреть, каким будет счёт'));
}

function issueInvoice() {
    var msg = 'Выставить счёт по заказу ' + order.order_number + ' на ' + fmtMoney(order.total) + '? Покупатель получит уведомление и письмо, счёт будет действителен 3 рабочих дня. Пока счёт действует, состав заказа не меняется.';
    if (!order.supplier_confirmed_at) msg = 'Поставщик ещё не подтвердил наличие. ' + msg;
    showConfirm(msg, function () {
        cabinetAjax({ajax_action: 'issue_invoice', id: order.id}).done(function (saved) {
            render(saved);
            showResult(true, 'Счёт выставлен, покупатель уведомлён');
        });
    }, {okText: 'Выставить счёт'});
}

function saveItems() {
    var items = [];
    $('#itemsBody tr').each(function () {
        var $q = $(this).find('.js-item-qty');
        if (!$q.length) return;
        items.push({index: Number($q.data('index')), qty: Number($q.val()), price: Number($(this).find('.js-item-price').val())});
    });
    showConfirm('Сохранить изменения состава заказа? Делайте это только по согласованию с покупателем. Поставщик получит уведомление.', function () {
        cabinetAjax({ajax_action: 'update_order_items', id: order.id, items: JSON.stringify(items)}).done(function (saved) {
            render(saved);
            showResult(true, 'Состав заказа сохранён');
        });
    }, {okText: 'Сохранить'});
}
