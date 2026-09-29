/* Карусели bitrix:news.list (шаблоны news-slider, partners-slider): слайд «+»
   в конце подгружает следующую страницу списка. Берём ту же страницу с
   PAGEN_<NavNum>=<следующая> — компонент отдаёт её со своими фильтрами и
   кэшем, — находим в ответе эту же карусель по data-nav-num и переносим её
   слайды. На последней странице «+» убирается.

   Карусели товаров (formaro:product.carousel) подгружаются своим
   script.js через JSON. */
(function ($) {
    if (!$) return;

    /** append/insert/remove во Flickity возвращают карусель к выбранному
     *  слайду (при freeScroll — к первому). Сохраняем позицию. */
    function keepPosition($carousel, fn) {
        var flkty = $carousel.data('flickity');
        var x = flkty ? flkty.x : null;
        fn();
        flkty = $carousel.data('flickity');
        if (flkty && x !== null) {
            flkty.x = x;
            flkty.positionSlider();
        }
    }

    function setLoading($carousel, value) {
        $carousel.data('carouselMoreLoading', value);
        $carousel.find('.load-more-slider').prop('disabled', value)
            .find('svg').each(function (i) {
                $(this).toggleClass('d-none', i === 0 ? value : !value);
            });
    }

    function pageUrl(navNum, page) {
        var url = new URL(window.location.href);
        url.hash = '';
        url.searchParams.set('PAGEN_' + navNum, page);
        return url.toString();
    }

    function load($carousel) {
        if ($carousel.data('carouselMoreLoading')) return;
        var navNum = $carousel.attr('data-nav-num');
        var page = (parseInt($carousel.attr('data-nav-page'), 10) || 1) + 1;
        var pageCount = parseInt($carousel.attr('data-nav-page-count'), 10) || 1;
        setLoading($carousel, true);

        $.get(pageUrl(navNum, page)).done(function (html) {
            var doc = new DOMParser().parseFromString(html, 'text/html');
            var source = doc.querySelector('.js-carousel-more[data-nav-num="' + navNum + '"]');
            var cells = source ? source.querySelectorAll('.carousel-cell:not(.carousel-cell-last)') : [];
            $carousel.attr('data-nav-page', page);

            keepPosition($carousel, function () {
                var $last = $carousel.find('.carousel-cell-last');
                if (cells.length) {
                    var index = $carousel.find('.carousel-cell').index($last);
                    $carousel.flickity('insert', $(Array.prototype.map.call(cells, function (cell) {
                        return document.importNode(cell, true);
                    })), index);
                }
                if (!cells.length || page >= pageCount) {
                    $carousel.flickity('remove', $last);
                }
            });
            // Высоту карточек выравнивает scripts.js на resize.
            $(window).trigger('resize');
        }).always(function () {
            setLoading($carousel, false);
        });
    }

    // Обработчик на самой карусели + stopPropagation: на body висит
    // демо-обработчик .load-more-slider из scripts.js.
    $(function () {
        $('.js-carousel-more').on('click', '.load-more-slider', function (e) {
            e.preventDefault();
            e.stopPropagation();
            load($(e.delegateTarget));
        });
    });
})(window.jQuery);
