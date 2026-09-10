document.addEventListener('DOMContentLoaded', () => {

    $('body').on('click', '#load-more', function (e) {
        e.preventDefault();
        $href = $(this).data('href');

        $(this).find('svg').toggleClass('d-none');
        $(this).find('span').addClass('text-muted').text('Загрузка...');

        $.ajax({
            url: $href,
            type: 'get',
            success: function(result) {
                $('#pagerNav').remove();
                $list = $(result).find('#pagerGrid').html();
                $('#pagerGrid').append($list);

                window.history.pushState('', '', $href);

            }
        });
    });

    $('body').on('click', '.pagging__item', function (e) {
        e.preventDefault();
        $href = $(this).attr('href');

        $.ajax({
            url: $href,
            type: 'get',
            success: function(result) {
                $('#pagerNav').remove();
                $list = $(result).find('#pagerGrid').html();
                $('#pagerGrid').html($list);

                window.history.pushState('', '', $href);
            }
        });
    });

});