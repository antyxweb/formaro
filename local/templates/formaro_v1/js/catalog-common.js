/* Общий код витрины для компонентов, которые рисуют карточки товаров и
   фильтр на клиенте (formaro:catalog.search на главной,
   formaro:favorites.list в /personal/favorites/).

   window.FormaroProductCard.html(item) — разметка .product-card по строке
     из /local/ajax/catalog_grid.php или favorites_list.php (метки/цену со
     скидкой уже посчитал сервер, см. ProductPricingService).
   window.FormaroCatalogFilter — чтение формы фильтра (#filter): категории,
     цена, чекбоксы; плюс поведение дерева категорий и полей цены.

   Подключается в footer.php синхронно, до DOMContentLoaded — скрипты
   компонентов, которые грузятся в <head>, пользуются им уже после этого
   события. */
(function () {
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
        var current = '<b class="text-danger" data-price="' + item.price + '">' + fmtPrice(item.price) + '</b> <small>руб/шт.</small>';
        return item.old_price ? '<s>' + fmtPrice(item.old_price) + '</s> ' + current : current;
    }

    function stockHtml(item) {
        if (item.stock > 0) return '<small>В наличии: ' + item.stock + ' шт.</small>';
        if (item.is_preorder) return '<small>Под заказ</small>';
        return '<small>Нет в наличии</small>';
    }

    /** Сама карточка (.product-card) — обёртку (.product-item в сетке,
     *  .carousel-cell в карусели) добавляет вызывающий код. */
    function productCardHtml(item) {
        var url = item.url || '#';
        return (
            '<div class="product-card">' +
                '<div class="product-card__img">' +
                    '<a href="' + escHtml(url) + '" class="embed-responsive embed-responsive-1by1" style="background-image: url(\'' + escHtml(item.image || '') + '\')"></a>' +
                    badgesHtml(item.badges) +
                    '<div class="favorite" data-product-id="' + Number(item.id) + '">' +
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

    /** Скелетон карточки — пока товары грузятся. Серверная копия —
     *  include/product_card_skeleton.php. */
    function skeletonHtml() {
        return (
            '<div class="product-card product-card--skeleton" aria-hidden="true">' +
                '<div class="product-card__img"><div class="embed-responsive embed-responsive-1by1 skeleton-img"></div></div>' +
                '<div class="product-card__info">' +
                    '<div class="skeleton-title">' +
                        '<div class="skeleton-line" style="width: 95%"></div>' +
                        '<div class="skeleton-line" style="width: 70%"></div>' +
                    '</div>' +
                    '<div class="skeleton-line skeleton-price"></div>' +
                    '<div class="skeleton-line skeleton-meta"></div>' +
                    '<div class="skeleton-line skeleton-meta" style="width: 45%"></div>' +
                '</div>' +
                '<div class="product-card__actions"><div class="skeleton-action"></div></div>' +
            '</div>'
        );
    }

    /** n скелетонов в обёртке wrapperClass (product-item / carousel-cell);
     *  у обёртки класс product-skeleton — по нему их убирают. */
    function skeletonsHtml(n, wrapperClass) {
        var html = '';
        for (var i = 0; i < n; i++) {
            html += '<div class="' + wrapperClass + ' product-skeleton">' + skeletonHtml() + '</div>';
        }
        return html;
    }

    window.FormaroProductCard = {html: productCardHtml, escHtml: escHtml, skeletonHtml: skeletonHtml, skeletonsHtml: skeletonsHtml};

    /* ---------------- Фильтр ---------------- */

    function bounds(form) {
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

    /** Цена из полей формы, зажатая в границы; active=false — диапазон
     *  не сужен (фильтровать по цене не нужно). */
    function readPrice(form) {
        var b = bounds(form);
        var min = parseInt(form.querySelector('.filterPriceMin').value, 10);
        var max = parseInt(form.querySelector('.filterPriceMax').value, 10);
        if (isNaN(min) || min < b.min) min = b.min;
        if (isNaN(max) || max > b.max) max = b.max;
        if (min > max) min = max;
        return {min: min, max: max, active: min > b.min || max < b.max};
    }

    window.FormaroCatalogFilter = {
        bounds: bounds,
        checkedValues: checkedValues,
        selectedSections: selectedSections,
        setSliderValue: setSliderValue,
        readPrice: readPrice
    };

    // Категории: стрелка раскрывает подкатегории; отметка корневой
    // отмечает/снимает все её подкатегории, снятие подкатегории снимает
    // корневую, отметка всех — ставит обратно.
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
        var cl = e.target.classList;
        if (!cl || !(cl.contains('filterPriceMin') || cl.contains('filterPriceMax'))) return;
        var form = e.target.form;
        if (!form || form.id !== 'filter') return;
        var b = bounds(form);
        var min = parseInt(form.querySelector('.filterPriceMin').value, 10);
        var max = parseInt(form.querySelector('.filterPriceMax').value, 10);
        setSliderValue(isNaN(min) ? b.min : min, isNaN(max) ? b.max : max);
    });
})();
