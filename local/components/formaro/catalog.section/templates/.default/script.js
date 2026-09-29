/* formaro:catalog.section — категория каталога.

   - «Показать еще»: следующая страница с теми же фильтром и сортировкой
     из /local/ajax/catalog_grid.php (параметры — data-query секции),
     пока идёт запрос — скелетоны карточек;
   - «Применить» — переход на ЧПУ фильтра, собранный из data-slug
     отмеченных чекбоксов и цены (формат — CatalogFilterUrl в модуле);
     цена пишется, только если сужена;
   - сортировка — ссылки в выпадающем списке; список лежит внутри <button>,
     где браузеры не всегда переходят по ссылке, поэтому переходим сами.

   Файл подключается в <head>; FormaroProductCard/FormaroCatalogFilter —
   в footer.php, поэтому старт — на DOMContentLoaded. */
document.addEventListener('DOMContentLoaded', function () {
    var root = document.getElementById('catalog-section');
    if (!root || !window.FormaroProductCard) return;

    var grid = document.getElementById('catalog-grid');
    var form = document.getElementById('filter');
    var button = document.getElementById('catalog-load-more');
    var page = parseInt(root.getAttribute('data-page'), 10) || 1;
    var pageSize = parseInt(root.getAttribute('data-page-size'), 10) || 24;
    var total = parseInt(root.getAttribute('data-total'), 10) || 0;
    var query = {};
    try { query = JSON.parse(root.getAttribute('data-query') || '{}'); } catch (e) { /* пусто */ }
    var loading = false;

    function setLoading(value) {
        loading = value;
        button.disabled = value;
        var icons = button.querySelectorAll('svg');
        icons[0].classList.toggle('d-none', value);
        icons[1].classList.toggle('d-none', !value);
        var pager = grid.querySelector('.pager');
        Array.prototype.forEach.call(grid.querySelectorAll('.product-skeleton'), function (node) {
            node.parentNode.removeChild(node);
        });
        if (value) pager.insertAdjacentHTML('beforebegin', window.FormaroProductCard.skeletonsHtml(4, 'product-item'));
    }

    function loadMore() {
        if (loading) return;
        setLoading(true);

        var params = Object.assign({}, query, {offset: page * pageSize, limit: pageSize});
        var qs = Object.keys(params).filter(function (k) {
            return params[k] !== '' && params[k] != null;
        }).map(function (k) {
            return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
        }).join('&');

        var xhr = new XMLHttpRequest();
        xhr.open('GET', '/local/ajax/catalog_grid.php?' + qs, true);
        xhr.onload = function () {
            setLoading(false);
            var data = null;
            try { data = JSON.parse(xhr.responseText); } catch (e) { /* пусто */ }
            if (xhr.status !== 200 || !data) return;

            var items = data.items || [];
            grid.querySelector('.pager').insertAdjacentHTML('beforebegin', items.map(function (item) {
                return '<div class="product-item">' + window.FormaroProductCard.html(item) + '</div>';
            }).join(''));
            page++;
            var more = !!data.hasMore && items.length > 0 && page * pageSize < total;
            document.getElementById('catalog-load-more-wrap').classList.toggle('d-none', !more);
        };
        xhr.onerror = function () { setLoading(false); };
        xhr.send();
    }

    if (button) button.addEventListener('click', loadMore);

    /** ЧПУ фильтра — тот же формат, что CatalogFilterUrl::build() на сервере:
     *  <категория>/filter/section-…/color-…/size-…/price-from-…-to-…/?sort=… */
    function filterUrl() {
        var slugs = function (name) {
            return Array.prototype.map.call(form.querySelectorAll('input[name="' + name + '[]"]:checked'), function (el) {
                return el.getAttribute('data-slug');
            }).filter(function (slug, i, all) { return slug && all.indexOf(slug) === i; });
        };
        // Отмеченная по умолчанию текущая категория в адрес не пишется.
        var sections = slugs('sections');
        var defaults = Array.prototype.map.call(form.querySelectorAll('input[name="sections[]"][data-default]'), function (el) {
            return el.getAttribute('data-slug');
        });
        if (sections.length === defaults.length && sections.every(function (s) { return defaults.indexOf(s) !== -1; })) {
            sections = [];
        }

        var segments = [];
        [['section', sections], ['color', slugs('colors')], ['size', slugs('sizes')]].forEach(function (pair) {
            if (pair[1].length) segments.push(pair[0] + '-' + pair[1].join('-or-'));
        });

        var price = window.FormaroCatalogFilter ? window.FormaroCatalogFilter.readPrice(form) : {active: false};
        if (price.active) {
            var bounds = window.FormaroCatalogFilter.bounds(form);
            var parts = [];
            if (price.min > bounds.min) parts.push('from-' + price.min);
            if (price.max < bounds.max) parts.push('to-' + price.max);
            segments.push('price-' + parts.join('-'));
        }

        var url = form.getAttribute('data-base-url').replace(/\/?$/, '/');
        if (segments.length) url += 'filter/' + segments.join('/') + '/';
        var sort = form.querySelector('input[name="sort"]');
        if (sort && sort.value) url += '?sort=' + encodeURIComponent(sort.value);

        return url;
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (window.jQuery && jQuery.fancybox) jQuery.fancybox.close();
            window.location.href = filterUrl();
        });
    }

    document.addEventListener('click', function (e) {
        var link = e.target.closest && e.target.closest('.catalog-sort a[href]');
        if (!link) return;
        e.preventDefault();
        window.location.href = link.href;
    });
});
