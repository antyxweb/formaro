/* formaro:product.carousel — слайд «+» в конце карусели подгружает следующие
   data-page-size товаров из /local/ajax/product_carousel.php. Пока идёт
   загрузка, перед «+» стоят скелетоны карточек. Когда товаров больше нет,
   слайд «+» убирается.

   MODE=VIEWED («Просмотренные товары»): список id — window.FormaroViewed
   (js/catalog-common.js: аккаунт или localStorage), сервер рисует пустую
   скрытую секцию, товары загружаются здесь.

   Карусель (Flickity) включает scripts.js; этот файл подключается в <head>,
   jQuery — в конце body, поэтому всё — на DOMContentLoaded и по клику. */
(function () {
    var VIEWED_MAX = 20;

    document.addEventListener('DOMContentLoaded', function () {
        var $ = window.jQuery;
        if (!$ || !window.FormaroProductCard || !window.FormaroViewed) return;

        var SKELETONS = 3;

        function params($carousel) {
            return {
                mode: $carousel.attr('data-mode'),
                partner: $carousel.attr('data-partner-id') || 0,
                section: $carousel.attr('data-section-id') || 0,
                exclude: $carousel.attr('data-exclude-id') || 0
            };
        }

        function cellsHtml(items) {
            return items.map(function (item) {
                return '<div class="carousel-cell">' + window.FormaroProductCard.html(item) + '</div>';
            }).join('');
        }

        function initViewed($carousel) {
            window.FormaroViewed.onReady(function (list) {
                loadViewed($carousel, list);
            });
        }

        function loadViewed($carousel, list) {
            var exclude = Number($carousel.attr('data-exclude-id')) || 0;
            var ids = list.filter(function (id) { return id !== exclude; });
            if (!ids.length) return;

            var query = params($carousel);
            query.ids = ids.join(',');
            query.limit = VIEWED_MAX;
            $.getJSON('/local/ajax/product_carousel.php', query).done(function (data) {
                var items = data.items || [];
                if (!items.length) return;
                $carousel.closest('section').removeClass('d-none');
                var flkty = $carousel.data('flickity');
                if (flkty) {
                    $carousel.flickity('append', $(cellsHtml(items)));
                    flkty.resize();
                } else {
                    $carousel.html(cellsHtml(items));
                }
            });
        }

        $('.js-product-carousel').each(function () {
            var $carousel = $(this);
            var loading = false;

            if ($carousel.attr('data-mode') === 'VIEWED') {
                initViewed($carousel);
                return;
            }

            /** append/insert/remove во Flickity возвращают карусель к выбранному
             *  слайду (при freeScroll — к первому). Сохраняем позицию. */
            function keepPosition(fn) {
                var flkty = $carousel.data('flickity');
                var x = flkty ? flkty.x : null;
                fn();
                flkty = $carousel.data('flickity');
                if (flkty && x !== null) {
                    flkty.x = x;
                    flkty.positionSlider();
                }
            }

            function lastIndex() {
                return $carousel.find('.carousel-cell').index($carousel.find('.carousel-cell-last'));
            }

            function setLoading(value) {
                loading = value;
                $carousel.find('.load-more-slider').prop('disabled', value)
                    .find('svg').each(function (i) {
                        $(this).toggleClass('d-none', i === 0 ? value : !value);
                    });
            }

            function load() {
                if (loading) return;
                setLoading(true);
                keepPosition(function () {
                    $carousel.flickity('insert', $(window.FormaroProductCard.skeletonsHtml(SKELETONS, 'carousel-cell')), lastIndex());
                });

                var offset = parseInt($carousel.attr('data-offset'), 10) || 0;
                var query = params($carousel);
                query.offset = offset;
                query.limit = parseInt($carousel.attr('data-page-size'), 10) || 12;
                $.getJSON('/local/ajax/product_carousel.php', query).done(function (data) {
                    var items = data.items || [];
                    $carousel.attr('data-offset', offset + items.length);
                    keepPosition(function () {
                        $carousel.flickity('remove', $carousel.find('.product-skeleton'));
                        if (items.length) {
                            $carousel.flickity('insert', $(cellsHtml(items)), lastIndex());
                        }
                        if (!data.hasMore || !items.length) {
                            $carousel.flickity('remove', $carousel.find('.carousel-cell-last'));
                        }
                    });
                }).fail(function () {
                    keepPosition(function () {
                        $carousel.flickity('remove', $carousel.find('.product-skeleton'));
                    });
                }).always(function () {
                    setLoading(false);
                });
            }

            // stopPropagation — чтобы не сработал демо-обработчик .load-more-slider
            // из scripts.js, висящий на body.
            $carousel.on('click', '.load-more-slider', function (e) {
                e.preventDefault();
                e.stopPropagation();
                load();
            });
        });
    });
})();
