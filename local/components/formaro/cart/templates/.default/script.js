/* formaro:cart — страница корзины (/personal/cart/).

   Состав корзины — window.FormaroCart (js/cart.js: аккаунт или
   localStorage), товары и цены — /local/ajax/cart_list.php. Товары
   сгруппированы по поставщикам; у группы шапка: слева поставщик ссылкой,
   справа «выбрать все», «снять выбор», «удалить выбранные». Итог справа —
   по отмеченным товарам; снятые с выбора товары запоминаются в браузере
   (localStorage). Под товарами — способ доставки (транспортные компании),
   данные покупателя (поля блока «Данные покупателя» заказа в кабинете;
   сервер предзаполняет их из последнего заказа или профиля) и способ
   оплаты (пока только по счёту); выбранные способы запоминаются в браузере,
   личные данные — нет. Кнопка оформления без отмеченных товаров — серая «Выберите
   товары», иначе — «Оформить заказ»: по заказу на каждого поставщика
   отмеченных товаров (/local/ajax/checkout.php, только для вошедших),
   оформленные товары уходят из корзины, сверху — сообщение с номерами
   заказов. Количество и «Удалить» в карточке работают через
   cart.js; после изменения количества цены перезапрашиваются (скидки
   «от N штук» и «от суммы»).
   Промокоды — купоны партнёров из кабинета: скидка на отмеченные товары
   своего поставщика (процент или сумма, не больше их стоимости). Можно
   несколько — по одному на поставщика (новый код того же поставщика
   заменяет прежний). Применённые коды хранятся в браузере (localStorage).

   Файл подключается в <head>; FormaroCart/FormaroProductCard — в
   footer.php, поэтому старт — по jQuery ready. */
