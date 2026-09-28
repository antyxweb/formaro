/* Шаблон formaro:catalog.search — поиск по каталогу на главной.

   Один поток данных (/local/ajax/catalog_grid.php: offset/limit + поиск и
   фильтр) и два представления: десктопная сетка #catalog-grid и мобильная
   карусель #catalog-search. Метки/цену со скидкой считает сервер
   (ProductPricingService). Разметка карточки (.product-card) — та же, что
   в статичной вёрстке, поэтому "В корзину"/избранное из scripts.js
   работают как раньше (делегирование на body).

   Этот файл Битрикс подключает в <head> (script.js шаблона компонента), а
   jQuery, Flickity, bootstrap-slider, fancybox и scripts.js грузятся в
   конце body (footer.php). Поэтому: DOM — по DOMContentLoaded, всё, что
   требует jQuery, — по window "load". */
(function () {
    var endpoint = '/local/ajax/catalog_grid.php';
    var HISTORY_KEY = 'formaro_search_history';
    var HISTORY_SIZE = 5;
    var SUGGEST_LIMIT = 6;

    var limit = 24;
    var state = {query: {}, offset: 0, items: [], hasMore: false, loaded: false, loading: false, requestId: 0};
    var views = [];

    /* ---------------- Разметка ---------------- */

    function escHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
        });
    }

    function fmtPrice(n) {
        return Number(n || 0).toLocaleString('ru-RU');
    }

    function badgesHtml(badges) {
        if (!badges || !badges.length) return '';
        return '<div class="badges">' + badges.map(function (b) {
            return '<small class="' + escHtml(b.class) + '">' + escHtml(b.text) + '</small><br/>';
        }).join('') + '</div>';
    }

    function priceHtml(item) {
        if (item.old_price) {
            return '<s>' + fmtPrice(item.old_price) + '</s> ' +
                '<b class="text-danger" data-price="' + item.price + '">' + fmtPrice(item.price) + '</b> <small>руб/шт.</small>';
        }
        return '<b data-price="' + item.price + '">' + fmtPrice(item.price) + '</b> <small>руб/шт.</small>';
    }

    function stockHtml(item) {
        if (item.stock > 0) return '<small>В наличии: ' + item.stock + ' шт.</small>';
        if (item.is_preorder) return '<small>Под заказ</small>';
        return '<small>Нет в наличии</small>';
    }

    function productCardHtml(item) {
        var url = item.url || '#';
        return (
            '<div class="product-card">' +
                '<div class="product-card__img">' +
                    '<a href="' + escHtml(url) + '" class="embed-responsive embed-responsive-1by1" style="background-image: url(\'' + escHtml(item.image || '') + '\')"></a>' +
                    badgesHtml(item.badges) +
                    '<div class="favorite">' +
                        '<button class="button-icon"><svg width="20" height="20"><use xlink:href="#icon-favorite-stroke"></use></svg></button>' +
                        '<button class="button-icon d-none"><svg width="20" height="20"><use xlink:href="#icon-favorite"></use></svg></button>' +
                    '</div>' +
                '</div>' +
                '<div class="product-card__info">' +
                    '<a href="' + escHtml(url) + '"><h3 title="' + escHtml(item.name) + '">' + escHtml(item.name) + '</h3></a>' +
                    '<div class="price mb-2">' + priceHtml(item) + '</div>' +
                    '<div class="text-secondary">' +
                        '<small>Артикул: ' + escHtml(item.sku) + '</small>' +
                        stockHtml(item) +
                    '</div>' +
                '</div>' +
                '<div class="product-card__actions">' +
                    '<div class="cart-cnt">' +
                        '<div class="buttons">' +
                            '<button class="cart-cnt-plus"><svg width="20" height="20"><use xlink:href="#icon-arrow-up"></use></svg></button>' +
                            '<button class="cart-cnt-minus"><svg width="20" height="20"><use xlink:href="#icon-arrow-down"></use></svg></button>' +
                        '</div>' +
                        '<input type="text" data-min="1" data-max="' + (item.stock || 0) + '" value="1">' +
                    '</div>' +
                    '<button class="cart-add f-button c-primary">В корзину</button>' +
                    '<button class="cart-remove f-button c-gray text-secondary d-none">' +
                        '<svg width="16" height="16"><use xlink:href="#icon-delete"></use></svg><span class="pl-2">Удалить</span>' +
                    '</button>' +
                '</div>' +
                '<a href="#" class="stretched-link"></a>' +
            '</div>'
        );
    }

    /* ---------------- Данные ---------------- */

    function request(params, cb) {
        var qs = Object.keys(params).filter(function (k) {
            return params[k] !== '' && params[k] != null;
        }).map(function (k) {
            return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
        }).join('&');

        var xhr = new XMLHttpRequest();
        xhr.open('GET', endpoint + '?' + qs, true);
        xhr.onload = function () {
            if (xhr.status !== 200) { cb(null); return; }
            try {
                cb(JSON.parse(xhr.responseText));
            } catch (e) {
                cb(null);
            }
        };
        xhr.onerror = function () { cb(null); };
        xhr.send();
    }

    function eachView(method, a, b) {
        views.forEach(function (v) { v[method](a, b); });
    }

    /** reset=true — новый поиск/фильтр: всё с начала, ответ на устаревший
     *  запрос (если он ещё в пути) будет проигнорирован. */
    function load(reset) {
        if (reset) {
            state.requestId++;
            state.offset = 0;
            state.items = [];
            state.hasMore = false;
            state.loaded = false;
            state.loading = false;
            eachView('reset');
        }
        if (state.loading) return;

        state.loading = true;
        eachView('setLoading', true);
        var requestId = state.requestId;
        var params = {offset: state.offset, limit: limit};
        Object.keys(state.query).forEach(function (k) { params[k] = state.query[k]; });

        request(params, function (data) {
            if (requestId !== state.requestId) return;
            state.loading = false;
            eachView('setLoading', false);
            if (!data) return;

            var items = data.items || [];
            state.items = state.items.concat(items);
            state.offset += items.length;
            state.hasMore = !!data.hasMore;
            state.loaded = true;
            eachView('render', items);
        });
    }

    /** Представление, подключившееся позже первой загрузки (мобильная
     *  карусель ждёт window "load"), сразу получает уже загруженное. */
    function addView(view) {
        views.push(view);
        if (state.loaded) view.render(state.items);
        if (state.loading) view.setLoading(true);
    }

    /* ---------------- Десктоп: #catalog-grid ---------------- */

    function createDesktopView(grid) {
        var pager = document.getElementById('catalogGridPager');
        var empty = document.getElementById('catalogGridEmpty');
        var button = document.getElementById('load-more');

        if (button) {
            button.addEventListener('click', function (e) {
                // В scripts.js на body висит старый демо-обработчик #load-more
                // (подменял сетку статичной копией) — до него клик не доходит.
                e.stopPropagation();
                load(false);
            });
        }

        return {
            reset: function () {
                Array.prototype.forEach.call(grid.querySelectorAll('.product-item'), function (el) {
                    el.parentNode.removeChild(el);
                });
                if (empty) empty.classList.add('d-none');
                if (pager) pager.classList.add('d-none');
            },
            setLoading: function (loading) {
                if (!button) return;
                button.disabled = loading;
                var icons = button.querySelectorAll('svg');
                if (icons[0]) icons[0].classList.toggle('d-none', loading);
                if (icons[1]) icons[1].classList.toggle('d-none', !loading);
            },
            render: function (items) {
                var html = items.map(function (item) {
                    return '<div class="product-item">' + productCardHtml(item) + '</div>';
                }).join('');
                (empty || pager || grid).insertAdjacentHTML(empty || pager ? 'beforebegin' : 'beforeend', html);
                if (empty) empty.classList.toggle('d-none', state.items.length > 0);
                if (pager) pager.classList.toggle('d-none', !state.hasMore);
            }
        };
    }

    /* ---------------- Мобильная карусель: #catalog-search ---------------- */

    function createMobileView($, $search) {
        function ensureFlickity() {
            // Повторный вызов по уже готовой карусели — no-op (jquery-bridget).
            $search.flickity({cellAlign: 'left', contain: true, pageDots: false, freeScroll: true});
        }

        function removeCells(selector) {
            var $cells = $search.find(selector);
            if ($cells.length) {
                ensureFlickity();
                $search.flickity('remove', $cells);
            }
        }

        function append(html) {
            ensureFlickity();
            $search.flickity('append', $(html));
        }

        // Старый обработчик scripts.js вставлял статичный снимок карусели.
        $('body').off('click', '.load-more-slider').on('click', '.load-more-slider', function () {
            load(false);
        });

        return {
            reset: function () {
                removeCells('.carousel-cell');
            },
            setLoading: function (loading) {
                $search.find('.load-more-slider').prop('disabled', loading)
                    .find('svg').each(function (i) {
                        $(this).toggleClass('d-none', i === 0 ? loading : !loading);
                    });
            },
            render: function (items) {
                removeCells('.carousel-cell-last, .carousel-cell-empty');
                if (items.length) {
                    append(items.map(function (item) {
                        return '<div class="carousel-cell">' + productCardHtml(item) + '</div>';
                    }).join(''));
                }
                if (!state.items.length) {
                    append(
                        '<div class="carousel-cell carousel-cell-empty">' +
                            '<div class="product-card d-flex align-items-center justify-content-center text-center text-secondary p-4">' +
                                'По вашему запросу ничего не найдено' +
                            '</div>' +
                        '</div>'
                    );
                }
                if (state.hasMore) {
                    append(
                        '<div class="carousel-cell carousel-cell-last">' +
                            '<div class="product-card">' +
                                '<button class="load-more-slider">' +
                                    '<svg width="36" height="36"><use xlink:href="#icon-plus"></use></svg>' +
                                    '<svg width="24" height="24" class="icon-progress d-none"><use xlink:href="#icon-progress"></use></svg>' +
                                '</button>' +
                            '</div>' +
                        '</div>'
                    );
                }
            }
        };
    }

    /* ---------------- Поиск: строка, подсказки, история ---------------- */

    /* История поиска: у авторизованного — в аккаунте (HL-блок
       SearchHistory через /local/ajax/search_history.php), у гостя — в
       localStorage. При входе гостевая история переносится в аккаунт и
       удаляется из браузера, чтобы после выхода не осталась видна
       следующему человеку за этим компьютером. */
    var historyEndpoint = '/local/ajax/search_history.php';
    var searchHistory = {authorized: false, sessid: '', items: []};

    function readLocalHistory() {
        try {
            var list = JSON.parse(window.localStorage.getItem(HISTORY_KEY) || '[]');
            return Array.isArray(list) ? list : [];
        } catch (e) {
            return [];
        }
    }

    function writeLocalHistory(list) {
        try {
            if (list.length) {
                window.localStorage.setItem(HISTORY_KEY, JSON.stringify(list));
            } else {
                window.localStorage.removeItem(HISTORY_KEY);
            }
        } catch (e) {
            // приватный режим/заблокированное хранилище — просто без истории
        }
    }

    function historyRequest(method, fields, cb) {
        var body = Object.keys(fields).map(function (k) {
            var v = fields[k];
            return Array.isArray(v)
                ? v.map(function (item) { return encodeURIComponent(k + '[]') + '=' + encodeURIComponent(item); }).join('&')
                : encodeURIComponent(k) + '=' + encodeURIComponent(v);
        }).filter(Boolean).join('&');

        var xhr = new XMLHttpRequest();
        xhr.open(method, historyEndpoint, true);
        if (method === 'POST') xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            var data = null;
            try { data = JSON.parse(xhr.responseText); } catch (e) { /* пусто */ }
            cb(xhr.status === 200 ? data : null);
        };
        xhr.onerror = function () { cb(null); };
        xhr.send(method === 'POST' ? body : null);
    }

    function applyServerHistory(data) {
        if (!data) return;
        searchHistory.authorized = !!data.authorized;
        searchHistory.sessid = data.sessid || '';
        if (searchHistory.authorized) searchHistory.items = data.items || [];
        renderHistory();
    }

    function initHistory() {
        searchHistory.items = readLocalHistory().slice(0, HISTORY_SIZE);
        renderHistory();

        historyRequest('GET', {}, function (data) {
            if (!data || !data.authorized) return;
            var guest = readLocalHistory();
            if (!guest.length) {
                applyServerHistory(data);
                return;
            }
            historyRequest('POST', {sessid: data.sessid, action: 'merge', queries: guest}, function (merged) {
                if (!merged) {
                    applyServerHistory(data);
                    return;
                }
                writeLocalHistory([]);
                applyServerHistory(merged);
            });
        });
    }

    function pushHistory(q) {
        if (!q) return;
        var list = searchHistory.items.filter(function (item) {
            return item.toLowerCase() !== q.toLowerCase();
        });
        list.unshift(q);
        searchHistory.items = list.slice(0, HISTORY_SIZE);
        renderHistory();

        if (searchHistory.authorized) {
            historyRequest('POST', {sessid: searchHistory.sessid, action: 'add', q: q}, applyServerHistory);
        } else {
            writeLocalHistory(searchHistory.items);
        }
    }

    var searchIcon = '<svg width="16" height="16"><use xlink:href="#icon-search"></use></svg>';

    function renderHistory() {
        var box = document.getElementById('hero-search-history');
        if (!box) return;
        var list = searchHistory.items;
        box.classList.toggle('d-none', !list.length);
        box.querySelector('ul').innerHTML = list.map(function (q) {
            return '<li>' + searchIcon + '<a href="#" data-search-query="' + escHtml(q) + '">' + escHtml(q) + '</a></li>';
        }).join('');
    }

    function highlight(name, q) {
        var i = name.toLowerCase().indexOf(q.toLowerCase());
        if (i < 0) return escHtml(name);
        return escHtml(name.slice(0, i)) + '<b>' + escHtml(name.slice(i, i + q.length)) + '</b>' + escHtml(name.slice(i + q.length));
    }

    function initSearch() {
        var form = document.getElementById('hero-search-form');
        var input = document.getElementById('hero-search-input');
        var suggest = document.getElementById('hero-search-suggest');
        if (!form || !input) return;

        initHistory();

        function runSearch(q) {
            q = (q || '').trim();
            input.value = q;
            input.blur();
            pushHistory(q);
            state.query.q = q;
            load(true);
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            runSearch(input.value);
        });

        form.addEventListener('click', function (e) {
            var link = e.target.closest('[data-search-query]');
            if (!link) return;
            e.preventDefault();
            runSearch(link.getAttribute('data-search-query'));
        });

        if (!suggest) return;
        var timer = null;
        var suggestId = 0;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            var q = input.value.trim();
            if (q.length < 2) {
                suggest.innerHTML = '';
                return;
            }
            timer = setTimeout(function () {
                var id = ++suggestId;
                request({q: q, offset: 0, limit: SUGGEST_LIMIT}, function (data) {
                    if (id !== suggestId) return;
                    var items = (data && data.items) || [];
                    suggest.innerHTML = items.length
                        ? items.map(function (item) {
                            return '<li>' + searchIcon + '<a href="' + escHtml(item.url || '#') + '">' + highlight(item.name, q) + '</a></li>';
                        }).join('')
                        : '<li class="text-secondary">Ничего не найдено</li>';
                });
            }, 250);
        });
    }

    /* ---------------- Фильтр (#filter-popup) ---------------- */

    function filterBounds(form) {
        return {
            min: parseInt(form.getAttribute('data-price-min'), 10) || 0,
            max: parseInt(form.getAttribute('data-price-max'), 10) || 0
        };
    }

    function checkedValues(form, name) {
        return Array.prototype.map.call(form.querySelectorAll('input[name="' + name + '"]:checked'), function (el) {
            return el.value;
        });
    }

    /** Отмеченные категории: корневая целиком (сервер берёт её вместе с
     *  подкатегориями) либо отдельные подкатегории, если корневая не отмечена. */
    function selectedSections(form) {
        var ids = [];
        Array.prototype.forEach.call(form.querySelectorAll('.filter-cat'), function (cat) {
            var parent = cat.querySelector(':scope > label input[name="sections"]');
            if (parent && parent.checked) {
                ids.push(parent.value);
                return;
            }
            Array.prototype.forEach.call(cat.querySelectorAll('.filter-cat-children input[name="sections"]:checked'), function (el) {
                ids.push(el.value);
            });
        });
        return ids;
    }

    function setSliderValue(min, max) {
        if (window.jQuery && jQuery.fn.slider) {
            jQuery('#filterPrice').slider('setValue', [min, max]);
        }
    }

    function applyFilter(form) {
        var bounds = filterBounds(form);
        var priceMin = parseInt(form.querySelector('.filterPriceMin').value, 10);
        var priceMax = parseInt(form.querySelector('.filterPriceMax').value, 10);
        if (isNaN(priceMin) || priceMin < bounds.min) priceMin = bounds.min;
        if (isNaN(priceMax) || priceMax > bounds.max) priceMax = bounds.max;
        if (priceMin > priceMax) priceMin = priceMax;

        var sections = selectedSections(form);
        var colors = checkedValues(form, 'colors');
        var sizes = checkedValues(form, 'sizes');
        var priceActive = priceMin > bounds.min || priceMax < bounds.max;

        state.query.sections = sections.join(',');
        state.query.colors = colors.join('|');
        state.query.sizes = sizes.join('|');
        state.query.price_min = priceActive ? priceMin : '';
        state.query.price_max = priceActive ? priceMax : '';

        var count = sections.length + colors.length + sizes.length + (priceActive ? 1 : 0);
        var badge = document.getElementById('hero-filter-count');
        if (badge) {
            badge.textContent = count;
            badge.classList.toggle('d-none', !count);
        }

        if (window.jQuery && jQuery.fancybox) jQuery.fancybox.close();
        load(true);
    }

    document.addEventListener('submit', function (e) {
        if (e.target.id !== 'filter') return;
        e.preventDefault();
        applyFilter(e.target);
    });

    document.addEventListener('reset', function (e) {
        if (e.target.id !== 'filter') return;
        var form = e.target;
        // Нативный reset сбросит поля к значениям из разметки уже после
        // этого события — применяем фильтр следующим тиком.
        setTimeout(function () {
            var bounds = filterBounds(form);
            setSliderValue(bounds.min, bounds.max);
            applyFilter(form);
        }, 0);
    });

    // Категории: стрелка раскрывает подкатегории; отметка корневой
    // отмечает/снимает все её подкатегории, снятие подкатегории снимает
    // корневую.
    document.addEventListener('click', function (e) {
        var toggle = e.target.closest && e.target.closest('.filter-cat-toggle');
        if (!toggle) return;
        e.preventDefault();
        var cat = toggle.closest('.filter-cat');
        cat.classList.toggle('open');
        cat.querySelector('.filter-cat-children').classList.toggle('d-none', !cat.classList.contains('open'));
        if (window.jQuery) jQuery('#filter').trigger('sticky_kit:recalc');
    });

    document.addEventListener('change', function (e) {
        var input = e.target;
        if (input.name !== 'sections') return;
        var cat = input.closest('.filter-cat');
        if (!cat) return;
        var parent = cat.querySelector(':scope > label input');
        var children = cat.querySelectorAll('.filter-cat-children input');
        if (input === parent) {
            Array.prototype.forEach.call(children, function (el) { el.checked = parent.checked; });
        } else if (parent) {
            parent.checked = children.length > 0 && Array.prototype.every.call(children, function (el) { return el.checked; });
        }
    });

    // Ручной ввод цены → двигаем ползунки (обратное направление делает
    // scripts.js: change на #filterPrice → инпуты).
    document.addEventListener('change', function (e) {
        if (!e.target.classList || !(e.target.classList.contains('filterPriceMin') || e.target.classList.contains('filterPriceMax'))) return;
        var form = e.target.form;
        if (!form || form.id !== 'filter') return;
        var bounds = filterBounds(form);
        var min = parseInt(form.querySelector('.filterPriceMin').value, 10);
        var max = parseInt(form.querySelector('.filterPriceMax').value, 10);
        setSliderValue(isNaN(min) ? bounds.min : min, isNaN(max) ? bounds.max : max);
    });

    /* ---------------- Старт ---------------- */

    document.addEventListener('DOMContentLoaded', function () {
        var hero = document.getElementById('hero-search');
        if (!hero) return;
        limit = parseInt(hero.getAttribute('data-page-size'), 10) || limit;

        var grid = document.getElementById('catalog-grid');
        if (grid) addView(createDesktopView(grid));

        initSearch();
        load(true);
    });

    window.addEventListener('load', function () {
        if (typeof jQuery === 'undefined') return;
        var $search = jQuery('#catalog-search');
        if ($search.length) addView(createMobileView(jQuery, $search));
    });
})();
