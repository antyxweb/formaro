var allNews = [];
var refreshClearBtn = null;
var pager = null;

$(function () {
    loadPartials('news', 'Новости');
    bindSelectAll('#checkAll', '.row-check-news');
    pager = initTablePager({root: '#newsTable', renderFn: renderPage, storageKey: 'pagesize_news'});

    dsLoad('news', 'data/news.json').done(function (rows) {
        allNews = ownOnly(rows);
        renderNews();
    });

    $('#findBtn').on('click', renderNews);
    $('#qInput').on('keypress', function (e) { if (e.which === 13) renderNews(); });
    $('#statusFilter').on('change', renderNews);
    refreshClearBtn = bindClearFilters('#clearBtn', '#qInput, #statusFilter', function () { $('#qInput').val(''); $('#statusFilter').val(''); renderNews(); });
    $(document).on('change', '.row-check-news', function () {
        $('#bulkDeleteBtn').toggleClass('d-none', getSelectedIds('.row-check-news').length === 0);
    });
    $('#bulkDeleteBtn').on('click', function () {
        var ids = getSelectedIds('.row-check-news').map(Number);
        if (!ids.length) return;
        showConfirm('Удалить выбранные новости (' + ids.length + ')?', function () {
            allNews = allNews.filter(function (n) { return ids.indexOf(n.id) === -1; });
            saveOwnRows('news', 'data/news.json', allNews, function () {
                showResult(true, 'Новости удалены');
                renderNews();
            });
        }, {danger: true, okText: 'Удалить'});
    });
});

function renderNews() {
    if (refreshClearBtn) refreshClearBtn();
    var q = $('#qInput').val().trim().toLowerCase();
    var status = $('#statusFilter').val();
    var rows = allNews.filter(function (n) {
        if (status && n.status !== status) return false;
        return !q || n.title.toLowerCase().indexOf(q) !== -1;
    }).sort(function (a, b) { return new Date(b.created_at) - new Date(a.created_at); });
    pager.setRows(rows);
}

function renderPage(rows) {
    var $t = $('#newsBody').empty();
    if (rows.length === 0) {
        $t.html('<tr><td colspan="5"><div class="empty-state"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18h-5"></path><path d="M18 14h-8"></path><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-4 0v-9a2 2 0 0 1 2-2h2"></path><rect width="8" height="4" x="10" y="6" rx="1"></rect></svg>Новостей не найдено.</div></td></tr>');
        $('#bulkDeleteBtn').addClass('d-none');
        return;
    }
    rows.forEach(function (n) {
        $t.append(
            '<tr>' +
            '<td><input type="checkbox" class="form-check-input row-check-news" value="' + n.id + '"></td>' +
            '<td><a href="news-detail.html?id=' + n.id + '">' + esc(n.title) + '</a></td>' +
            '<td>' + statusPill(n.status || 'active') + '</td>' +
            '<td class="text-muted-2 small">' + fmtDate(n.created_at) + '</td>' +
            '<td class="text-end">' +
              '<a class="btn btn-sm btn-outline-secondary" href="news-detail.html?id=' + n.id + '" title="Редактировать"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg></a> ' +
              '<a class="btn btn-sm btn-outline-secondary" href="preview.html?type=news&id=' + n.id + '" target="_blank" title="Предпросмотр"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"></path><path d="M10 14 21 3"></path><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path></svg></a> ' +
              '<button class="btn btn-sm btn-outline-danger" onclick="deleteNews(' + n.id + ')" title="Удалить"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>' +
            '</td>' +
            '</tr>'
        );
    });
}

function deleteNews(id) {
    var n = allNews.find(function (x) { return x.id === id; });
    showConfirm('Удалить новость «' + (n ? n.title : '') + '»?', function () {
        allNews = allNews.filter(function (x) { return x.id !== id; });
        saveOwnRows('news', 'data/news.json', allNews, function () {
            showResult(true, 'Новость удалена');
            renderNews();
        });
    }, {danger: true, okText: 'Удалить'});
}
