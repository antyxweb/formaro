var newsId = 0;
var allNews = [];
var previewDataUrl = '';
var fullDataUrl = '';

var newsDatePicker = null;

$(function () {
    loadPartials('news', 'Новость');
    newsId = (getQueryParam('id') && getQueryParam('id') !== 'new') ? parseInt(getQueryParam('id'), 10) : 0;

    bindImagePreview('#previewFile', '#previewImg', function (url) { previewDataUrl = url; });
    bindImagePreview('#fullFile', '#fullImg', function (url) { fullDataUrl = url; });
    bindImageRemove('#previewRemoveBtn', '#previewImg', 'https://placehold.co/200x200?text=%20', function () { previewDataUrl = ''; });
    bindImageRemove('#fullRemoveBtn', '#fullImg', 'https://placehold.co/200x200?text=%20', function () { fullDataUrl = ''; });
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

    dsLoad('news', 'data/news.json').done(function (rows) {
        allNews = ownOnly(rows);
        if (newsId) {
            var n = allNews.find(function (x) { return x.id === newsId; });
            if (!n) { showResult(false, 'Новость не найдена'); return; }
            $('#previewBtn').attr('href', 'preview.html?type=news&id=' + n.id);
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
            if (n.image) { setBgImage('#previewImg', n.image); previewDataUrl = n.image; setBgImage('#fullImg', n.image); fullDataUrl = n.image; }
        } else {
            if (newsDatePicker) newsDatePicker.setDate(new Date(), true); else $('#f_datetime').val(fmtDateInput(new Date().toISOString()));
        }
    });

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

    var payload = {
        title: title,
        slug: $('#f_code').val().trim() || slugifyClient(title),
        short_desc: $('#f_short_desc').val(),
        full_desc: fullDesc,
        image: previewDataUrl || fullDataUrl,
        created_at: parseDateInput($('#f_datetime').val()),
        status: $('#f_status').val(),
        partner_id: CURRENT_PARTNER_ID
    };

    if (newsId) {
        allNews = allNews.map(function (n) { return n.id === newsId ? Object.assign({}, n, payload) : n; });
    } else {
        payload.id = dsNextId(allNews);
        allNews.push(payload);
        newsId = payload.id;
        $('#f_id').val(newsId);
        $('#formTitle').text('Редактирование: ' + title);
        $('#previewBtn').attr('href', 'preview.html?type=news&id=' + newsId);
    }
    saveOwnRows('news', 'data/news.json', allNews, function () {
        showResult(true, newsId ? 'Новость сохранена' : 'Новость создана');
        if (goBack) setTimeout(function () { window.location.href = 'news.html'; }, 700);
    });
}

function doDelete() {
    if (!newsId) return;
    var n = allNews.find(function (x) { return x.id === newsId; });
    showConfirm('Удалить новость «' + (n ? n.title : '') + '»? Это действие необратимо.', function () {
        allNews = allNews.filter(function (x) { return x.id !== newsId; });
        saveOwnRows('news', 'data/news.json', allNews, function () {
            showResult(true, 'Новость удалена');
            setTimeout(function () { window.location.href = 'news.html'; }, 700);
        });
    }, {danger: true, okText: 'Удалить'});
}
