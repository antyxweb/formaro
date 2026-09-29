/* Шаблон formaro:favorites.list — /personal/favorites/.

   Список избранного берём у js/favorites.js (window.FormaroFavorites:
   аккаунт или localStorage), товары — /local/ajax/favorites_list.php.
   Карточка и поведение фильтра — js/catalog-common.js.

   Файл подключается в <head>; FormaroFavorites/FormaroProductCard и jQuery
   появляются в footer.php, поэтому старт — по jQuery ready (он наступает
   после выполнения всех синхронных скриптов body). */
(function () {
    var endpoint = '/local/ajax/favorites_list.php';

    var limit = 24;
    var state = {ids: [], sort: 'added', query: {}, offset: 0, total: 0, loading: false, requestId: 0, facetsApplied: false};
    var el = {};

    function request(params, cb) {
        var qs = Object.keys(params).filter(function (k) {
            return params[k] !== '' && params[k] != null;
        }).map(function (k) {
            return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
        }).join('&');

        var xhr = new XMLHttpRequest();
        xhr.open('GET', endpoint + '?' + qs, true);
        xhr.onload = function () {
            var data = null;
            try { data = JSON.parse(xhr.responseText); } catch (e) { /* пусто */ }
            cb(xhr.status === 200 ? data : null);
        };
        xhr.onerror = function () { cb(null); };
        xhr.send();
    }

    function show(node, visible) {
        if (node) node.classList.toggle('d-none', !visible);
    }

    function hasFilter() {
        return !!(state.query.sections || state.query.price_min !== '' && state.query.price_min != null);
    }

    function setTotal(total) {
        state.total = total;
        el.total.textContent = total;
    }

    /** Боковой фильтр: на xl виден (d-xl-block), ниже — только в попапе
     *  по кнопке «Фильтры». Для пустого избранного убираем совсем. */
    function showSidebar(visible) {
        el.filterWrap.classList.toggle('d-xl-block', visible);
        el.content.classList.toggle('col-xl-9', visible);
    }

    /** Нет избранного вообще — только заглушка со ссылкой в каталог. */
    function renderEmpty() {
        showSidebar(false);
        show(el.options, false);
        show(el.pager, false);
        show(el.notFound, false);
        show(el.empty, true);
    }

    /** Сужаем фильтр до того, что есть в избранном. */
    function applyFacets(facets) {
        var sections = (facets.sections || []).map(Number);
        var anyCategory = false;
        Array.prototype.forEach.call(el.form.querySelectorAll('[data-section-id]'), function (node) {
            var visible = sections.indexOf(Number(node.getAttribute('data-section-id'))) !== -1;
            show(node, visible);
            if (visible) anyCategory = true;
            if (!visible) {
                var input = node.querySelector('input');
                if (input) input.checked = false;
            }
        });
        show(el.categories, anyCategory);

        var min = Math.floor(facets.price_min || 0);
        var max = Math.max(min + 1, Math.ceil(facets.price_max || 0));
        el.form.setAttribute('data-price-min', min);
        el.form.setAttribute('data-price-max', max);
        el.priceMin.value = min;
        el.priceMax.value = max;
        if (window.jQuery && jQuery.fn.slider) {
            jQuery('#filterPrice')
                .slider('setAttribute', 'min', min)
                .slider('setAttribute', 'max', max)
                .slider('refresh')
                .slider('setValue', [min, max]);
        }
        state.facetsApplied = true;
        if (window.jQuery) jQuery('#filter').trigger('sticky_kit:recalc');
    }

    /** Скелетоны карточек перед «Показать еще» — пока идёт запрос:
     *  8 при новой выборке, 4 при подгрузке. */
    function showSkeletons(visible, n) {
        Array.prototype.forEach.call(el.grid.querySelectorAll('.product-skeleton'), function (node) {
            node.parentNode.removeChild(node);
        });
        if (visible) {
            el.pager.insertAdjacentHTML('beforebegin', window.FormaroProductCard.skeletonsHtml(n || 8, 'product-item'));
        }
    }

    function clearCards() {
        Array.prototype.forEach.call(el.grid.querySelectorAll('.product-item'), function (node) {
            node.parentNode.removeChild(node);
        });
    }

    function setLoading(loading) {
        state.loading = loading;
        el.loadMore.disabled = loading;
        var icons = el.loadMore.querySelectorAll('svg');
        if (icons[0]) icons[0].classList.toggle('d-none', loading);
        if (icons[1]) icons[1].classList.toggle('d-none', !loading);
    }

    function load(reset) {
        if (!state.ids.length) {
            clearCards();
            showSkeletons(false);
            renderEmpty();
            return;
        }
        if (reset) {
            state.requestId++;
            state.offset = 0;
            clearCards();
            show(el.pager, false);
            show(el.notFound, false);
            showSkeletons(true, 8);
        } else if (state.loading) {
            return;
        } else {
            showSkeletons(true, 4);
        }

        setLoading(true);
        var requestId = state.requestId;
        var params = {ids: state.ids.join(','), sort: state.sort, offset: state.offset, limit: limit};
        Object.keys(state.query).forEach(function (k) { params[k] = state.query[k]; });

        request(params, function (data) {
            if (requestId !== state.requestId) return;
            setLoading(false);
            showSkeletons(false);
            if (!data) return;

            if (!state.facetsApplied) {
                // Все избранные товары неактивны/удалены — как пустое избранное.
                if (!data.total && !hasFilter()) {
                    renderEmpty();
                    return;
                }
                applyFacets(data.facets || {});
            }

            showSidebar(true);
            show(el.empty, false);
            show(el.options, true);

            var items = data.items || [];
            el.pager.insertAdjacentHTML('beforebegin', items.map(function (item) {
                return '<div class="product-item">' + window.FormaroProductCard.html(item) + '</div>';
            }).join(''));
            state.offset += items.length;
            setTotal(data.total || 0);
            show(el.pager, !!data.hasMore);
            show(el.notFound, !data.total);
        });
    }

    function applyFilter() {
        var filter = window.FormaroCatalogFilter;
        var price = filter.readPrice(el.form);
        state.query.sections = filter.selectedSections(el.form).join(',');
        state.query.price_min = price.active ? price.min : '';
        state.query.price_max = price.active ? price.max : '';
        if (window.jQuery && jQuery.fancybox) jQuery.fancybox.close();
        load(true);
    }

    function resetFilter() {
        el.form.reset();
    }

    /** Сердечко на этой странице снимает товар из избранного — карточка
     *  уходит сразу, счётчик уменьшается. */
    function onFavoritesChange(ids, id, added) {
        state.ids = ids;
        if (added) return;

        var removed = 0;
        Array.prototype.forEach.call(el.grid.querySelectorAll('.favorite[data-product-id="' + id + '"]'), function (fav) {
            var item = fav.closest('.product-item');
            if (item) {
                item.parentNode.removeChild(item);
                removed++;
            }
        });
        if (!removed) return;

        state.offset = Math.max(0, state.offset - removed);
        setTotal(Math.max(0, state.total - removed));
        if (!state.ids.length) {
            renderEmpty();
        } else if (!state.total) {
            show(el.pager, false);
            show(el.notFound, true);
        }
    }

    function init() {
        var root = document.getElementById('favorites-list');
        if (!root || !window.FormaroFavorites || !window.FormaroProductCard) return;
        limit = parseInt(root.getAttribute('data-page-size'), 10) || limit;

        el = {
            form: document.getElementById('filter'),
            filterWrap: document.getElementById('favorites-filter-wrap'),
            categories: document.getElementById('favorites-filter-categories'),
            content: document.getElementById('favorites-content'),
            options: document.getElementById('favorites-options'),
            total: document.getElementById('favorites-total'),
            grid: document.getElementById('catalog-grid'),
            empty: document.getElementById('favorites-empty'),
            notFound: document.getElementById('favorites-not-found'),
            pager: document.getElementById('favorites-pager'),
            loadMore: document.getElementById('favorites-load-more')
        };
        el.priceMin = el.form.querySelector('.filterPriceMin');
        el.priceMax = el.form.querySelector('.filterPriceMax');

        el.loadMore.addEventListener('click', function () { load(false); });
        document.getElementById('favorites-reset-filter').addEventListener('click', resetFilter);

        el.form.addEventListener('submit', function (e) {
            e.preventDefault();
            applyFilter();
        });
        el.form.addEventListener('reset', function () {
            // Нативный reset вернёт поля к значениям из разметки уже после
            // события — восстанавливаем границы цены и применяем следующим тиком.
            setTimeout(function () {
                var bounds = window.FormaroCatalogFilter.bounds(el.form);
                el.priceMin.value = bounds.min;
                el.priceMax.value = bounds.max;
                window.FormaroCatalogFilter.setSliderValue(bounds.min, bounds.max);
                applyFilter();
            }, 0);
        });

        // Сортировка: подпись/активный пункт переключает scripts.js (.f-dropdown a).
        el.options.addEventListener('click', function (e) {
            var link = e.target.closest('[data-sort]');
            if (!link) return;
            e.preventDefault();
            if (state.sort === link.getAttribute('data-sort')) return;
            state.sort = link.getAttribute('data-sort');
            load(true);
        });

        window.FormaroFavorites.onChange(onFavoritesChange);
        window.FormaroFavorites.onReady(function (ids) {
            state.ids = ids;
            load(true);
        });
    }

    if (window.jQuery) {
        jQuery(init);
    } else {
        document.addEventListener('DOMContentLoaded', function () {
            if (window.jQuery) jQuery(init); else init();
        });
    }
})();
