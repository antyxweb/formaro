/* Новости в кабинете администратора: все новости сайта — площадки и
   партнёров; фильтры по разделу, партнёру, статусу. Удаление — по одной и
   группой (dsDeleteOne по каждой). */
var allNews = [];
var sections = [];
var pager = null;
var refreshClearBtn = null;

$(function () {
    fillPartnerSelect('#partnerFilter', {siteLabel: 'Площадка'});
    bindSelectAll('#checkAll', '.row-check-news');
    pager = initTablePager({root: '#newsTable', renderFn: renderPage, storageKey: 'admin_pagesize_news'});

    $.when(dsLoad('news'), dsLoad('news_sections')).done(function (rows, secs) {
        allNews = rows;
        sections = secs;
        sections.forEach(function (s) { $('#sectionFilter').append('<option value="' + s.id + '">' + esc(s.name) + '</option>'); });
        render();
    });

    $('#findBtn').on('click', render);
    $('#qInput').on('keypress', function (e) { if (e.which === 13) render(); });
    $('#sectionFilter, #partnerFilter, #statusFilter').on('change', render);
    refreshClearBtn = bindClearFilters('#clearBtn', '#qInput, #sectionFilter, #partnerFilter, #statusFilter', function () {
        $('#qInput, #sectionFilter, #partnerFilter, #statusFilter').val('');
        render();
    });
    $(document).on('change', '.row-check-news', function () {
        $('#bulkDeleteBtn').toggleClass('d-none', getSelectedIds('.row-check-news').length === 0);
    });
    $('#bulkDeleteBtn').on('click', function () {
        var ids = getSelectedIds('.row-check-news').map(Number);
        if (!ids.length) return;
        showConfirm('Удалить выбранные новости (' + ids.length + ')?', function () { deleteMany(ids); }, {danger: true, okText: 'Удалить'});
    });
});

/** ACTIVE_FROM приходит в формате сайта «дд.мм.гггг чч:мм:сс». */
function newsTime(n) {
    var m = /^(\d{2})\.(\d{2})\.(\d{4})(?:\s(\d{2}):(\d{2}))?/.exec(n.created_at || '');
    return m ? new Date(+m[3], +m[2] - 1, +m[1], +(m[4] || 0), +(m[5] || 0)).getTime() : 0;
}
function sectionName(id) {
    var s = sections.find(function (x) { return x.id === id; });
    return s ? s.name : '—';
}

function render() {
    if (refreshClearBtn) refreshClearBtn();
    var q = $('#qInput').val().trim().toLowerCase();
    var section = $('#sectionFilter').val();
    var partner = $('#partnerFilter').val();
    var status = $('#statusFilter').val();
    pager.setRows(allNews.filter(function (n) {
        if (q && n.title.toLowerCase().indexOf(q) === -1) return false;
        if (section && n.section_id !== Number(section)) return false;
        if (!matchPartner(n.partner_id, partner)) return false;
        return !status || n.status === status;
    }).sort(function (a, b) { return newsTime(b) - newsTime(a); }));
}

function renderPage(rows) {
    var $t = $('#newsBody').empty();
    $('#checkAll').prop('checked', false);
    $('#bulkDeleteBtn').addClass('d-none');
    if (!rows.length) { $t.html(emptyRow(7, 'Новостей не найдено.')); return; }
    rows.forEach(function (n) {
        var time = newsTime(n);
        $t.append('<tr>' +
            '<td><input type="checkbox" class="form-check-input row-check-news" value="' + n.id + '"></td>' +
            '<td><a href="' + CABINET_URL + 'news/edit/' + n.id + '/">' + esc(n.title) + '</a></td>' +
            '<td class="small">' + esc(sectionName(n.section_id)) + '</td>' +
            '<td>' + partnerCell(n.partner_id, 'Площадка') + '</td>' +
            '<td>' + statusPill(n.status || 'active') + '</td>' +
            '<td class="text-muted-2 small">' + (time ? fmtDate(new Date(time).toISOString(), true) : '—') + '</td>' +
            '<td class="text-end text-nowrap">' +
                (n.public_url && n.status === 'active' ? '<a class="btn btn-sm btn-outline-secondary" href="' + n.public_url + '" target="_blank" title="На сайте"><i class="bi bi-box-arrow-up-right"></i></a> ' : '') +
                '<a class="btn btn-sm btn-outline-secondary" href="' + CABINET_URL + 'news/edit/' + n.id + '/" title="Редактировать"><i class="bi bi-pencil"></i></a> ' +
                '<button class="btn btn-sm btn-outline-danger" onclick="deleteMany([' + n.id + '], true)" title="Удалить"><i class="bi bi-trash"></i></button>' +
            '</td></tr>');
    });
}

function deleteMany(ids, ask) {
    if (ask) {
        var n = allNews.find(function (x) { return x.id === ids[0]; });
        showConfirm('Удалить новость «' + (n ? n.title : '') + '»?', function () { deleteMany(ids, false); }, {danger: true, okText: 'Удалить'});
        return;
    }
    $.when.apply($, ids.map(function (id) { return dsDeleteOne('news', id); })).done(function () {
        allNews = allNews.filter(function (x) { return ids.indexOf(x.id) === -1; });
        render();
        showResult(true, ids.length > 1 ? 'Новости удалены' : 'Новость удалена');
    });
}
