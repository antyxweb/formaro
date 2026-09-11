/* Порт cabinet-html/assets/js/news-detail.js.
   Изменения относительно прототипа:
   - id новости — из ROUTE_ID (путь /news/edit/#ID#/), не из ?id=;
   - новость теперь настоящий элемент инфоблока cabinet_news — сохранение/
     удаление напрямую через dsSaveOne/dsDeleteOne (сервер сам возвращает
     реальный id), как и в discount-detail.js — не нужно гонять локальный
     массив "всех новостей", т.к. запись всегда одна своя, без чужих
     записей в памяти, которые надо было бы не потерять;
   - "Предпросмотр" пока отключён в разметке (публичной витрины новостей
     ещё нет) — ссылка на preview.html убрана. */
var CABINET_URL = window.CABINET_BOOTSTRAP.cabinetUrl;

var newsId = 0;
var previewDataUrl = '';

var newsDatePicker = null;

$(function () {
    newsId = (ROUTE_ID && ROUTE_ID !== 'new') ? parseInt(ROUTE_ID, 10) : 0;

    bindImagePreview('#previewFile', '#previewImg', function (url) { previewDataUrl = url; });
    bindImageRemove('#previewRemoveBtn', '#previewImg', 'https://placehold.co/200x200?text=%20', function () { previewDataUrl = ''; });
    initRichText('#f_full_desc');
    bindAutoHeight('#f_short_desc');

    if (window.flatpickr) {
        newsDatePicker = flatpickr('#f_datetime', {
            enableTime: true,
            dateFormat: 'd.m.Y H:i',
            time_24hr: true,
            minDate: 'today', // запрещаем выбор даты из прошлого
            locale: (flatpickr.l10ns && flatpickr.l10ns.ru) ? 'ru' : undefined
        });
    }

    var codeChain = bindCodeChain('#f_title', '#f_code', '#codeChainBtn');

    if (newsId) {
        dsLoad('news').done(function (rows) {
            var n = ownOnly(rows).find(function (x) { return x.id === newsId; });
            if (!n) { showResult(false, 'Новость не найдена'); return; }
            $('#deleteBtn').removeClass('d-none');
            $('#formTitle').text('Редактирование: ' + n.title);
            $('#f_id').val(n.id);
            $('#f_title').val(n.title);
            $('#f_code').val(n.slug); codeChain.setExisting();
            $('#f_short_desc').val(n.short_desc); autoHeightResize('#f_short_desc');
            $('#f_status').val(n.status || 'active');
            $('#f_full_desc').val(n.full_desc);
            if ($.fn.trumbowyg) $('#f_full_desc').trumbowyg('html', n.full_desc || '');
            if (newsDatePicker) newsDatePicker.setDate(new Date(n.created_at), true); else $('#f_datetime').val(fmtDateInput(n.created_at));
            if (n.image) { setBgImage('#previewImg', n.image); previewDataUrl = n.image; }
        });
    } else {
        if (newsDatePicker) newsDatePicker.setDate(new Date(), true); else $('#f_datetime').val(fmtDateInput(new Date().toISOString()));
    }

    $('#saveBtn').on('click', function () { doSave(true); });
    $('#applyBtn').on('click', function () { doSave(false); });
    $('#deleteBtn').on('click', doDelete);
});

/** dd.mm.yyyy hh:mm — формат поля даты/времени (flatpickr) */
function fmtDateInput(iso) {
    var d = new Date(iso);
    if (isNaN(d.getTime())) return '';
    function p(n) { return (n < 10 ? '0' : '') + n; }
    return p(d.getDate()) + '.' + p(d.getMonth() + 1) + '.' + d.getFullYear() + ' ' + p(d.getHours()) + ':' + p(d.getMinutes());
}
/** dd.mm.yyyy hh:mm → ISO */
function parseDateInput(str) {
    var m = /^(\d{2})\.(\d{2})\.(\d{4})[ T](\d{2}):(\d{2})/.exec(str || '');
    if (!m) return new Date().toISOString();
    return new Date(m[3], m[2] - 1, m[1], m[4], m[5]).toISOString();
}

function doSave(goBack) {
    var title = $('#f_title').val().trim();
    if (!title) { showResult(false, 'Укажите заголовок новости'); return; }
    var fullDesc = $.fn.trumbowyg ? $('#f_full_desc').trumbowyg('html') : $('#f_full_desc').val();

    var wasNew = !newsId;
    var payload = {
        id: newsId || undefined,
        title: title,
        slug: $('#f_code').val().trim() || slugifyClient(title),
        short_desc: $('#f_short_desc').val(),
        full_desc: fullDesc,
        image: previewDataUrl,
        created_at: parseDateInput($('#f_datetime').val()),
        status: $('#f_status').val()
    };

    dsSaveOne('news', payload).done(function (saved) {
        newsId = saved.id;
        $('#f_id').val(newsId);
        $('#formTitle').text('Редактирование: ' + title);
        $('#deleteBtn').removeClass('d-none');
        showResult(true, wasNew ? 'Новость создана' : 'Новость сохранена');
        if (goBack) setTimeout(function () { window.location.href = CABINET_URL + 'news/'; }, 700);
    });
}

function doDelete() {
    if (!newsId) return;
    var title = $('#f_title').val();
    showConfirm('Удалить новость «' + title + '»? Это действие необратимо.', function () {
        dsDeleteOne('news', newsId).done(function () {
            showResult(true, 'Новость удалена');
            setTimeout(function () { window.location.href = CABINET_URL + 'news/'; }, 700);
        });
    }, {danger: true, okText: 'Удалить'});
}
