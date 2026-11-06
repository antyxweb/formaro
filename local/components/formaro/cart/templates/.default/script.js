/* formaro:cart — страница корзины (/personal/cart/).

   Состав корзины — window.FormaroCart (js/cart.js: аккаунт или
   localStorage), товары и цены — /local/ajax/cart_list.php. Товары — одной
   таблицей «Состав заказа», без деления на поставщиков (стили таблицы —
   .product-sizes, css/pages.css); в шапке «выбрать все», «снять выбор»,
   «удалить выбранные». Минимальная сумма — на весь заказ. Итог справа —
   по отмеченным товарам; снятые с выбора товары запоминаются в браузере
   (localStorage). Под товарами — способ доставки (транспортные компании),
   данные покупателя (поля блока «Данные покупателя» заказа в кабинете;
   сервер предзаполняет их из последнего заказа или профиля) и способ
   оплаты (пока только по счёту); выбранные способы запоминаются в браузере,
   личные данные — нет. Кнопка оформления без отмеченных товаров — серая «Выберите
   товары», иначе — «Оформить заказ»: один заказ на все отмеченные товары
   (/local/ajax/checkout.php, только для вошедших), оформленные товары
   уходят из корзины, сверху — сообщение с номером заказа. Количество в строке — не меньше 1, убрать товар —
   корзиной в конце строки; после изменения количества цены
   перезапрашиваются (скидки «от N штук» и «от суммы»).
   Промокоды — купоны партнёров из кабинета: скидка на отмеченные товары
   своего поставщика (процент или сумма, не больше их стоимости). Можно
   несколько — по одному на поставщика (новый код того же поставщика
   заменяет прежний). Применённые коды хранятся в браузере (localStorage).

   Файл подключается в <head>; FormaroCart/FormaroProductCard — в
   footer.php, поэтому старт — по jQuery ready. */
