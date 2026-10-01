/* Поиск в шапке (#header-search): от 2 символов — совпадения по категориям,
   товарам, партнёрам и новостям (/local/ajax/header_search.php,
   SiteSearchService), группами в выпадающем блоке под строкой. Enter или
   лупа — поиск товаров на главной (/?q=…#hero-search), туда же «Все
   товары». Блок показывается, пока строка в фокусе или курсор над ним
   (css/pages.css). */
(function () {
    var ENDPOINT = '/local/ajax/header_search.php';
    var MIN_LENGTH = 2;
    var DELAY = 250;
    var HINT = '<div class="text-secondary">Категории, товары, партнёры и новости</div>';
    var GROUPS = [
        ['categories', 'Категории'],
        ['products', 'Товары'],
        ['partners', 'Партнёры'],
        ['news', 'Новости']
    ];

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
        });
    }

    /* Совпадение — жирным (без учёта регистра). */
    function highlight(text, q) {
        text = String(text || '');
        var i = text.toLowerCase().indexOf(q.toLowerCase());
        if (i === -1) return esc(text);
        return esc(text.slice(0, i)) + '<b>' + esc(text.slice(i, i + q.length)) + '</b>' + esc(text.slice(i + q.length));
    }

    function price(n) {
        return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }

    function searchUrl(q) {
        return '/?q=' + encodeURIComponent(q) + '#hero-search';
    }

    function item(group, it, q) {
        var media = '';
        if (group === 'products' || group === 'partners') {
            media = it.image
                ? '<span class="header-search__thumb" style="background-image:url(\'' + esc(it.image) + '\')"></span>'
                : '<span class="header-search__thumb"></span>';
        }
        var meta = '';
        if (group === 'products') {
            meta = '<span class="header-search__price">'
                + (it.old_price ? '<s>' + price(it.old_price) + '</s> ' : '')
                + price(it.price) + ' руб.</span>';
        }
        var hint = it.hint ? '<small class="header-search__hint">' + esc(it.hint) + '</small>' : '';
        if (group === 'products' && it.sku) hint = '<small class="header-search__hint">Артикул: ' + highlight(it.sku, q) + '</small>';

        return '<li><a class="header-search__item" href="' + esc(it.url || '#') + '">' + media
            + '<span class="header-search__text"><span class="header-search__name">' + highlight(it.name, q) + '</span>' + hint + '</span>'
            + meta + '</a></li>';
    }

    function render(data, q) {
        var html = '';
        GROUPS.forEach(function (g) {
            var list = data[g[0]] || [];
            if (!list.length) return;
            html += '<div class="header-search__group"><h6 class="h6 text-secondary">' + g[1] + '</h6><ul class="mb-0">'
                + list.map(function (it) { return item(g[0], it, q); }).join('') + '</ul>';
            if (g[0] === 'products' && data.products_total > list.length) {
                html += '<a class="header-search__all" href="' + esc(searchUrl(q)) + '">Все товары (' + data.products_total + ')</a>';
            }
            html += '</div>';
        });

        return html || '<div class="text-secondary">Ничего не найдено</div>';
    }

    document.addEventListener('DOMContentLoaded', function () {
        var input = document.querySelector('.js-header-search');
        var results = document.querySelector('.js-header-search-results');
        if (!input || !results) return;

        var timer = null;
        var requestId = 0;

        function go() {
            var q = input.value.trim();
            if (q.length >= MIN_LENGTH) window.location.href = searchUrl(q);
        }

        input.addEventListener('input', function () {
            clearTimeout(timer);
            var q = input.value.trim();
            if (q.length < MIN_LENGTH) {
                requestId++;
                results.innerHTML = HINT;
                return;
            }
            timer = setTimeout(function () {
                var id = ++requestId;
                var xhr = new XMLHttpRequest();
                xhr.open('GET', ENDPOINT + '?q=' + encodeURIComponent(q), true);
                xhr.onload = function () {
                    if (id !== requestId) return;
                    var data = null;
                    try { data = JSON.parse(xhr.responseText); } catch (e) { /* пусто */ }
                    results.innerHTML = xhr.status === 200 && data
                        ? render(data, q)
                        : '<div class="text-danger">Не удалось выполнить поиск</div>';
                };
                xhr.send();
            }, DELAY);
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                go();
            } else if (e.key === 'Escape') {
                input.blur();
            }
        });

        var button = document.querySelector('.js-header-search-go');
        if (button) button.addEventListener('click', go);
    });
})();
