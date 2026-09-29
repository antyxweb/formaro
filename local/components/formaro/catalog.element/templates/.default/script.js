/* formaro:catalog.element — карточка товара.

   - Товар запоминается в просмотренных (FormaroViewed, js/catalog-common.js).
   - Переход на вариант (цвет/размер, .js-product-variant) — без перезагрузки:
     загружаем страницу варианта, заменяем #product-detail, заголовок,
     хлебные крошки, <title> и адрес (history.pushState); «Назад» в
     браузере возвращает предыдущий вариант так же. Галерею (Flickity) и
     залипание блоков (sticky-kit) scripts.js включает при загрузке —
     для новой разметки включаем заново с теми же настройками.

   Файл подключается в <head>, jQuery — в конце body: старт на DOMContentLoaded. */
document.addEventListener('DOMContentLoaded', function () {
    var $ = window.jQuery;
    var root = document.getElementById('product-detail');
    if (!root) return;

    if (window.FormaroViewed) window.FormaroViewed.add(root.getAttribute('data-product-id'));
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

    function stick(on) {
        if (!$.fn.stick_in_parent) return;
        if (on) {
            $('#product-option').stick_in_parent({offset_top: 60});
            $('#product-sidebar').stick_in_parent({});
        } else {
            $('#product-option, #product-sidebar').trigger('sticky_kit:detach');
        }
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

            stick(false);
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
            stick(true);
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

    history.replaceState({productUrl: window.location.href}, '', window.location.href);
    window.addEventListener('popstate', function (e) {
        if (e.state && e.state.productUrl) load(e.state.productUrl, false);
    });
});
