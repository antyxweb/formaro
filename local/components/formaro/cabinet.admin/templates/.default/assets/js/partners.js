/* Список партнёров в кабинете администратора: проверка, показ на сайте,
   изменения на проверке; клик — карточка модерации. */
var allPartners = [];
var pager = null;
var refreshClearBtn = null;

$(function () {
    var statusParam = getQueryParam('status');
    if (statusParam) $('#statusFilter').val(statusParam);
    pager = initTablePager({root: '#partnersTable', renderFn: renderPage, storageKey: 'admin_pagesize_partners'});
    dsLoad('partners').done(function (rows) { allPartners = rows; render(); });

    $('#qInput').on('input', debounce(render, 200));
    $('#statusFilter, #activeFilter').on('change', render);
    refreshClearBtn = bindClearFilters('#clearBtn', '#qInput, #statusFilter, #activeFilter', function () {
        $('#qInput, #statusFilter, #activeFilter').val('');
        render();
    });
});

function render() {
    if (refreshClearBtn) refreshClearBtn();
    var q = $('#qInput').val().trim().toLowerCase();
    var status = $('#statusFilter').val();
    var active = $('#activeFilter').val();
    pager.setRows(allPartners.filter(function (p) {
        if (q && (p.name + ' ' + p.name_full + ' ' + p.inn).toLowerCase().indexOf(q) === -1) return false;
        if (status === 'moderation' && !partnerNeedsModeration(p)) return false;
        if (status && status !== 'moderation' && p.verification_status !== status) return false;
        if (active && (active === 'Y') !== p.active) return false;
        return true;
    }));
}

function verificationPill(p) {
    var html = partnerStatusPill(p.verification_status);
    if (p.has_pending && p.verification_status !== 'pending') html += ' <span class="pill pill-yellow" title="Партнёр прислал изменения">Изменения</span>';
    return html;
}

function renderPage(rows) {
    var $t = $('#partnersBody').empty();
    if (!rows.length) { $t.html(emptyRow(6, 'Партнёров не найдено.')); return; }
    rows.forEach(function (p) {
        var logo = p.logo ? bgThumbHtml(p.logo, 'avatar-sm') : '<div class="avatar-sm avatar-initials">' + esc(p.name.slice(0, 2).toUpperCase()) + '</div>';
        $t.append(
            '<tr class="cursor-pointer" onclick="location.href=\'' + CABINET_URL + 'partners/edit/' + p.id + '/\'">' +
            '<td><div class="d-flex align-items-center gap-2">' + logo + '<div><div class="fw-semibold">' + esc(p.name) + '</div>' +
                (p.name_full !== p.name ? '<div class="text-muted-2 small">' + esc(p.name_full) + '</div>' : '') + '</div></div></td>' +
            '<td class="small">' + esc(p.inn || '—') + '</td>' +
            '<td>' + verificationPill(p) + '</td>' +
            '<td>' + (p.active ? '<span class="pill pill-green">Показывается</span>' : '<span class="pill pill-gray">Скрыт</span>') + '</td>' +
            '<td class="small text-muted-2">' + (p.created_at ? fmtDate(p.created_at, false) : '—') + '</td>' +
            '<td class="text-end text-nowrap">' +
                (p.active ? '<a class="btn btn-sm btn-outline-secondary" href="' + p.public_url + '" target="_blank" onclick="event.stopPropagation();" title="Страница на сайте"><i class="bi bi-box-arrow-up-right"></i></a> ' : '') +
                '<a class="btn btn-sm btn-outline-primary" href="' + CABINET_URL + 'partners/edit/' + p.id + '/" onclick="event.stopPropagation();">Карточка</a>' +
            '</td>' +
            '</tr>'
        );
    });
}
