/* Живые счётчики непрочитанного для вошедшего покупателя: уведомления
   ([data-notify-count]) и ответы продавцов в чатах ([data-messages-count]) —
   в меню кабинета покупателя и на плитках /personal/. Раз в 20 с и при
   возврате на вкладку — GET /local/ajax/counters.php. Счётчики с
   [data-hide-zero] при нуле скрываются (у меню кабинета это делает его
   шаблон). Подключается в footer.php только для вошедших. */
(function () {
    var INTERVAL = 20000;

    function apply(name, value) {
        document.querySelectorAll('[data-' + name + '-count]').forEach(function (node) {
            if (node.textContent !== String(value)) node.textContent = value;
            if (node.hasAttribute('data-hide-zero')) node.classList.toggle('d-none', !(value > 0));
        });
    }

    function refresh() {
        if (document.hidden) return;
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '/local/ajax/counters.php', true);
        xhr.onload = function () {
            if (xhr.status !== 200) return;
            try {
                var data = JSON.parse(xhr.responseText);
                apply('notify', data.notify | 0);
                apply('messages', data.messages | 0);
            } catch (err) { /* пусто */ }
        };
        xhr.send();
    }

    setInterval(refresh, INTERVAL);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) refresh(); });
})();
