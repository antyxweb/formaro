/* #catalog-grid на главной (index.php) — реальные товары из cabinet_catalog
   вместо статичной демо-разметки. Данные и постраничная подгрузка —
   /local/ajax/catalog_grid.php (метки "Новинка"/"Топ продаж"/скидка и цену
   со скидкой считает сервер, см. ProductPricingService). Разметка карточки
   — точная копия прежней статичной (те же классы/иконки), поэтому кнопки
   "В корзину"/избранное продолжают работать как раньше — их обработчики в
   scripts.js навешаны через делегирование на document/body, не на сами
   карточки. */
(function () {
    var $grid = document.getElementById('catalog-grid');
    if (!$grid) return;

    var $pager = document.getElementById('catalogGridPager');
    var $loadMoreBtn = document.getElementById('load-more');
    var endpoint = '/local/ajax/catalog_grid.php';
    var limit = 24;
    var offset = 0;
    var loading = false;

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

    function cardHtml(item) {
        var url = item.url || '#';
        return (
            '<div class="product-item">' +
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
                '</div>' +
            '</div>'
        );
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

        var xhr = new XMLHttpRequest();
        xhr.open('GET', endpoint + '?offset=' + offset + '&limit=' + limit, true);
        xhr.onload = function () {
            setLoading(false);
            if (xhr.status !== 200) return;
            var data;
            try {
                data = JSON.parse(xhr.responseText);
            } catch (e) {
                return;
            }
            var html = (data.items || []).map(cardHtml).join('');
            if ($pager) {
                $pager.insertAdjacentHTML('beforebegin', html);
            } else {
                $grid.insertAdjacentHTML('beforeend', html);
            }
            offset += (data.items || []).length;
            if ($pager) $pager.classList.toggle('d-none', !data.hasMore);
        };
        xhr.onerror = function () { setLoading(false); };
        xhr.send();
    }

    if ($pager) $pager.classList.add('d-none');
    if ($loadMoreBtn) $loadMoreBtn.addEventListener('click', loadPage);

    loadPage();
})();
