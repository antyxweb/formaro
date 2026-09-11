/* Порт cabinet-html/assets/js/order-detail.js.
   Изменения относительно прототипа:
   - id заказа — из ROUTE_ID (путь /orders/edit/#ID#/), не из ?id=;
   - товары для добавления в заказ — dsLoad('products') теперь и так отдаёт
     только СВОИ товары партнёра (сервер фильтрует по PARTNER_ID) — в
     прототипе таким фильтром не было (позволяло добавить чужой товар),
     здесь это правильнее и без специального кода;
   - discounts.json прототипа хранил скидки и купоны одним объектом
     {discounts, coupons} — теперь это два независимых entity ('discounts'
     и 'coupons'), каждый — обычный плоский массив, как и everywhere else;
   - order_number для нового заказа теперь считает сервер (по настоящему
     id) — клиентский dsNextId был лишь временной прикидкой для локального
     состояния до реального сохранения. */
var CABINET_URL = window.CABINET_BOOTSTRAP.cabinetUrl;

var orderId = 0;
var isNewOrder = false;
var allOrders = [];
var allProductsForPicker = [];
var currentItems = [];
var currentHistory = [];
var LOCKED_STATUSES = ['confirmed', 'shipped', 'completed'];
var activeDiscounts = [];
var allCouponsForOrder = [];
var appliedDiscount = null;
var appliedCoupon = null;

function isOrderLocked() { return LOCKED_STATUSES.indexOf($('#statusSelect').val()) !== -1; }

function applyLockState() {
    var locked = isOrderLocked();
    $('#orderLockedBanner').toggleClass('d-none', !locked);
    $('#custName, #custPhone, #custEmail, #custAddress, #custComment').prop('disabled', locked);
    $('#addProductSearch').prop('disabled', locked).attr('placeholder', locked ? 'Заказ подтверждён — товары нельзя менять' : 'Начните вводить название или артикул (от 3 символов)…');
    $('#applyDiscountSelect, #couponCodeInput, #applyCouponBtn, #removeAppliedBtn').prop('disabled', locked);
    renderItems();
}

$(function () {
    isNewOrder = (ROUTE_ID === 'new');
    orderId = isNewOrder ? 0 : (parseInt(ROUTE_ID, 10) || 0);
    bindAutoHeight('#custComment');

    dsLoad('products').done(function (prods) {
        allProductsForPicker = prods;
    });

    $.when(dsLoad('discounts'), dsLoad('coupons')).done(function (discountRows, couponRows) {
        var today = new Date().toISOString().slice(0, 10);
        activeDiscounts = ownOnly(discountRows || []).filter(function (d) {
            return d.status === 'active' && (!d.date_from || d.date_from <= today) && (!d.date_to || d.date_to >= today);
        });
        allCouponsForOrder = ownOnly(couponRows || []);
        var $sel = $('#applyDiscountSelect');
        activeDiscounts.forEach(function (d) {
            $sel.append('<option value="' + d.id + '">' + esc(d.name) + ' (' + (d.discount_type === 'percent' ? d.value + '%' : fmtMoney(d.value)) + ')</option>');
        });

        dsLoad('orders').done(function (rows) {
            allOrders = rows;
            if (isNewOrder) {
                $('#orderNumber').text('(новый)');
                $('#orderDate').text(fmtDate(new Date().toISOString()));
                currentItems = [];
                currentHistory = [];
                renderItems();
                renderHistory();
                applyLockState();
            } else {
                var o = allOrders.find(function (x) { return x.id === orderId; });
                if (!o) { showResult(false, 'Заказ не найден'); return; }
                renderOrder(o);
                applyLockState();
            }
        });
    });

    $('#applyDiscountSelect').on('change', function () {
        var id = parseInt($(this).val(), 10);
        if (!id) { appliedDiscount = null; recalcTotals(); return; }
        appliedDiscount = activeDiscounts.find(function (d) { return d.id === id; }) || null;
        appliedCoupon = null;
        $('#couponCodeInput').val('');
        recalcTotals();
    });
    $('#applyCouponBtn').on('click', function () {
        var code = $('#couponCodeInput').val().trim().toUpperCase();
        if (!code) return;
        var today = new Date().toISOString().slice(0, 10);
        var coupon = allCouponsForOrder.find(function (c) { return c.code === code; });
        if (!coupon) { showResult(false, 'Купон с таким кодом не найден'); return; }
        if (coupon.status !== 'active') { showResult(false, 'Купон неактивен'); return; }
        if ((coupon.date_from && coupon.date_from > today) || (coupon.date_to && coupon.date_to < today)) {
            showResult(false, 'Срок действия купона истёк или ещё не начался'); return;
        }
        appliedCoupon = coupon;
        appliedDiscount = null;
        $('#applyDiscountSelect').val('');
        recalcTotals();
        showResult(true, 'Купон «' + code + '» применён');
    });
    $('#removeAppliedBtn').on('click', function () {
        appliedDiscount = null;
        appliedCoupon = null;
        $('#applyDiscountSelect').val('');
        $('#couponCodeInput').val('');
        recalcTotals();
    });

    $('#statusSelect').on('change', applyLockState);

    $('#addProductSearch').on('input', debounce(function () {
        var q = $(this).val().trim();
        if (q.length < 3) { $('#addProductResults').removeClass('show').empty(); return; }
        searchProductsForOrder(q);
    }, 250));
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#addProductSearch, #addProductResults').length) $('#addProductResults').removeClass('show');
    });

    $('#addHistoryBtn').on('click', function () {
        var text = $('#historyComment').val().trim();
        if (!text) return;
        currentHistory.push({date: new Date().toISOString(), text: text, author: 'Менеджер'});
        $('#historyComment').val('');
        renderHistory();
    });

    $('#saveOrderBtn').on('click', doSaveOrder);
});

