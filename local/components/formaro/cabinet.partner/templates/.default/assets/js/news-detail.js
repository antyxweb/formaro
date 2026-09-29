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
var fullDataUrl = '';

var newsDatePicker = null;

// Вкладка «Каталог»: привязка новости к категориям и своим товарам.
var catalogCategories = [];
var catalogProducts = [];
var selectedCatalogProducts = [];
// Пока справочники не загружены, дерево/список пустые — сохранять их
// нельзя, иначе затрём уже сохранённую привязку (см. doSave()).
var catalogReady = false;

$(function () {
    newsId = (ROUTE_ID && ROUTE_ID !== 'new') ? parseInt(ROUTE_ID, 10) : 0;

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

    var codeChain = bindCodeChain('#f_title', '#f_code');

    bindCatalogProductSearch();
    $('#f_catalog_sections').on('change', 'input[type=checkbox]', function () {
        // дерево обновляет своё состояние в том же обработчике — считаем после
        setTimeout(updateCatalogBadges, 0);
    });

    var newsLoaded = $.Deferred();
    dsLoad('categories').done(function (cats) {
        catalogCategories = visibleCategories(cats);
        dsLoad('products').done(function (prods) {
            catalogProducts = ownOnly(prods);
            newsLoaded.done(function (n) {
                renderCategoryCheckboxTree('#f_catalog_sections', catalogCategories, (n && n.catalog_section_ids) || [], []);
                selectedCatalogProducts = ((n && n.catalog_product_ids) || []).slice();
                renderCatalogProductChips();
                catalogReady = true;
                updateCatalogBadges();
            });
        });
    });

    if (newsId) {
        dsLoad('news').done(function (rows) {
            var n = ownOnly(rows).find(function (x) { return x.id === newsId; });
            if (!n) { showResult(false, 'Новость не найдена'); return; }
            $('#deleteBtn').removeClass('d-none');
            $('#formTitle').text('Редактирование: ' + n.title);
            $('#f_id').val(n.id);
            $('#f_title').val(n.title);
            $('#f_code').val(n.slug); codeChain.setExisting();
            $('#codeChainBtn').removeClass('d-none');
            bindCodeCopyBtn('#codeChainBtn', function () { return n.public_url; });
            $('#f_short_desc').val(n.short_desc); autoHeightResize('#f_short_desc');
            $('#f_status').val(n.status || 'active');
            $('#f_full_desc').val(n.full_desc);
            if ($.fn.trumbowyg) $('#f_full_desc').trumbowyg('html', n.full_desc || '');
            if (newsDatePicker) newsDatePicker.setDate(parseSiteDateTime(n.created_at) || new Date(), true); else $('#f_datetime').val(fmtDateInput(parseSiteDateTime(n.created_at) || new Date()));
            if (n.image) { setBgImage('#previewImg', n.image); previewDataUrl = n.image; }
            if (n.full_image) { setBgImage('#fullImg', n.full_image); fullDataUrl = n.full_image; }
            newsLoaded.resolve(n);
        });
    } else {
        if (newsDatePicker) newsDatePicker.setDate(new Date(), true); else $('#f_datetime').val(fmtDateInput(new Date().toISOString()));
        newsLoaded.resolve(null);
    }

    bindPreviewBtn('#previewBtn', 'news', collectNews);
    $('#saveBtn').on('click', function () { doSave(true); });
    $('#applyBtn').on('click', function () { doSave(false); });
    $('#deleteBtn').on('click', doDelete);
});

/* ---------------- Вкладка «Каталог» ---------------- */

function updateCatalogBadges() {
    var sections = catalogReady ? getCheckboxTreeSelected('#f_catalog_sections').length : 0;
    var products = selectedCatalogProducts.length;
    function badge(sel, n) { $(sel).text(n).toggle(n > 0); }
    badge('#catalogSectionsBadge', sections);
    badge('#catalogProductsBadge', products);
    badge('#catalogCountBadge', sections + products);
}