(function () {
    var endpoint = '/local/ajax/cart_list.php';
    var CHECKOUT_URL = '/local/ajax/checkout.php';
    var LOGIN_URL = '/personal/profile/?backurl=' + encodeURIComponent('/personal/cart/');
    var REFRESH_DELAY = 300;
    var QTY_DELAY = 500; // как в js/cart.js
    var STICKY_TOP = 60;
    var COUPON_KEY = 'formaro_cart_coupons';
    var UNCHECKED_KEY = 'formaro_cart_unchecked';
    var CHECKOUT_KEY = 'formaro_cart_checkout';

    var el = {};
    var items = [];           // товары корзины (partner_id нужен промокодам)
    var minOrder = 0;         // минимальная сумма заказа
    var qtyTimers = {};       // id → таймер ещё не отправленного количества
    var unchecked = readUnchecked(); // id → true: снятые с выбора (по умолчанию выбрано всё)
    var requestId = 0;
    var refreshTimer = null;
    var coupons = [];         // действующие промокоды (ответ сервера), по одному на поставщика
    var couponError = '';     // почему не подошёл введённый код
    var couponCodes = readCoupons();
    var attempt = null;       // {code, prev: [...]} — код, который сейчас проверяем

    function readCoupons() {
        try {
            var list = JSON.parse(window.localStorage.getItem(COUPON_KEY) || '[]');
            return Array.isArray(list) ? list.filter(function (c) { return typeof c === 'string' && c; }) : [];
        } catch (e) {
            return [];
        }
    }

    function writeCoupons(codes) {
        try {
            if (codes.length) window.localStorage.setItem(COUPON_KEY, JSON.stringify(codes)); else window.localStorage.removeItem(COUPON_KEY);
        } catch (e) { /* без хранилища — промокоды до перезагрузки */ }
    }

    function readUnchecked() {
        var map = {};
        try {
            var list = JSON.parse(window.localStorage.getItem(UNCHECKED_KEY) || '[]');
            if (Array.isArray(list)) list.forEach(function (id) { if (Number(id) > 0) map[Number(id)] = true; });
        } catch (e) { /* пусто */ }
        return map;
    }

    /** Сохраняем выбор; товары, которых уже нет в корзине, забываем —
     *  добавленный заново товар снова будет отмечен. */
    function writeUnchecked() {
        var present = {};
        items.forEach(function (item) { present[item.id] = true; });
        Object.keys(unchecked).forEach(function (id) { if (!present[id]) delete unchecked[id]; });
        var ids = Object.keys(unchecked).map(Number);
        try {
            if (ids.length) window.localStorage.setItem(UNCHECKED_KEY, JSON.stringify(ids)); else window.localStorage.removeItem(UNCHECKED_KEY);
        } catch (e) { /* без хранилища — выбор до перезагрузки */ }
    }

    /** Способ доставки/оплаты: {delivery, payment}. */
    function readCheckout() {
        try {
            var data = JSON.parse(window.localStorage.getItem(CHECKOUT_KEY) || '{}');
            return data && typeof data === 'object' ? data : {};
        } catch (e) {
            return {};
        }
    }

    function writeCheckout() {
        var form = el.checkoutForm;
        var data = {
            delivery: form.elements.delivery.value,
            payment: form.elements.payment.value
        };
        try {
            window.localStorage.setItem(CHECKOUT_KEY, JSON.stringify(data));
        } catch (e) { /* без хранилища — до перезагрузки */ }
    }

    function card() { return window.FormaroProductCard; }

    function plural(n, one, few, many) {
        var n10 = n % 10, n100 = n % 100;
        if (n10 === 1 && n100 !== 11) return one;
        if (n10 >= 2 && n10 <= 4 && (n100 < 12 || n100 > 14)) return few;
        return many;
    }

    function load(cartItems, cb) {
        var id = ++requestId;
        var qs = cartItems.map(function (item) { return item.id + ':' + item.qty; }).join(',');
        var xhr = new XMLHttpRequest();
        xhr.open('GET', endpoint + '?items=' + encodeURIComponent(qs) + (couponCodes.length ? '&coupons=' + encodeURIComponent(couponCodes.join(',')) : ''), true);
        xhr.onload = function () {
            if (id !== requestId) return;
            var data = null;
            try { data = JSON.parse(xhr.responseText); } catch (e) { /* пусто */ }
            if (xhr.status === 200 && data) cb(data);
        };
        xhr.send();
    }

    function stockText(item) {
        return item.stock > 0 ? String(item.stock) : (item.is_preorder ? 'Под заказ' : 'Нет в наличии');
    }

    function priceCellHtml(item) {
        var fmt = card().fmtPrice;
        return fmt(item.price) + ' ₽' + (item.old_price ? ' <s class="small">' + fmt(item.old_price) + ' ₽</s>' : '');
    }

    function rowHtml(item) {
        var esc = card().escHtml;
        var name = esc(item.name);
        return (
            '<tr data-cart-item="' + item.id + '">' +
                '<td class="cart-table__choose">' +
                    '<div class="custom-control custom-checkbox">' +
                        '<input type="checkbox" class="custom-control-input js-cart-choose" id="cart-item-' + item.id + '" value="' + item.id + '" aria-label="Выбрать: ' + name + '"' + (unchecked[item.id] ? '' : ' checked') + '>' +
                        '<label class="custom-control-label text-muted" for="cart-item-' + item.id + '"></label>' +
                    '</div>' +
                '</td>' +
                '<td><a href="' + esc(item.url) + '" class="cart-table__link">' +
                    '<span class="cart-table__img" style="background-image: url(\'' + esc(item.image || '') + '\')"></span>' +
                    '<span>' + name + '</span>' +
                '</a></td>' +
                '<td class="product-sizes__price js-cart-price">' + priceCellHtml(item) + '</td>' +
                '<td class="js-cart-stock">' + esc(stockText(item)) + '</td>' +
                '<td>' +
                    '<div class="product-sizes__qty">' +
                        '<button type="button" class="js-cart-minus" aria-label="Меньше: ' + name + '">−</button>' +
                        '<input type="text" inputmode="numeric" class="js-cart-qty" value="' + item.qty + '" aria-label="Количество: ' + name + '">' +
                        '<button type="button" class="js-cart-plus" aria-label="Больше: ' + name + '">+</button>' +
                        '<span>шт.</span>' +
                    '</div>' +
                '</td>' +
                '<td class="product-sizes__cart">' +
                    '<button type="button" class="js-cart-remove" title="Убрать из корзины" aria-label="Убрать из корзины: ' + name + '">' +
                        '<svg width="20" height="20"><use xlink:href="#icon-delete"></use></svg>' +
                    '</button>' +
                '</td>' +
            '</tr>'
        );
    }

    function tableHtml() {
        return (
            '<div class="mb-5">' +
                '<div class="catalog-options mb-3 mb-lg-4 d-flex align-items-center">' +
                    '<span class="cart-group-title mr-auto">Состав заказа</span>' +
                    '<div class="cart-group-actions d-flex ml-3">' +
                        '<button type="button" class="button-icon bg-light js-cart-check" title="Выбрать все товары"><svg width="16" height="16"><use xlink:href="#icon-checkbox-tick"></use></svg></button>' +
                        '<button type="button" class="button-icon js-cart-uncheck" title="Снять выбор"><svg width="16" height="16"><use xlink:href="#icon-close"></use></svg></button>' +
                        '<button type="button" class="button-icon js-cart-delete" title="Удалить выбранные"><svg width="16" height="16"><use xlink:href="#icon-delete"></use></svg></button>' +
                    '</div>' +
                '</div>' +
                '<div class="product-sizes cart-table">' +
                    '<table>' +
                        '<thead><tr>' +
                            '<th class="cart-table__choose"></th>' +
                            '<th>Товар</th>' +
                            '<th>Цена</th>' +
                            '<th>Доступно на складе</th>' +
                            '<th>Количество</th>' +
                            '<th class="product-sizes__cart"><svg width="24" height="24" aria-label="Корзина"><use xlink:href="#icon-shopping"></use></svg></th>' +
                        '</tr></thead>' +
                        '<tbody>' + items.map(rowHtml).join('') + '</tbody>' +
                    '</table>' +
                '</div>' +
            '</div>'
        );
    }

    function render() {
        el.groups.innerHTML = items.length ? tableHtml() : '';
        // После оформления вместо «корзина пуста» — сообщение о заказе.
        el.empty.classList.toggle('d-none', items.length > 0 || !el.success.classList.contains('d-none'));
        el.summaryWrap.classList.toggle('d-none', !items.length);
        el.checkoutForm.classList.toggle('d-none', !items.length);
        writeUnchecked();
        renderSummary();
        updateSticky();
    }

    function renderSummary() {
        var fmt = card().fmtPrice;
        var selected = selectedItems();
        var sum = 0, total = 0;
        selected.forEach(function (item) {
            sum += item.old_sum;
            total += item.sum;
        });
        var couponDiscount = 0;
        coupons.forEach(function (coupon) {
            var base = selected.filter(function (item) { return item.partner_id === coupon.partner_id; })
                // Промокод поставщика — от его цен (без наценки площадки), как на сервере.
                .reduce(function (s, item) { return s + (item.coupon_base !== undefined ? item.coupon_base : item.sum); }, 0);
            couponDiscount += coupon.discount_type === 'percent'
                ? Math.round(base * coupon.value / 100)
                : Math.min(coupon.value, base);
        });
        total -= couponDiscount;
        el.couponSum.textContent = '-' + fmt(couponDiscount);
        el.couponWrap.classList.toggle('d-none', couponDiscount <= 0);

        var n = selected.length;
        el.count.textContent = n + ' ' + plural(n, 'позиция', 'позиции', 'позиций');
        el.sum.textContent = fmt(sum);
        el.total.textContent = fmt(total);
        el.discount.textContent = '-' + fmt(sum - total - couponDiscount);
        el.discountWrap.classList.toggle('d-none', sum - total - couponDiscount <= 0);
        var short = belowMinOrder();
        var blocked = n === 0 || short;
        el.checkout.disabled = blocked;
        el.checkout.classList.toggle('c-gray', blocked);
        el.checkout.classList.toggle('text-secondary', blocked); // как «Удалить» в карточке
        el.checkout.classList.toggle('c-success', !blocked);
        el.checkout.textContent = !n ? 'Выберите товары' : (short ? 'Не набрана минимальная сумма' : 'Оформить заказ');
        if (blocked) el.checkoutNote.classList.add('d-none');
        updateActions();
    }

    function selectedItems() {
        return items.filter(function (item) { return !unchecked[item.id]; });
    }

    /** Сумма отмеченных товаров (цены покупателя до промокода). */
    function selectedSum() {
        return selectedItems().reduce(function (s, item) { return s + item.sum; }, 0);
    }

    /** Что-то отмечено, но на сумму меньше минимальной — оформить нельзя
     *  (сервер проверяет то же самое). */
    function belowMinOrder() {
        return selectedItems().length > 0 && minOrder > 0 && selectedSum() < minOrder;
    }

    /** «Удалить выбранные» — только если что-то отмечено; подсказка о минимальной сумме. */
    function updateActions() {
        var button = el.groups.querySelector('.js-cart-delete');
        if (!button) return;
        button.classList.toggle('d-none', !selectedItems().length);
        var note = el.minOrderNote;
        var short = belowMinOrder();
        note.classList.toggle('d-none', !short);
        if (short) {
            var fmt = card().fmtPrice;
            note.textContent = 'Минимальная сумма заказа — ' + fmt(minOrder) + ' руб. Добавьте товаров ещё на ' +
                fmt(minOrder - selectedSum()) + ' руб., чтобы оформить заказ.';
        }
    }

    /** Ответ сервера: товары поставщиков — одним списком. */
    function setItems(data) {
        minOrder = Number(data.min_order) || 0;
        items = (data.groups || []).reduce(function (list, g) { return list.concat(g.items); }, []);
    }

    function renderCoupon() {
        var esc = card().escHtml;
        var html = '';
        if (couponError) {
            html += '<div class="small text-danger mb-1">' + esc(couponError) + '</div>';
        }
        coupons.forEach(function (coupon) {
            html += '<div class="mb-1">' +
                '<span class="badge badge-primary">' + esc(coupon.code) +
                    ' <a href="#" class="text-white ml-1 js-cart-coupon-remove" data-code="' + esc(coupon.code) + '" aria-label="Убрать промокод">&times;</a>' +
                '</span>' +
                '<div class="small text-muted">' + esc(coupon.message || ('Скидка на товары продавца «' + coupon.partner_name + '»')) + '</div>' +
            '</div>';
        });
        el.couponState.innerHTML = html;
    }

    /** Ответ по промокодам. Неподходящий введённый код не запоминаем и не
     *  трогаем им уже применённые — только показываем ошибку; ранее
     *  сохранённый код, который перестал действовать, тихо убираем. Новый
     *  код поставщика заменяет его прежний. */
    function setCoupons(data) {
        var list = data.coupons || [];
        couponError = '';
        if (attempt) {
            var tried = list.filter(function (c) { return c.code === attempt.code; })[0];
            if (!tried || !tried.valid) {
                couponError = tried ? tried.message : 'Промокод не найден';
                list = list.filter(function (c) { return c.code !== attempt.code; });
            } else {
                list = list.filter(function (c) { return c.code === tried.code || c.partner_id !== tried.partner_id; });
            }
            attempt = null;
        }
        coupons = list.filter(function (c) { return c.valid; });
        couponCodes = coupons.map(function (c) { return c.code; });
        writeCoupons(couponCodes);
        renderCoupon();
    }

    /** Данные с сервера; если состав не менялся (только количество/цены) —
     *  обновляем строки на месте, не перерисовывая таблицу (не сбиваем ввод
     *  количества). */
    function apply(data) {
        setCoupons(data);
        var before = items.map(function (x) { return x.id; }).join();
        setItems(data);
        if (items.map(function (x) { return x.id; }).join() !== before) {
            render();
            return;
        }
        items.forEach(function (item) {
            var row = el.groups.querySelector('[data-cart-item="' + item.id + '"]');
            if (!row) return;
            row.querySelector('.js-cart-price').innerHTML = priceCellHtml(item);
            row.querySelector('.js-cart-stock').textContent = stockText(item);
            var input = row.querySelector('.js-cart-qty');
            if (!qtyTimers[item.id] && document.activeElement !== input) input.value = item.qty;
        });
        renderSummary();
    }

    function refresh() {
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(function () {
            load(window.FormaroCart.getItems(), apply);
        }, REFRESH_DELAY);
    }

    /** Удаление — сразу убираем строки, цены перезапросим. */
    function removeLocally(ids) {
        ids.forEach(function (id) {
            clearTimeout(qtyTimers[id]);
            delete qtyTimers[id];
        });
        items = items.filter(function (item) { return ids.indexOf(item.id) === -1; });
        render();
    }

    /** Количество из строки: не меньше 1 (убрать — корзиной в конце строки),
     *  не больше остатка; не число — остаётся прежнее. */
    function changeQty(row, value) {
        var id = Number(row.getAttribute('data-cart-item'));
        var item = items.filter(function (x) { return x.id === id; })[0];
        if (!item) return;
        var qty = Math.max(1, parseInt(value, 10) || item.qty);
        if (item.max > 0 && qty > item.max) qty = item.max;
        row.querySelector('.js-cart-qty').value = qty;
        clearTimeout(qtyTimers[id]);
        qtyTimers[id] = setTimeout(function () {
            delete qtyTimers[id];
            window.FormaroCart.set(id, qty);
        }, QTY_DELAY);
    }

    function setChecked(list, checked) {
        list.forEach(function (item) {
            if (checked) delete unchecked[item.id]; else unchecked[item.id] = true;
            var input = document.getElementById('cart-item-' + item.id);
            if (input) input.checked = checked;
        });
        writeUnchecked();
        renderSummary();
    }

    /** Строка доставки в итоге — название выбранной транспортной компании. */
    function renderDelivery() {
        var checked = el.checkoutForm.querySelector('input[name="delivery"]:checked');
        var name = checked ? checked.closest('.cart-choice').textContent.trim() : '';
        el.deliveryTitle.textContent = 'Доставка' + (name ? ' (' + name + ')' : '') + ':';
    }

    /** Обязательные поля покупателя; первое ошибочное — в фокус. */
    function validateBuyer() {
        var checks = [
            [el.buyerName, function (v) { return v !== ''; }],
            [el.buyerPhone, function (v) { return /^\+?[\d\s\-()]{7,20}$/.test(v); }],
            [el.buyerEmail, function (v) { return v === '' || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); }],
            [el.address, function (v) { return v !== ''; }]
        ];
        var first = null;
        checks.forEach(function (check) {
            var ok = check[1](check[0].value.trim());
            check[0].classList.toggle('is-invalid', !ok);
            if (!ok && !first) first = check[0];
        });
        if (first) {
            first.scrollIntoView({block: 'center', behavior: 'smooth'});
            first.focus({preventScroll: true});
        }
        return !first;
    }

    function initCheckoutForm() {
        var form = el.checkoutForm;
        var saved = readCheckout();
        ['delivery', 'payment'].forEach(function (name) {
            var input = saved[name] && form.querySelector('input[name="' + name + '"][value="' + String(saved[name]).replace(/[^a-z]/g, '') + '"]');
            if (input) input.checked = true;
        });
        renderDelivery();

        form.addEventListener('change', function (e) {
            if (e.target.name === 'delivery') renderDelivery();
            writeCheckout();
        });
        form.addEventListener('input', function (e) {
            e.target.classList.remove('is-invalid');
        });
        form.addEventListener('submit', function (e) { e.preventDefault(); });
    }

    function showNote(html, isError) {
        el.checkoutNote.innerHTML = html;
        el.checkoutNote.classList.toggle('text-danger', !!isError);
        el.checkoutNote.classList.toggle('text-muted', !isError);
        el.checkoutNote.classList.remove('d-none');
    }

    /** Оформление: отмеченные товары → заказ. */
    function submitOrder() {
        if (!window.FormaroCart.isAuthorized()) {
            showNote('<a href="' + LOGIN_URL + '">Войдите</a>, чтобы оформить заказ — товары в корзине сохранятся.');
            return;
        }
        var ids = selectedItems().map(function (item) { return item.id; });
        if (!ids.length) return;

        var form = el.checkoutForm;
        var params = ['sessid=' + encodeURIComponent(window.FormaroCart.sessid())];
        ids.forEach(function (id) { params.push('ids[]=' + id); });
        params.push('coupons=' + encodeURIComponent(couponCodes.join(',')));
        params.push('delivery=' + encodeURIComponent(form.elements.delivery.value));
        params.push('payment=' + encodeURIComponent(form.elements.payment.value));
        [['name', el.buyerName], ['phone', el.buyerPhone], ['email', el.buyerEmail], ['address', el.address], ['comment', el.buyerComment]].forEach(function (f) {
            params.push('buyer[' + f[0] + ']=' + encodeURIComponent(f[1].value.trim()));
        });

        el.checkout.disabled = true;
        el.checkout.textContent = 'Оформляем…';
        el.checkoutNote.classList.add('d-none');

        var xhr = new XMLHttpRequest();
        xhr.open('POST', CHECKOUT_URL, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            var data = null;
            try { data = JSON.parse(xhr.responseText); } catch (e) { /* пусто */ }
            renderSummary();
            if (xhr.status === 200 && data && data.orders) {
                showSuccess(data.orders, ids);
            } else {
                showNote(card().escHtml((data && data.error) || 'Не удалось оформить заказ, попробуйте ещё раз'), true);
            }
        };
        xhr.onerror = function () {
            renderSummary();
            showNote('Нет связи с сервером, попробуйте ещё раз', true);
        };
        xhr.send(params.join('&'));
    }

    /** Заказ создан: сообщение с номером, оформленные товары убираем
     *  из корзины (на сервере их уже нет). */
    function showSuccess(orders, ids) {
        var esc = card().escHtml, fmt = card().fmtPrice;
        document.getElementById('cart-success-list').innerHTML = orders.map(function (o) {
            return '<li>Заказ <b>' + esc(o.order_number) + '</b> — ' + fmt(o.total) + ' руб</li>';
        }).join('');
        var box = el.success;
        box.classList.remove('d-none');
        el.buyerComment.value = '';
        // Счётчик заказов в шапке (и в меню личного кабинета) — сервер
        // выводит его только при загрузке страницы.
        Array.prototype.forEach.call(document.querySelectorAll('[data-orders-count]'), function (counter) {
            counter.textContent = (parseInt(counter.textContent, 10) || 0) + orders.length;
        });
        window.FormaroCart.remove(ids);
        // Карточки уже убраны (remove → onChange → render синхронно); ждём
        // кадр, чтобы вёрстка пересчиталась, и ставим плашку под
        // фиксированной шапкой сайта (scrollIntoView прятал её верх под ней).
        window.requestAnimationFrame(function () {
            var header = document.getElementById('header');
            var offset = (header ? header.offsetHeight : 0) + 20;
            window.scrollTo({top: Math.max(0, box.getBoundingClientRect().top + window.pageYOffset - offset), behavior: 'smooth'});
            box.focus({preventScroll: true});
        });
    }

    /** Итог залипает CSS sticky (style.css), только если помещается в окно;
     *  sticky-kit, который scripts.js включает на #product-option, снимаем. */
    function updateSticky() {
        var box = document.getElementById('product-option');
        if (!box) return;
        if (window.jQuery && jQuery.fn.stick_in_parent) jQuery(box).trigger('sticky_kit:detach');
        box.classList.toggle('is-sticky', box.offsetHeight + STICKY_TOP <= window.innerHeight);
    }

    function init() {
        var root = document.getElementById('cart-page');
        if (!root || !window.FormaroCart || !window.FormaroProductCard) return;

        el = {
            groups: document.getElementById('cart-groups'),
            empty: document.getElementById('cart-empty'),
            summaryWrap: document.getElementById('cart-summary-wrap'),
            count: document.getElementById('cart-summary-count'),
            sum: document.getElementById('cart-summary-sum'),
            discount: document.getElementById('cart-summary-discount'),
            discountWrap: document.getElementById('cart-summary-discount-wrap'),
            total: document.getElementById('cart-summary-total'),
            checkout: document.getElementById('cart-checkout'),
            couponSum: document.getElementById('cart-summary-coupon'),
            couponWrap: document.getElementById('cart-summary-coupon-wrap'),
            couponState: document.getElementById('cart-coupon-state'),
            couponInput: document.getElementById('cart-coupon-input'),
            checkoutNote: document.getElementById('cart-checkout-note'),
            minOrderNote: document.getElementById('cart-min-order'),
            checkoutForm: document.getElementById('cart-checkout-form'),
            success: document.getElementById('cart-success'),
            address: document.getElementById('cart-address'),
            buyerName: document.getElementById('cart-buyer-name'),
            buyerPhone: document.getElementById('cart-buyer-phone'),
            buyerEmail: document.getElementById('cart-buyer-email'),
            buyerComment: document.getElementById('cart-buyer-comment'),
            deliveryTitle: document.getElementById('cart-summary-delivery-title'),
            delivery: document.getElementById('cart-summary-delivery')
        };
        initCheckoutForm();

        root.addEventListener('change', function (e) {
            if (e.target.classList.contains('js-cart-qty')) {
                changeQty(e.target.closest('[data-cart-item]'), e.target.value);
                return;
            }
            if (!e.target.classList.contains('js-cart-choose')) return;
            var id = Number(e.target.value);
            if (e.target.checked) delete unchecked[id]; else unchecked[id] = true;
            writeUnchecked();
            renderSummary();
        });

        root.addEventListener('click', function (e) {
            var button = e.target.closest && e.target.closest('.js-cart-check, .js-cart-uncheck, .js-cart-delete, .js-cart-minus, .js-cart-plus, .js-cart-remove');
            if (!button) return;
            var row = button.closest('[data-cart-item]');
            if (button.classList.contains('js-cart-check')) {
                setChecked(items, true);
            } else if (button.classList.contains('js-cart-uncheck')) {
                setChecked(items, false);
            } else if (button.classList.contains('js-cart-remove')) {
                window.FormaroCart.remove([Number(row.getAttribute('data-cart-item'))]);
            } else if (row) {
                var current = parseInt(row.querySelector('.js-cart-qty').value, 10) || 1;
                changeQty(row, current + (button.classList.contains('js-cart-plus') ? 1 : -1));
            } else {
                var ids = selectedItems().map(function (item) { return item.id; });
                if (!ids.length) return;
                window.FormaroConfirm({
                    title: 'Удалить выбранные товары?',
                    message: 'Из корзины будут удалены отмеченные товары (позиций: ' + ids.length + ').',
                    okText: 'Удалить',
                    danger: true
                }).then(function (ok) { if (ok) window.FormaroCart.remove(ids); });
            }
        });

        document.getElementById('cart-coupon-form').addEventListener('submit', function (e) {
            e.preventDefault();
            var code = el.couponInput.value.trim().toUpperCase();
            if (!code) return;
            el.couponInput.value = '';
            if (couponCodes.indexOf(code) !== -1) return;
            attempt = {code: code};
            couponCodes = couponCodes.concat(code);
            load(window.FormaroCart.getItems(), apply);
        });

        el.couponState.addEventListener('click', function (e) {
            var link = e.target.closest('.js-cart-coupon-remove');
            if (!link) return;
            e.preventDefault();
            var code = link.getAttribute('data-code');
            coupons = coupons.filter(function (c) { return c.code !== code; });
            couponCodes = coupons.map(function (c) { return c.code; });
            writeCoupons(couponCodes);
            couponError = '';
            renderCoupon();
            renderSummary();
        });

        el.checkout.addEventListener('click', function () {
            // Гостю сначала — предложение войти, поля проверим потом.
            if (window.FormaroCart.isAuthorized() && !validateBuyer()) return;
            submitOrder();
        });

        // Любое изменение корзины: удалённые карточки убираем сразу, цены и
        // итог перезапрашиваем.
        window.FormaroCart.onChange(function (cartItems) {
            var present = cartItems.map(function (item) { return item.id; });
            var removed = items.map(function (item) { return item.id; }).filter(function (id) { return present.indexOf(id) === -1; });
            if (removed.length) removeLocally(removed);
            refresh();
        });

        window.FormaroCart.onReady(function (cartItems) {
            if (!cartItems.length) {
                items = [];
                render();
                return;
            }
            load(cartItems, function (data) {
                setCoupons(data);
                setItems(data);
                render();
            });
        });

        window.addEventListener('resize', updateSticky);
        window.addEventListener('load', updateSticky);
    }

    if (window.jQuery) {
        jQuery(init);
    } else {
        document.addEventListener('DOMContentLoaded', function () {
            if (window.jQuery) jQuery(init); else init();
        });
    }
})();