function findProductLive(productId) {
    return allProductsForPicker.find(function (p) { return p.id === productId; });
}

function disposeItemPopovers() {
    $('.product-info-trigger').each(function () {
        var inst = bootstrap.Popover.getInstance(this);
        if (inst) inst.dispose();
    });
}

function evalDiscountAmount(d) {
    var eligibleItems = currentItems.filter(function (it) {
        if (d.target_type === 'all') return true;
        if (d.target_type === 'products') return (d.target_ids || []).indexOf(it.product_id) !== -1;
        if (d.target_type === 'category') {
            var live = findProductLive(it.product_id);
            var catIds = live ? (live.category_ids || []) : [];
            return catIds.some(function (cid) { return (d.target_ids || []).indexOf(cid) !== -1; });
        }
        return false;
    });
    var eligibleQty = eligibleItems.reduce(function (s, it) { return s + it.qty; }, 0);
    var eligibleSubtotal = eligibleItems.reduce(function (s, it) { return s + it.qty * it.price; }, 0);
    var conditionsMet = (!d.min_qty || eligibleQty >= d.min_qty) && (!d.min_amount || eligibleSubtotal >= d.min_amount);
    if (!conditionsMet || eligibleSubtotal <= 0) return {amount: 0, conditionsMet: conditionsMet};
    var amount = d.discount_type === 'percent' ? eligibleSubtotal * d.value / 100 : Math.min(d.value * eligibleQty, eligibleSubtotal);
    return {amount: amount, conditionsMet: true};
}

function autoApplyBestDiscount() {
    if (appliedCoupon) return;
    var best = null, bestAmount = 0;
    activeDiscounts.forEach(function (d) {
        var r = evalDiscountAmount(d);
        if (r.conditionsMet && r.amount > bestAmount) { bestAmount = r.amount; best = d; }
    });
    appliedDiscount = best;
    $('#applyDiscountSelect').val(best ? best.id : '');
}