function renderCatalogProductChips() {
    var $c = $('#catalogProductChips').empty();
    if (!selectedCatalogProducts.length) {
        $c.append('<div class="empty-state py-3"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"></path><path d="M12 22V12"></path><polyline points="3.29 7 12 12 20.71 7"></polyline><path d="m7.5 4.27 9 5.15"></path></svg>Товары не выбраны</div>');
    }
    selectedCatalogProducts.forEach(function (id) {
        var p = catalogProducts.find(function (x) { return x.id === id; });
        $c.append(
            '<div class="variant-item">' +
              (p ? bgThumbHtml(p.preview_image, 'thumb-sm') : '') +
              '<div class="variant-info">' +
                '<div class="variant-name">' + (p ? '<a href="' + CABINET_URL + 'products/edit/' + p.id + '/">' + esc(p.name) + '</a>' : ('#' + id)) + '</div>' +
                (p ? '<div class="text-muted-2 small">' + esc(p.sku || '—') + (p.color ? ' · ' + esc(p.color) : '') + (p.size ? ' · ' + esc(p.size) : '') + '</div>' : '') +
              '</div>' +
              '<button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCatalogProduct(' + id + ')" title="Убрать"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>' +
            '</div>'
        );
    });
    updateCatalogBadges();
}

function removeCatalogProduct(id) {
    selectedCatalogProducts = selectedCatalogProducts.filter(function (x) { return x !== id; });
    renderCatalogProductChips();
}

function addCatalogProduct(id) {
    if (selectedCatalogProducts.indexOf(id) === -1) selectedCatalogProducts.push(id);
    $('#catalogProductSearch').val('');
    $('#catalogProductResults').removeClass('show').empty();
    renderCatalogProductChips();
}

function bindCatalogProductSearch() {
    $('#catalogProductSearch').on('input', debounce(function () {
        var q = $(this).val().trim().toLowerCase();
        var $results = $('#catalogProductResults');
        if (q.length < 3) { $results.removeClass('show').empty(); return; }
        var matches = catalogProducts.filter(function (p) {
            return selectedCatalogProducts.indexOf(p.id) === -1 &&
                (p.name.toLowerCase().indexOf(q) !== -1 || (p.sku || '').toLowerCase().indexOf(q) !== -1);
        }).slice(0, 10);
        if (!matches.length) { $results.html('<div class="gsearch-empty">Ничего не найдено</div>').addClass('show'); return; }
        $results.html(matches.map(function (p) {
            return '<div class="gsearch-item cursor-pointer" onclick="addCatalogProduct(' + p.id + ')"><span>' + esc(p.name) + ' <span class="text-muted-2">· ' + esc(p.sku || '') + '</span></span></div>';
        }).join('')).addClass('show');
    }, 250));
    $(document).on('click.catalogsearch', function (e) {
        if (!$(e.target).closest('#catalogProductSearch, #catalogProductResults').length) $('#catalogProductResults').removeClass('show');
    });
}

/** "dd.mm.yyyy[ hh:mm[:ss]]" — формат, в котором сервер отдаёт ACTIVE_FROM
 *  (сайтовый формат даты Bitrix; при полночи время вообще опускается, см.
 *  NewsRepository::toArray()) — new Date() такую строку не парсит (не
 *  ISO/US-формат, тихо возвращает Invalid Date), из-за чего при открытии
 *  формы редактирования уже сохранённая дата/время не подставлялись в
 *  поле. Возвращает Date или null, если строка вообще не в этом формате. */
function parseSiteDateTime(str) {
    var m = /^(\d{2})\.(\d{2})\.(\d{4})(?:[ T](\d{2}):(\d{2}))?/.exec(str || '');
    if (!m) return null;
    return new Date(+m[3], +m[2] - 1, +m[1], +(m[4] || 0), +(m[5] || 0));
}
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

/** Данные формы в формате сохранения — для doSave() и «Предпросмотра». */
function collectNews() {
    var title = $('#f_title').val().trim();
    var payload = {
        id: newsId || undefined,
        title: title,
        slug: $('#f_code').val().trim() || slugifyClient(title),
        short_desc: $('#f_short_desc').val(),
        full_desc: $.fn.trumbowyg ? $('#f_full_desc').trumbowyg('html') : $('#f_full_desc').val(),
        image: previewDataUrl,
        full_image: fullDataUrl,
        created_at: parseDateInput($('#f_datetime').val()),
        status: $('#f_status').val()
    };
    if (catalogReady) {
        payload.catalog_section_ids = getCheckboxTreeSelected('#f_catalog_sections');
        payload.catalog_product_ids = selectedCatalogProducts.slice();
    }
    return payload;
}

function doSave(goBack) {
    var payload = collectNews();
    var title = payload.title;
    if (!title) { showResult(false, 'Укажите заголовок новости'); return; }
    var wasNew = !newsId;

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
