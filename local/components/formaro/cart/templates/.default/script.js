/* formaro:cart — страница корзины (/personal/cart/).

   Состав корзины — window.FormaroCart (js/cart.js: аккаунт или
   localStorage), товары и цены — /local/ajax/cart_list.php. Товары
   сгруппированы по поставщикам; у группы шапка: слева поставщик ссылкой,
   справа «выбрать все», «снять выбор», «удалить выбранные». Итог справа —
   по отмеченным товарам. Количество и «Удалить» в карточке работают через
   cart.js; после изменения количества цены перезапрашиваются (скидки
   «от N штук» и «от суммы»).

   Файл подключается в <head>; FormaroCart/FormaroProductCard — в
   footer.php, поэтому старт — по jQuery ready. */
(function () {
    var endpoint = '/local/ajax/cart_list.php';
    var REFRESH_DELAY = 300;
    var STICKY_TOP = 60;

    var el = {};
    var groups = [];          // [{partner, items}]
    var unchecked = {};       // id → true: снятые с выбора (по умолчанию выбрано всё)
    var requestId = 0;
    var refreshTimer = null;

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
        xhr.open('GET', endpoint + '?items=' + encodeURIComponent(qs), true);
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
        el.empty.classList.toggle('d-none', items.length > 0);
        el.summaryWrap.classList.toggle('d-none', !items.length);
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
        var n = selected.length;
        el.count.textContent = n + ' ' + plural(n, 'позиция', 'позиции', 'позиций');
        el.sum.textContent = fmt(sum);
        el.total.textContent = fmt(total);
        el.discount.textContent = '-' + fmt(sum - total);
        el.discountWrap.classList.toggle('d-none', sum - total <= 0);
        el.checkout.disabled = n === 0;
    }

    /** Данные с сервера → группы; если состав групп не менялся (только
     *  количество/цены) — обновляем цены на месте, не перерисовывая
     *  карточки (не сбиваем ввод количества). */
    function apply(data) {
        var next = data.groups || [];
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
        renderSummary();
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
            checkoutNote: document.getElementById('cart-checkout-note')
        };

        root.addEventListener('change', function (e) {
            if (!e.target.classList.contains('js-cart-choose')) return;
            var id = Number(e.target.value);
            if (e.target.checked) delete unchecked[id]; else unchecked[id] = true;
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

        el.checkout.addEventListener('click', function () {
            el.checkoutNote.classList.remove('d-none');
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
                groups = data.groups || [];
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