function calcTotals() {
    var subtotal = currentItems.reduce(function (s, it) { return s + it.qty * it.price; }, 0);
    var discountAmount = 0;
    var label = '';

    if (appliedDiscount) {
        var r = evalDiscountAmount(appliedDiscount);
        if (r.conditionsMet && r.amount > 0) {
            discountAmount = r.amount;
            label = appliedDiscount.name;
        } else if (!r.conditionsMet) {
            label = appliedDiscount.name + ' — условия не выполнены';
        }
    } else if (appliedCoupon) {
        var c = appliedCoupon;
        discountAmount = c.discount_type === 'percent' ? subtotal * c.value / 100 : Math.min(c.value, subtotal);
        label = 'Купон ' + c.code;
    }

    discountAmount = Math.round(discountAmount);
    return {subtotal: subtotal, discountAmount: discountAmount, total: Math.max(0, subtotal - discountAmount), label: label};
}

function recalcTotals() {
    var t = calcTotals();
    $('#orderSubtotal').text(fmtMoney(t.subtotal));
    $('#orderTotal').text(fmtMoney(t.total));
    $('#discountRow').toggleClass('d-none', t.discountAmount <= 0);
    if (t.discountAmount > 0) {
        $('#discountRowLabel').text('Скидка «' + t.label + '»:');
        $('#orderDiscountAmount').text('−' + fmtMoney(t.discountAmount));
    }

    var $info = $('#appliedInfo').empty();
    $('#removeAppliedBtn').toggleClass('d-none', !(appliedDiscount || appliedCoupon));
    if (appliedDiscount) {
        $info.html('<span class="pill pill-blue">' + esc(appliedDiscount.name) + (t.discountAmount > 0 ? ' · −' + fmtMoney(t.discountAmount) : ' · условия не выполнены') + '</span>');
    } else if (appliedCoupon) {
        $info.html('<span class="pill pill-green">Купон ' + esc(appliedCoupon.code) + ' · −' + fmtMoney(t.discountAmount) + '</span>');
    }
}

function renderItems() {
    var locked = isOrderLocked();
    disposeItemPopovers();
    var $b = $('#itemsBody').empty();
    if (currentItems.length === 0) {
        $b.append('<tr><td colspan="8" class="text-center text-muted-2 py-3">Товары не добавлены</td></tr>');
    }
    currentItems.forEach(function (it, idx) {
        var live = findProductLive(it.product_id);
        var color = it.color || (live && live.color) || '—';
        var size = it.size || (live && live.size) || '—';
        var qtyCell = locked
            ? esc(String(it.qty))
            : '<input type="number" min="1" class="form-control form-control-sm item-qty" data-idx="' + idx + '" value="' + it.qty + '" style="width:80px;">';
        var removeCell = locked ? '' : '<button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(' + idx + ')"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>';

        var popoverContent = '<div class="small">' +
            '<div><strong>Артикул:</strong> ' + esc(it.sku || '—') + '</div>' +
            '<div><strong>Цвет:</strong> ' + esc(color) + '</div>' +
            '<div><strong>Размер:</strong> ' + esc(size) + '</div>' +
            '<div><strong>Цена:</strong> ' + esc(fmtMoney(it.price)) + '</div>' +
            (live && live.short_desc ? '<div class="mt-1 text-muted-2">' + esc(live.short_desc) + '</div>' : '') +
            '</div>';

        $b.append(
            '<tr>' +
            '<td>' + (live ? '<a href="' + CABINET_URL + 'products/edit/' + live.id + '/">' + esc(it.name) + '</a>' : esc(it.name)) + ' ' +
              '<span class="product-info-trigger cursor-pointer" tabindex="0" data-bs-toggle="popover" data-bs-html="true" ' +
              'data-bs-title="' + esc(it.name) + '" data-bs-content="' + esc(popoverContent) + '"><i class="bi bi-info-circle text-muted-2" style="font-size:.8rem;"></i></span></td>' +
            '<td class="text-muted-2">' + esc(it.sku || '—') + '</td>' +
            '<td class="text-muted-2">' + esc(color) + '</td>' +
            '<td class="text-muted-2">' + esc(size) + '</td>' +
            '<td>' + qtyCell + '</td>' +
            '<td>' + fmtMoney(it.price) + '</td>' +
            '<td class="item-sum" data-idx="' + idx + '">' + fmtMoney(it.qty * it.price) + '</td>' +
            '<td>' + removeCell + '</td>' +
            '</tr>'
        );
    });
    autoApplyBestDiscount();
    recalcTotals();

    $('.item-qty').off('input').on('input', function () {
        var idx = $(this).data('idx');
        var qty = Math.max(1, parseInt($(this).val(), 10) || 1);
        currentItems[idx].qty = qty;
        $('.item-sum[data-idx="' + idx + '"]').text(fmtMoney(qty * currentItems[idx].price));
        autoApplyBestDiscount();
        recalcTotals();
    });

    $('.product-info-trigger').each(function () {
        new bootstrap.Popover(this, {
            trigger: 'hover focus', placement: 'top', container: 'body',
            delay: {show: 100, hide: 0}
        });
    });
    $('#itemsBody').off('mouseleave.popoverfix').on('mouseleave.popoverfix', function () {
        $('.product-info-trigger').each(function () {
            var inst = bootstrap.Popover.getInstance(this);
            if (inst) inst.hide();
        });
    });
}
function removeItem(idx) {
    var it = currentItems[idx];
    showConfirm('Убрать «' + (it ? it.name : 'товар') + '» из заказа?', function () {
        currentItems.splice(idx, 1);
        renderItems();
    }, {danger: true, okText: 'Убрать'});
}

