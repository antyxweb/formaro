/* formaro:catalog.section — категория каталога.

   - «Показать еще»: следующая страница с теми же фильтром и сортировкой
     из /local/ajax/catalog_grid.php (параметры — data-query секции),
     пока идёт запрос — скелетоны карточек;
   - фильтр — GET-форма: перед отправкой убираем пустые поля и цену, если
     она не сужена, чтобы в адресе было только выбранное;
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

    if (form) {
        form.addEventListener('submit', function () {
            var filter = window.FormaroCatalogFilter;
            var price = filter ? filter.readPrice(form) : {active: true};
            var min = form.querySelector('.filterPriceMin');
            var max = form.querySelector('.filterPriceMax');
            if (!price.active) {
                min.disabled = true;
                max.disabled = true;
            } else {
                min.value = price.min;
                max.value = price.max;
            }
            if (window.jQuery && jQuery.fancybox) jQuery.fancybox.close();
        });
    }

    document.addEventListener('click', function (e) {
        var link = e.target.closest && e.target.closest('.catalog-sort a[href]');
        if (!link) return;
        e.preventDefault();
        window.location.href = link.href;
    });
});
