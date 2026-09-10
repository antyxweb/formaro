$(function () {
    //
    $html = $("html");
    $body = $("body");
    $header = $("#header");
    $sidebar = $(".sidebar");
    $lastScroll = 0;

    //RESPONSIVE

    function responsive() {
        //PARAMS
        $wW = $(window).width();
        $wH = $(window).height();

        $('.news-card__info, .partners-card__info').css('height', 'auto');

        /*$('.product-card__info, .news-card__info, .partners-card__info').css('height', 'auto');

        $('.product-list, .product-carousel, .product-search-carousel').each(function () {
            $dH = 0;

            $(this).find('.product-card__info').each(function () {
                if($(this).outerHeight() > $dH) {
                    $dH = $(this).outerHeight();
                }
            });

            $(this).find('.product-card__info').outerHeight($dH);
        });*/

        $('.news-list').each(function () {
            $dH = 0;

            $(this).find('.news-card__info').each(function () {
                if($(this).outerHeight() > $dH) {
                    $dH = $(this).outerHeight();
                }
            });

            $(this).find('.news-card__info').outerHeight($dH);
        });

        $('.partners-list').each(function () {
            $dH = 0;

            $(this).find('.partners-card__info').each(function () {
                if($(this).outerHeight() > $dH) {
                    $dH = $(this).outerHeight();
                }
            });

            $(this).find('.partners-card__info').outerHeight($dH);
        });
    }
    responsive();
    $(window).on("resize", responsive);

    //SCROLLING

    function scrolling() {
        $wW = $(window).width();
        $wH = $(window).height();

        $top1 = $body.scrollTop();
        $top2 = $html.scrollTop();

        if ($top1) {
            $top = $top1;
        } else {
            $top = $top2;
        }


        if($top > 60) {
            $('.main-page').removeClass('main-page-active');
            $('.sidebar__actions-cookie').addClass('show');
        } else {
            $('.main-page').addClass('main-page-active');
            $('.sidebar__actions-cookie').removeClass('show');
        }

        if($top > $(window).height()) {
            $('.sidebar__actions-up').removeClass('hide');
        } else {
            $('.sidebar__actions-up').addClass('hide');
        }

        //Overtop
        if($top > $lastScroll && $top > 100) {
            $header.addClass('overtop-page');
            $sidebar.removeClass('overtop-page');
        } else {
            $header.removeClass('overtop-page');
            $sidebar.addClass('overtop-page');
        }
        $lastScroll = $top;
    }
    scrolling();
    $(window).on("scroll", scrolling);

    if($('#main-title').length) {
        $mainTitle = $('#main-title');
        $mainTitleText = $mainTitle.text().replace(" ","</span> ");
        $mainTitle.html('<span class="text-primary">'+$mainTitleText);
    }

    $('.show-bg-menu, .bg-menu-close, .bg-menu-open').click(function () {
        $header.toggleClass('bg-menu-active');
        $html.toggleClass('overflow-hidden');
        $body.toggleClass('overflow-hidden');
    });

    $('.sidebar__actions-cookie .f-button').click(function () {
        $('.sidebar__actions-cookie').css('opacity', 0).delay(300).css('height', 0);

        $.cookie('cookie-policy', 'Y');
    });
    if($.cookie('cookie-policy')) {
        $('.sidebar__actions-cookie').remove();
    }

    $('body').on('click', '.favorite', function () {
        $(this).find('button').toggleClass('d-none');
    });

    $('.sidebar__actions-up').click(function () {
        $('html, body').animate({ scrollTop: 0 }, 1000);
    });





    $catalogSearch = $('#catalog-search');
    $loadMoreCatalogSearch = $catalogSearch.html();

    $('body').on('click', '.load-more-slider', function (e) {
        if(!$(this).hasClass('bg-white')) {
            $(this).addClass('bg-white');
            $(this).find('svg').toggleClass('d-none');

            setTimeout(function () {
                $cellElement = $catalogSearch.find('.load-more-slider').closest('.carousel-cell');
                $cellIndex = $cellElement.index();
                var $cellElems = $($loadMoreCatalogSearch);
                $catalogSearch.flickity( 'remove', $cellElement );
                $catalogSearch.flickity( 'append', $cellElems );
                $catalogSearch.flickity( 'select', $cellIndex );
            }, 1000);
        }
    });


    $('.product-carousel').flickity({
        cellAlign: 'left',
        contain: true,
        pageDots: false,
        freeScroll: true,
    }).on( 'dragStart.flickity', function( event, pointer ) {
        document.ontouchmove = function (e) {
            e.preventDefault();
        }
    }).on( 'dragEnd.flickity', function( event, pointer ) {
        document.ontouchmove = function (e) {
            return true;
        }
    });

    $('.news-carousel').flickity({
        cellAlign: 'left',
        contain: true,
        pageDots: false,
        freeScroll: true,
    }).on( 'dragStart.flickity', function( event, pointer ) {
        document.ontouchmove = function (e) {
            e.preventDefault();
        }
    }).on( 'dragEnd.flickity', function( event, pointer ) {
        document.ontouchmove = function (e) {
            return true;
        }
    });

    $('.partners-carousel').flickity({
        cellAlign: 'left',
        contain: true,
        //wrapAround: true,
        //prevNextButtons: false,
        pageDots: false,
        freeScroll: true,
    }).on( 'dragStart.flickity', function( event, pointer ) {
        document.ontouchmove = function (e) {
            e.preventDefault();
        }
    }).on( 'dragEnd.flickity', function( event, pointer ) {
        document.ontouchmove = function (e) {
            return true;
        }
    });

    $('body').on('keydown', '.cart-cnt input', function (e) {
        // Числа и управляющие клавиши: backspace, delete, стрелки – особенные гости на вечеринке чисел!
        var validKeys = [8, 9, 13, 27, 35, 36, 37, 38, 39, 40, 46, 48, 57, 96, 105];
        var isCommand = e.metaKey || e.ctrlKey;
        var isNumber = (e.keyCode >= 48 && e.keyCode <= 57) || (e.keyCode >= 96 && e.keyCode <= 105);

        if ($.inArray(e.keyCode, validKeys) !== -1 || isCommand || isNumber)
            return; // Разрешить ввод
        e.preventDefault(); // Запретить нажатие нечисловых клавиш
    });

    $('body').on('keyup change', '.cart-cnt input', function (e) {
        $val = $(this).val();
        $min = $(this).data('min');
        $max = $(this).data('max');

        if($val < $min) {
            $(this).val($min);
        }

        if($max && $val > $max) {
            $(this).val($max);
        }
    });

    $('body').on('click', '.f-dropdown a', function (e) {
        $selectText = $(this).text();

        $(this).closest('.f-dropdown').find('a').removeClass('active');
        $(this).closest('.f-dropdown').find('span').text($selectText);
        $(this).addClass('active');
    });

    $('body').on('click', '.cart-cnt .buttons button', function (e) {
        $input = $(this).closest('.cart-cnt').find('input');
        $val = $input.val()*1;

        if($(this).hasClass('cart-cnt-plus')) {
            $val += 1;
        } else {
            $val -= 1;
        }

        $input.val($val);
        $input.trigger('change');
    });

    $('body').on('click', '.cart-add', function (e) {
        $(this).addClass('d-none');
        $(this).closest('.product-card__actions').addClass('in-cart');
        $(this).closest('.product-card__actions').find('.cart-remove').removeClass('d-none');
    });

    $('body').on('click', '.cart-remove', function (e) {
        $(this).addClass('d-none');
        $(this).closest('.product-card__actions').removeClass('in-cart');
        $(this).closest('.product-card__actions').find('.cart-add').removeClass('d-none');

        $min = $(this).closest('.product-card__actions').find('input').data('min');
        $(this).closest('.product-card__actions').find('input').val($min);
    });

    $('body').on('click', '.header__action-link-search', function (e) {
        $('#header-search').closest('.header__search').toggleClass('header-search-visible');
    });

    $('body').on('keyup', '.section-search-input', function (e) {
        $s = $(this).val();
        $sB = $(this).closest('section').find('.section-search-block');

        if($s) {
            $sB.hide();

            $(this).closest('section').find('.section-search-block:contains('+$s+')').show();
        } else {
            $sB.show();
        }
    });

    $('body').on('keyup', '.search-input', function (e) {
        $s = $(this).val();

        $(this).next('.search-result-block').find('.search-result-block-wrap-ajax span').text($s);

        if($s) {
            $(this).next('.search-result-block').find('.search-result-block-wrap-ajax').removeClass('d-none');
            $(this).next('.search-result-block').find('.search-result-block-wrap-offer').addClass('d-none');
        } else {
            $(this).next('.search-result-block').find('.search-result-block-wrap-ajax').addClass('d-none');
            $(this).next('.search-result-block').find('.search-result-block-wrap-offer').removeClass('d-none');
        }
    });


    $catalogGrid = $('#catalog-grid');
    $loadMoreContent = $catalogGrid.html();

    $('body').on('click', '#load-more', function (e) {
        if(!$(this).hasClass('c-white')) {
            $(this).addClass('c-white');
            $(this).find('svg').toggleClass('d-none');

            setTimeout(function () {
                $catalogGrid.find('.pager').remove();
                $catalogGrid.append($loadMoreContent);
            }, 1000);
        }
    });


    var placeholderText = [
        "Начните поиск здесь...",
        "Жилет спасательный",
        "Жилет с подогревом",
        "Нашивка"
    ];
    $('#hero-search-input').placeholderTypewriter({
        pause: 2000,
        text: placeholderText,
    });

    $('.scrollbar-inner').scrollbar();

    // FILTER

    if($('.filter-wrap').length) {
        $("#filter").stick_in_parent();
    }

    $("#filterPrice").slider({});

    $('body').on('change', '.filter #filterPrice', function (e) {
        $filterPriceVals = $(this).val().split(',');
        console.log($filterPriceVals);
        $('.filterPriceMin').val($filterPriceVals[0]);
        $('.filterPriceMax').val($filterPriceVals[1]);
    });

    $('body').on('click', '.filter .filter-row-list a', function (e) {
        $(this).closest('.filter-row-list').find('.collapse-list').toggleClass('d-none');
        $(this).find('span').toggleClass('d-none');

        $("#filter").trigger("sticky_kit:recalc");
    });

    $('body').on('click', '.filter .filter-row-title', function (e) {
        $(this).closest('.filter-row').toggleClass('collapse-row');

        $("#filter").trigger("sticky_kit:recalc");
    });

    // PRODUCT

    if($('.product-gallery-wrap').length) {
        $('.product-gallery-carousel').flickity({
            cellAlign: 'left',
            contain: true,
            //wrapAround: true,
            //prevNextButtons: false,
            pageDots: false,
            freeScroll: true,
            fullscreen: true,
            lazyLoad: 1
        }).on( 'dragStart.flickity', function( event, pointer ) {
            document.ontouchmove = function (e) {
                e.preventDefault();
            }
        }).on( 'dragEnd.flickity', function( event, pointer ) {
            document.ontouchmove = function (e) {
                return true;
            }
        });
    }

    $('body').on('click', '.tab-head button', function (e) {
        $(this).closest('.tab-wrap').find('.tab-head button').removeClass('c-secondary').addClass('c-white');
        $(this).removeClass('c-white').addClass('c-secondary');

        $tabId = $(this).attr('data-tab');

        $(this).closest('.tab-wrap').find('.tab-body .tab-content').addClass('d-none');
        $(this).closest('.tab-wrap').find('.tab-body .tab-'+$tabId).removeClass('d-none');
    });

    $('body').on('click', '[data-click-tab]', function (e) {
        $tabId = $(this).data('click-tab');

        $('.tab-head button[data-tab='+$tabId+']').trigger('click');

        if($(window).width() >= 768) {
            $offset = 60;
        } else {
            $offset = 30;
        }

        $('html, body').animate({ scrollTop: ($('.product-tabs-wrap').offset().top - $offset) }, 1000);
    });

    if($('.product-option-wrap').length) {
        $("#product-option").stick_in_parent({
            offset_top: 60,
        });
    }

    if($('.product-sidebar-wrap').length) {
        $("#product-sidebar").stick_in_parent({
            //offset_top: 0,
        });
    }

    //FAVORITES
    if($('.favorites-page').length) {
        $('.favorite').each(function () {
            $(this).find('button').toggleClass('d-none');
        });
    }

    //NEWS

    if($('.news-sidebar-wrap').length) {
        $("#news-sidebar").stick_in_parent({
            offset_top: 60,
        });
    }

    //CART

    $('body').on('click', '#cart-check', function (e) {
        $('.cart-grid input').prop('checked', true);
    });

    $('body').on('click', '#cart-uncheck', function (e) {
        $('.cart-grid input').prop('checked', false);
    });








    //TEMP
    $('.cart-grid .product-item .cart-add').not('.d-none').each(function () {
        $(this).trigger('click');
    });

    $('.svg-sprite symbol').each(function () {
        $attrId = $(this).attr('id');
        $('#main .svg-icons').append('<button class="button-icon">\n' +
            '      <svg width="20" height="20">\n' +
            '        <use xlink:href="#'+$attrId+'"></use>\n' +
            '      </svg>\n' +
            '      <small class="sidebar-tooltip">'+$attrId+'</small>\n' +
            '    </button>');
    });

});
