/* formaro:catalog.element — карточка товара.

   - Товар запоминается в просмотренных (FormaroViewed, js/catalog-common.js).
   - Переход на вариант (цвет/размер, .js-product-variant) — без перезагрузки:
     загружаем страницу варианта, заменяем #product-detail, заголовок,
     хлебные крошки, <title> и адрес (history.pushState); «Назад» в
     браузере возвращает предыдущий вариант так же. Галерею (Flickity)
     scripts.js включает при загрузке — для новой разметки включаем заново.
   - Таблица размеров (#product-sizes): количество сразу кладётся в корзину
     (FormaroCart), 0 — убирает. Обработчики делегированы: таблица заменяется
     вместе с #product-detail при смене цвета.
   - Залипание блока цены — CSS sticky (style.css, класс is-sticky),
     только если блок целиком помещается в окно; сайдбар «О продавце» не
     залипает. sticky-kit, который scripts.js включает на обоих, снимаем —
     он дёргал блоки.

   Файл подключается в <head>, jQuery — в конце body: старт на DOMContentLoaded. */
document.addEventListener('DOMContentLoaded', function () {
    var $ = window.jQuery;
    var root = document.getElementById('product-detail');
    if (!root) return;

    // Предпросмотр из кабинета (/preview/) — не просмотр товара покупателем.
    if (window.FormaroViewed && !root.hasAttribute('data-preview')) window.FormaroViewed.add(root.getAttribute('data-product-id'));

    var QTY_DELAY = 500; // как в js/cart.js
    var sizeTimers = {};

    function renderSizeRows() {
        if (!window.FormaroCart) return;
        var qtyById = {};
        window.FormaroCart.getItems().forEach(function (item) { qtyById[item.id] = item.qty; });
        Array.prototype.forEach.call(root.querySelectorAll('.js-size-row'), function (row) {
            var id = row.getAttribute('data-product-id');
            var input = row.querySelector('.js-size-qty');
            if (!sizeTimers[id] && document.activeElement !== input) input.value = qtyById[id] || 0;
            row.querySelector('.js-size-remove').classList.toggle('d-none', !qtyById[id]);
        });
    }

    function changeSizeQty(row, qty) {
        var id = row.getAttribute('data-product-id');
        var max = Number(row.getAttribute('data-max'));
        qty = Math.max(0, parseInt(qty, 10) || 0);
        if (max > 0 && qty > max) qty = max;
        row.querySelector('.js-size-qty').value = qty;
        clearTimeout(sizeTimers[id]);
        sizeTimers[id] = setTimeout(function () {
            delete sizeTimers[id];
            window.FormaroCart.set(id, qty);
            renderSizeRows();
        }, QTY_DELAY);
    }

    document.addEventListener('click', function (e) {
        var button = e.target.closest && e.target.closest('.js-size-minus, .js-size-plus, .js-size-remove');
        if (!button || !window.FormaroCart) return;
        var row = button.closest('.js-size-row');
        var input = row.querySelector('.js-size-qty');
        if (button.classList.contains('js-size-remove')) {
            changeSizeQty(row, 0);
        } else {
            changeSizeQty(row, (parseInt(input.value, 10) || 0) + (button.classList.contains('js-size-plus') ? 1 : -1));
        }
    });

    document.addEventListener('change', function (e) {
        if (e.target.classList && e.target.classList.contains('js-size-qty') && window.FormaroCart) {
            changeSizeQty(e.target.closest('.js-size-row'), e.target.value);
        }
    });

    if (window.FormaroCart) {
        window.FormaroCart.onReady(renderSizeRows);
        window.FormaroCart.onChange(renderSizeRows);
    }

    if (!$ || !window.DOMParser || !window.history || !history.pushState) return;

    var loading = false;

    function initGallery() {
        if (!$.fn.flickity) return;
        $('.product-gallery-carousel').flickity({
            cellAlign: 'left',
            contain: true,
            pageDots: false,
            freeScroll: true,
            fullscreen: true,
            lazyLoad: 1
        });
    }

    var STICKY_TOP = 60; // как top в style.css

    /** Залипает только блок цены; сайдбар «О продавце» — нет, но
     *  sticky-kit со scripts.js снимаем с обоих. */
    function updateSticky() {
        if ($.fn.stick_in_parent) $('#product-option, #product-sidebar').trigger('sticky_kit:detach');
        var option = document.getElementById('product-option');
        if (option) option.classList.toggle('is-sticky', option.offsetHeight + STICKY_TOP <= window.innerHeight);
    }

    /** Заголовок как у scripts.js: первое слово — акцентом. */
    function setTitle(text) {
        var h1 = document.getElementById('main-title');
        if (!h1) return;
        text = text.trim();
        var space = text.indexOf(' ');
        var first = space === -1 ? text : text.slice(0, space);
        var rest = space === -1 ? '' : text.slice(space);
        var esc = window.FormaroProductCard ? window.FormaroProductCard.escHtml : function (s) { return s; };
        h1.innerHTML = '<span class="text-primary">' + esc(first) + '</span>' + esc(rest);
    }

    function load(url, push) {
        if (loading) return;
        loading = true;
        root.style.opacity = '0.6';

        $.ajax({url: url, dataType: 'html'}).done(function (html) {
            var doc = new DOMParser().parseFromString(html, 'text/html');
            var next = doc.getElementById('product-detail');
            if (!next) {
                window.location.href = url;
                return;
            }

            if ($.fn.flickity) $('.product-gallery-carousel').flickity('destroy');
            root.parentNode.replaceChild(document.importNode(next, true), root);
            root = document.getElementById('product-detail');

            var title = doc.getElementById('main-title');
            if (title) setTitle(title.textContent);
            var crumbs = doc.querySelector('.breadcrumbs');
            var currentCrumbs = document.querySelector('.breadcrumbs');
            if (crumbs && currentCrumbs) currentCrumbs.innerHTML = crumbs.innerHTML;
            document.title = doc.title;

            if (push) history.pushState({productUrl: url}, '', url);
            initGallery();
            updateSticky();
            renderSizeRows();
            if (window.FormaroViewed) window.FormaroViewed.add(root.getAttribute('data-product-id'));
        }).fail(function () {
            window.location.href = url;
        }).always(function () {
            loading = false;
            if (root) root.style.opacity = '';
        });
    }

    document.addEventListener('click', function (e) {
        var link = e.target.closest && e.target.closest('#product-detail .js-product-variant');
        if (!link || e.ctrlKey || e.metaKey || e.shiftKey) return;
        e.preventDefault();
        if (link.querySelector('.border-primary')) return; // уже открыт
        load(link.href, true);
    });

    // scripts.js включает sticky-kit при загрузке — отключаем после него.
    if (document.readyState === 'complete') {
        updateSticky();
    } else {
        window.addEventListener('load', updateSticky);
    }
    var resizeTimer = null;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(updateSticky, 150);
    });

    history.replaceState({productUrl: window.location.href}, '', window.location.href);
    window.addEventListener('popstate', function (e) {
        if (e.state && e.state.productUrl) load(e.state.productUrl, false);
    });
});
