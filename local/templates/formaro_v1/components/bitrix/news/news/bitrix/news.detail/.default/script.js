/* Детальная новость: залипание сайдбара (#news-sidebar — партнёр, категории,
   товары). scripts.js включает stick_in_parent всегда, но sticky-kit
   дёргает блок при прокрутке, если он выше окна браузера или не ниже
   колонки с новостью. Оставляем залипание, только когда сайдбар целиком
   помещается в окно и ниже новости. Высоты меняются после загрузки
   картинок и при ресайзе — пересчитываем на load и resize.
   Файл подключается в <head>, jQuery и sticky-kit — в конце body. */
window.addEventListener('load', function () {
    var $ = window.jQuery;
    if (!$ || !$.fn.stick_in_parent) return;

    var $sidebar = $('#news-sidebar');
    var $main = $('.news-detail .news-gallery-wrap');
    if (!$sidebar.length || !$main.length) return;

    var OFFSET_TOP = 60; // как в scripts.js
    var sticky = true; // scripts.js уже включил

    function update() {
        var height = $sidebar.outerHeight(true);
        var fits = height < $main.outerHeight(true) && height + OFFSET_TOP <= window.innerHeight;
        if (fits && !sticky) {
            $sidebar.stick_in_parent({offset_top: OFFSET_TOP});
            sticky = true;
        } else if (!fits && sticky) {
            $sidebar.trigger('sticky_kit:detach');
            sticky = false;
        } else if (sticky) {
            $(document.body).trigger('sticky_kit:recalc');
        }
    }

    var timer = null;
    $(window).on('resize', function () {
        clearTimeout(timer);
        timer = setTimeout(update, 150);
    });

    update();
});