function searchProductsForOrder(q) {
    var $results = $('#addProductResults');
    $results.html('<div class="gsearch-empty">Ищем…</div>').addClass('show');
    setTimeout(function () {
        var ql = q.toLowerCase();
        var matches = allProductsForPicker.filter(function (p) {
            return p.status === 'active' && (p.name.toLowerCase().indexOf(ql) !== -1 || (p.sku || '').toLowerCase().indexOf(ql) !== -1);
        }).slice(0, 10);
        if (matches.length === 0) { $results.html('<div class="gsearch-empty">Ничего не найдено</div>'); return; }
        var html = matches.map(function (p) {
            return '<div class="gsearch-item cursor-pointer" onclick="addProductToOrder(' + p.id + ')"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"></path><path d="M12 22V12"></path><polyline points="3.29 7 12 12 20.71 7"></polyline><path d="m7.5 4.27 9 5.15"></path></svg><span>' + esc(p.name) +
                ' <span class="text-muted-2">· ' + esc(p.sku || '') + ' · ' + fmtMoney(p.price) + '</span></span></div>';
        }).join('');
        $results.html(html);
    }, 200);
}
function addProductToOrder(pid) {
    var p = allProductsForPicker.find(function (x) { return x.id === pid; });
    if (!p) return;
    var existing = currentItems.find(function (it) { return it.product_id === pid; });
    if (existing) { existing.qty += 1; } else { currentItems.push({product_id: p.id, name: p.name, sku: p.sku, color: p.color, size: p.size, qty: 1, price: p.price}); }
    renderItems();
    $('#addProductSearch').val('');
    $('#addProductResults').removeClass('show').empty();
}

function renderHistory() {
    var $h = $('#historyList').empty();
    if (currentHistory.length === 0) { $h.append('<div class="text-muted-2 small">История пока пуста.</div>'); return; }
    currentHistory.slice().sort(function (a, b) { return new Date(b.date) - new Date(a.date); }).forEach(function (h) {
        $h.append(
            '<div class="order-history-item">' +
              '<div>' + esc(h.text) + '</div>' +
              '<div class="order-history-time">' + esc(h.author) + ' · ' + fmtDate(h.date) + '</div>' +
            '</div>'
        );
    });
}

