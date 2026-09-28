/* Крестик очистки поиска по категориям (.section-search). Сама фильтрация
   блоков — в scripts.js (обработчик keyup/input на .section-search-input),
   поэтому после очистки просто шлём событие input. Файл подключается в
   <head>, отсюда делегирование на document. */
(function () {
    function clearButtonFor(input) {
        var wrap = input.closest('.section-search');
        return wrap ? wrap.querySelector('.section-search-clear') : null;
    }

    document.addEventListener('input', function (e) {
        var input = e.target;
        if (!input.classList || !input.classList.contains('section-search-input')) return;
        var button = clearButtonFor(input);
        if (button) button.classList.toggle('d-none', input.value === '');
    });

    document.addEventListener('click', function (e) {
        var button = e.target.closest && e.target.closest('.section-search-clear');
        if (!button) return;
        e.preventDefault();
        var input = button.closest('.section-search').querySelector('.section-search-input');
        if (!input) return;
        input.value = '';
        input.dispatchEvent(new Event('input', {bubbles: true}));
        input.focus();
    });
})();