(function () {
    var endpoint = '/local/ajax/cart_list.php';
    var CHECKOUT_URL = '/local/ajax/checkout.php';
    var LOGIN_URL = '/login/?backurl=' + encodeURIComponent('/personal/cart/');
    var REFRESH_DELAY = 300;
    var STICKY_TOP = 60;
    var COUPON_KEY = 'formaro_cart_coupons';
    var UNCHECKED_KEY = 'formaro_cart_unchecked';
    var CHECKOUT_KEY = 'formaro_cart_checkout';

    var el = {};
    var groups = [];          // [{partner, items}]
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
        allItems().forEach(function (item) { present[item.id] = true; });
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

    function load(items, cb) {
        var id = ++requestId;
        var qs = items.map(function (item) { return item.id + ':' + item.qty; }).join(',');
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

    function allItems() {
        return groups.reduce(function (list, g) { return list.concat(g.items); }, []);
    }

    function itemHtml(item) {
        var checked = !unchecked[item.id];
        // Количество и «Удалить» в карточке выставит cart.js (класс in-cart).
        return (
            '<div class="product-item col-sm-6 col-md-4 col-xl-3" data-cart-item="' + item.id + '">' +
                '<div class="cart-item-choose">' +
                    '<div class="custom-control custom-checkbox mr-sm-2">' +
                        '<input type="checkbox" class="custom-control-input js-cart-choose" id="cart-item-' + item.id + '" value="' + item.id + '"' + (checked ? ' checked' : '') + '>' +
                        '<label class="custom-control-label text-muted" for="cart-item-' + item.id + '"></label>' +
                    '</div>' +
                '</div>' +
                card().html(item) +
            '</div>'
        );
    }

    function groupHtml(group, index) {
        var esc = card().escHtml;
        var p = group.partner;
        var title = p.url
            ? '<a href="' + esc(p.url) + '" class="cart-group-title mr-auto">' + esc(p.name) + '</a>'
            : '<span class="cart-group-title mr-auto">' + esc(p.name) + '</span>';
        return (
            '<div class="cart-group mb-5" data-cart-group="' + index + '">' +
                '<div class="catalog-options mb-3 mb-lg-4 d-flex align-items-center">' +
                    title +
                    '<div class="cart-group-actions d-flex ml-3">' +
                        '<button type="button" class="button-icon bg-light js-cart-check" title="Выбрать все товары поставщика"><svg width="16" height="16"><use xlink:href="#icon-checkbox-tick"></use></svg></button>' +
                        '<button type="button" class="button-icon js-cart-uncheck" title="Снять выбор"><svg width="16" height="16"><use xlink:href="#icon-close"></use></svg></button>' +
                        '<button type="button" class="button-icon js-cart-delete" title="Удалить выбранные"><svg width="16" height="16"><use xlink:href="#icon-delete"></use></svg></button>' +
                    '</div>' +
                '</div>' +
                '<div class="cart-grid row product-list d-flex flex-wrap">' +
                    group.items.map(itemHtml).join('') +
                '</div>' +
            '</div>'
        );
    }

    function render() {
        var items = allItems();
        el.groups.innerHTML = groups.map(groupHtml).join('');
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
        var selected = allItems().filter(function (item) { return !unchecked[item.id]; });
        var sum = 0, total = 0;
        selected.forEach(function (item) {
            sum += item.old_sum;
            total += item.sum;
        });
        var couponDiscount = 0;
        coupons.forEach(function (coupon) {
            var base = selected.filter(function (item) { return item.partner_id === coupon.partner_id; })
                .reduce(function (s, item) { return s + item.sum; }, 0);
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
        el.checkout.disabled = n === 0;
        el.checkout.classList.toggle('c-gray', n === 0);
        el.checkout.classList.toggle('text-secondary', n === 0); // как «Удалить» в карточке
        el.checkout.classList.toggle('c-success', n > 0);
        el.checkout.textContent = n ? 'Оформить заказ' : 'Выберите товары';
        if (!n) el.checkoutNote.classList.add('d-none');
        updateGroupActions();
    }

    /** «Удалить выбранные» у группы — только если в ней что-то отмечено. */
    function updateGroupActions() {
        groups.forEach(function (group, index) {
            var button = el.groups.querySelector('[data-cart-group="' + index + '"] .js-cart-delete');
            if (!button) return;
            var any = group.items.some(function (item) { return !unchecked[item.id]; });
            button.classList.toggle('d-none', !any);
        });
    }

    /** Партнёр товара — нужен для промокода (купон одного поставщика). */
    function withPartners(list) {
        list.forEach(function (g) {
            g.items.forEach(function (item) { item.partner_id = g.partner.id; });
        });
        return list;
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

    /** Данные с сервера → группы; если состав групп не менялся (только
     *  количество/цены) — обновляем цены на месте, не перерисовывая
     *  карточки (не сбиваем ввод количества). */
    function apply(data) {
        setCoupons(data);
        var next = withPartners(data.groups || []);
        var sameStructure = next.length === groups.length && next.every(function (g, i) {
            return g.partner.id === groups[i].partner.id &&
                g.items.map(function (x) { return x.id; }).join() === groups[i].items.map(function (x) { return x.id; }).join();
        });
        groups = next;
        if (!sameStructure) {
            render();
            return;
        }
        allItems().forEach(function (item) {
            var node = el.groups.querySelector('[data-cart-item="' + item.id + '"] .product-card__info .price');
            if (node) node.innerHTML = card().priceHtml(item);
        });
        renderSummary();
    }

    function refresh() {
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(function () {
            load(window.FormaroCart.getItems(), apply);
        }, REFRESH_DELAY);
    }

    /** Удаление — сразу убираем карточки (и пустые группы), цены
     *  перезапросим. */
    function removeLocally(ids) {
        groups = groups.map(function (g) {
            return {partner: g.partner, items: g.items.filter(function (item) { return ids.indexOf(item.id) === -1; })};
        }).filter(function (g) { return g.items.length; });
        render();
    }

    function groupItems(button) {
        var group = groups[Number(button.closest('[data-cart-group]').getAttribute('data-cart-group'))];
        return group ? group.items : [];
    }

    function setChecked(items, checked) {
        items.forEach(function (item) {
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

    /** Оформление: отмеченные товары → заказы по поставщикам. */
    function submitOrder() {
        if (!window.FormaroCart.isAuthorized()) {
            showNote('<a href="' + LOGIN_URL + '">Войдите</a>, чтобы оформить заказ — товары в корзине сохранятся.');
            return;
        }
        var ids = allItems().filter(function (item) { return !unchecked[item.id]; }).map(function (item) { return item.id; });
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

    /** Заказы созданы: сообщение с номерами, оформленные товары убираем
     *  из корзины (на сервере их уже нет). */
    function showSuccess(orders, ids) {
        var esc = card().escHtml, fmt = card().fmtPrice;
        document.getElementById('cart-success-list').innerHTML = orders.map(function (o) {
            return '<li>Заказ <b>' + esc(o.order_number) + '</b> — ' + esc(o.partner_name) + ', ' + fmt(o.total) + ' руб</li>';
        }).join('');
        var box = el.success;
        box.querySelector('.cart-success-title').textContent = orders.length > 1 ? 'Заказы оформлены' : 'Заказ оформлен';
        box.classList.remove('d-none');
        el.buyerComment.value = '';
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
            if (!e.target.classList.contains('js-cart-choose')) return;
            var id = Number(e.target.value);
            if (e.target.checked) delete unchecked[id]; else unchecked[id] = true;
            writeUnchecked();
            renderSummary();
        });

        root.addEventListener('click', function (e) {
            var button = e.target.closest && e.target.closest('.js-cart-check, .js-cart-uncheck, .js-cart-delete');
            if (!button) return;
            var items = groupItems(button);
            if (button.classList.contains('js-cart-check')) {
                setChecked(items, true);
            } else if (button.classList.contains('js-cart-uncheck')) {
                setChecked(items, false);
            } else {
                var ids = items.filter(function (item) { return !unchecked[item.id]; }).map(function (item) { return item.id; });
                if (!ids.length) return;
                if (!window.confirm('Удалить выбранные товары (' + ids.length + ') из корзины?')) return;
                window.FormaroCart.remove(ids);
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
        window.FormaroCart.onChange(function (items) {
            var present = items.map(function (item) { return item.id; });
            var removed = allItems().map(function (item) { return item.id; }).filter(function (id) { return present.indexOf(id) === -1; });
            if (removed.length) removeLocally(removed);
            refresh();
        });

        window.FormaroCart.onReady(function (items) {
            if (!items.length) {
                groups = [];
                render();
                return;
            }
            load(items, function (data) {
                setCoupons(data);
                groups = withPartners(data.groups || []);
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
