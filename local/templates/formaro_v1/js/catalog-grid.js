/* #catalog-grid (десктоп) и #catalog-search (мобильная карусель) на
   главной (index.php) — реальные товары из cabinet_catalog вместо
   статичной демо-разметки. Данные и постраничная подгрузка —
   /local/ajax/catalog_grid.php (метки "Новинка"/"Топ продаж"/скидка и цену
   со скидкой считает сервер, см. ProductPricingService). Внутренняя
   разметка карточки (.product-card) — точная копия прежней статичной,
   поэтому кнопки "В корзину"/избранное продолжают работать как раньше —
   их обработчики в scripts.js навешаны через делегирование на body, не на
   сами карточки. */
(function () {
    var endpoint = '/local/ajax/catalog_grid.php';
    var limit = 24;

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

    /** Сама карточка товара — общая для десктопного грида (обёртка
     *  .product-item) и мобильной карусели (обёртка .carousel-cell). */
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

    function fetchPage(offset, cb) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', endpoint + '?offset=' + offset + '&limit=' + limit, true);
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

    /* ---------------- Десктоп: #catalog-grid ---------------- */
    (function initDesktopGrid() {
        var $grid = document.getElementById('catalog-grid');
        if (!$grid) return;

        var $pager = document.getElementById('catalogGridPager');
        var $loadMoreBtn = document.getElementById('load-more');
        var offset = 0;
        var loading = false;

        function cardHtml(item) {
            return '<div class="product-item">' + productCardHtml(item) + '</div>';
        }

        function setLoading(state) {
            loading = state;
            if (!$loadMoreBtn) return;
            $loadMoreBtn.disabled = state;
            var icons = $loadMoreBtn.querySelectorAll('svg');
            if (icons[0]) icons[0].classList.toggle('d-none', state);
            if (icons[1]) icons[1].classList.toggle('d-none', !state);
        }

        function loadPage() {
            if (loading) return;
            setLoading(true);
            fetchPage(offset, function (data) {
                setLoading(false);
                if (!data) return;
                var html = (data.items || []).map(cardHtml).join('');
                if ($pager) {
                    $pager.insertAdjacentHTML('beforebegin', html);
                } else {
                    $grid.insertAdjacentHTML('beforeend', html);
                }
                offset += (data.items || []).length;
                if ($pager) $pager.classList.toggle('d-none', !data.hasMore);
            });
        }

        if ($pager) $pager.classList.add('d-none');
        if ($loadMoreBtn) $loadMoreBtn.addEventListener('click', loadPage);

        loadPage();
    })();

    /* ---------------- Мобильная карусель: #catalog-search ----------------
       Раньше кнопка "+" (.load-more-slider) в последней ячейке просто
       заново вставляла тот же самый статичный набор карточек, снятый
       один раз при загрузке страницы (см. scripts.js — $loadMoreCatalogSearch
       = $catalogSearch.html(), захват до нашего вмешательства). Реальным
       данным это не подходит — снимаем тот старый обработчик и вешаем свой,
       с настоящей AJAX-подгрузкой по тому же эндпоинту, что и десктоп. */
    // window "load" (не DOMContentLoaded/jQuery-ready!) — сам тег
    // catalog-grid.js подключён в середине body, а jQuery и scripts.js
    // (footer.php) грузятся куда позже, самими последними тегами страницы.
    // На момент разбора ЭТОГО файла jQuery ещё не существует вообще —
    // jQuery(fn) тут же упал бы. "load" гарантированно наступает уже после
    // всех блокирующих <script> (включая jQuery и scripts.js), так что к
    // этому моменту и jQuery готов, и scripts.js уже успел навесить свой
    // старый .load-more-slider — можно надёжно его снять через .off().
    window.addEventListener('load', function initMobileCarousel() {
        if (typeof jQuery === 'undefined') return;
        var $ = jQuery;
        var $search = $('#catalog-search');
        if (!$search.length) return;

        var offset = 0;
        var loading = false;

        function cellHtml(item) {
            return '<div class="carousel-cell">' + productCardHtml(item) + '</div>';
        }

        function loadMoreCellHtml() {
            return (
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

        function ensureFlickityInited() {
            // Повторный вызов с опциями по уже готовой карусели — безопасный
            // no-op (jquery-bridget), а не пересоздание/дублирование.
            $search.flickity({cellAlign: 'left', contain: true, pageDots: false, freeScroll: true});
        }

        function appendItems(items) {
            if (!items.length) return;
            ensureFlickityInited();
            $search.flickity('append', $(items.map(cellHtml).join('')));
        }

        function loadPage() {
            if (loading) return;
            loading = true;
            $search.find('.load-more-slider').prop('disabled', true).find('svg').toggleClass('d-none');

            fetchPage(offset, function (data) {
                loading = false;
                var $oldTrailing = $search.find('.carousel-cell-last');
                if ($oldTrailing.length) {
                    ensureFlickityInited();
                    $search.flickity('remove', $oldTrailing);
                }
                if (!data) return;

                appendItems(data.items || []);
                offset += (data.items || []).length;
                if (data.hasMore) {
                    ensureFlickityInited();
                    $search.flickity('append', $(loadMoreCellHtml()));
                }
            });
        }

        // Старый обработчик из scripts.js работал со статичным снимком —
        // отвязываем именно его (единственный обработчик на этом селекторе)
        // и вешаем свой, с реальной подгрузкой.
        $('body').off('click', '.load-more-slider');
        $('body').on('click', '.load-more-slider', loadPage);

        loadPage();
    });
})();