function renderOrder(o) {
    $('#orderNumber').text(o.order_number);
    $('#orderStatusPill').html(statusPill(o.status));
    $('#orderDate').text(fmtDate(o.created_at));
    $('#statusSelect').val(o.status);
    $('#deliverySelect').val(o.delivery_method || 'Курьером по Москве');
    $('#paymentMethodSelect').val(o.payment_method || 'Банковской картой онлайн');
    $('#paymentStatusSelect').val(o.payment_status || 'awaiting');
    $('#custName').val(o.customer.name);
    $('#custPhone').val(o.customer.phone);
    $('#custEmail').val(o.customer.email);
    $('#custAddress').val(o.customer.address);
    $('#custComment').val(o.customer_comment || ''); autoHeightResize('#custComment');
    currentItems = (o.items || []).map(function (it) { return Object.assign({}, it); });
    currentHistory = (o.history || []).map(function (h) { return Object.assign({}, h); });
    if (o.discount_id) {
        appliedDiscount = activeDiscounts.find(function (d) { return d.id === o.discount_id; }) || null;
        if (appliedDiscount) $('#applyDiscountSelect').val(appliedDiscount.id);
    } else if (o.coupon_code) {
        appliedCoupon = allCouponsForOrder.find(function (c) { return c.code === o.coupon_code; }) || null;
        if (appliedCoupon) $('#couponCodeInput').val(appliedCoupon.code);
    }
    renderItems();
    renderHistory();
}

function doSaveOrder() {
    if (currentItems.length === 0) { showResult(false, 'Добавьте хотя бы один товар в заказ'); return; }
    var name = $('#custName').val().trim();
    if (!name) { showResult(false, 'Укажите имя или название компании покупателя'); return; }

    var totals = calcTotals();
    var newStatus = $('#statusSelect').val();
    var wasNew = isNewOrder;

    var payload = {
        status: newStatus,
        delivery_method: $('#deliverySelect').val(),
        payment_method: $('#paymentMethodSelect').val(),
        payment_status: $('#paymentStatusSelect').val(),
        customer: {
            name: name, phone: $('#custPhone').val(), email: $('#custEmail').val(), address: $('#custAddress').val()
        },
        customer_comment: $('#custComment').val(),
        items: currentItems,
        subtotal: totals.subtotal,
        discount_id: appliedDiscount ? appliedDiscount.id : null,
        discount_name: appliedDiscount ? appliedDiscount.name : (appliedCoupon ? 'Купон ' + appliedCoupon.code : ''),
        coupon_code: appliedCoupon ? appliedCoupon.code : null,
        discount_amount: totals.discountAmount,
        total: totals.total,
        history: currentHistory
    };

    if (isNewOrder) {
        payload.id = dsNextId(allOrders);
        payload.history = [{date: new Date().toISOString(), text: 'Заказ создан вручную в панели партнёра', author: 'Менеджер'}];
        allOrders.push(payload);
        orderId = payload.id;
    } else {
        var existing = allOrders.find(function (o) { return o.id === orderId; });
        if (existing && existing.status !== newStatus) {
            var statusRu = {new: 'Новый', processing: 'В обработке', confirmed: 'Подтверждён', shipped: 'Отправлен', completed: 'Выполнен', cancelled: 'Отменён'};
            payload.history = payload.history.concat([{date: new Date().toISOString(), text: 'Статус изменён на «' + statusRu[newStatus] + '»', author: 'Менеджер'}]);
        }
        payload.id = orderId;
        allOrders = allOrders.map(function (o) { return o.id === orderId ? Object.assign({}, o, payload) : o; });
    }

    saveOwnRows('orders', null, allOrders, function (savedRows) {
        if (wasNew) {
            var saved = savedRows[savedRows.length - 1];
            if (saved && saved.id) {
                orderId = saved.id;
                isNewOrder = false;
                showResult(true, 'Заказ создан');
                setTimeout(function () { window.location.href = CABINET_URL + 'orders/edit/' + orderId + '/'; }, 700);
                return;
            }
        }
        showResult(true, 'Заказ сохранён');
        var o = allOrders.find(function (x) { return x.id === orderId; });
        if (o) {
            $('#orderStatusPill').html(statusPill(o.status));
            currentHistory = (o.history || []).slice();
            renderHistory();
        }
    });
}
