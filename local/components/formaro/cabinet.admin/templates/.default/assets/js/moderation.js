/* Очередь модерации: категории партнёров на проверке (допустить / отклонить
   прямо в списке) и партнёры, ждущие проверки (переход в карточку). */
var allCategories = [];
var allPartners = [];

$(function () {
    fillPartnerSelect('#partnerFilter');
    $('#partnerFilter').on('change', render);
    if (getQueryParam('tab') === 'partners') bootstrap.Tab.getOrCreateInstance($('[data-bs-target="#tabPartners"]')[0]).show();
    load();
});

function load() {
    $.when(dsLoad('categories'), dsLoad('partners')).done(function (cats, partners) {
        allCategories = cats;
        allPartners = partners;
        render();
        refreshSidebarCounts();
    });
}

function render() {
    var partner = $('#partnerFilter').val();
    var cats = allCategories.filter(function (c) { return categoryNeedsModeration(c) && matchPartner(c.partner_id, partner); });
    var partners = allPartners.filter(function (p) { return partnerNeedsModeration(p) && matchPartner(p.id, partner); });
    $('#countCategories').text(cats.length).toggle(cats.length > 0);
    $('#countPartners').text(partners.length).toggle(partners.length > 0);

    var $c = $('#categoriesBody').empty();
    if (!cats.length) $c.html(emptyRow(5, 'Категорий на проверке нет.'));
    cats.forEach(function (c) {
        $c.append('<tr>' +
            '<td><a href="' + CABINET_URL + 'categories/edit/' + c.id + '/">' + esc(c.name) + '</a></td>' +
            '<td class="small text-muted-2">' + esc(c.parent_id ? categoryBreadcrumb(allCategories, c.parent_id) : '—') + '</td>' +
            '<td>' + partnerCell(c.partner_id) + '</td>' +
            '<td class="small"><span class="truncate-cell" title="' + esc(c.short_desc || '') + '">' + esc(c.short_desc || '—') + '</span></td>' +
            '<td class="text-end text-nowrap">' +
                '<button type="button" class="btn btn-sm btn-success" onclick="approveCategory(' + c.id + ')"><i class="bi bi-check-lg"></i> Допустить</button> ' +
                '<button type="button" class="btn btn-sm btn-outline-danger" onclick="rejectCategory(' + c.id + ')"><i class="bi bi-slash-circle"></i> Отклонить</button>' +
            '</td></tr>');
    });

    var $p = $('#partnersBody').empty();
    if (!partners.length) $p.html(emptyRow(4, 'Партнёров, ждущих проверки, нет.'));
    partners.forEach(function (p) {
        var what = [];
        if (p.verification_status === 'pending' || p.has_pending) what.push('Данные компании отправлены на проверку');
        if (!p.active && p.verification_status === 'not_verified') what.push('Новый партнёр — проверить и включить показ на сайте');
        $p.append('<tr class="cursor-pointer" onclick="location.href=\'' + CABINET_URL + 'partners/edit/' + p.id + '/\'">' +
            '<td><div class="fw-semibold">' + esc(p.name) + '</div><div class="small text-muted-2">' + esc(p.name_full !== p.name ? p.name_full : '') + '</div></td>' +
            '<td class="small">' + esc(p.inn || '—') + '</td>' +
            '<td class="small">' + partnerStatusPill(p.verification_status) + ' ' + esc(what.join('; ')) + '</td>' +
            '<td class="text-end"><a class="btn btn-sm btn-outline-primary" href="' + CABINET_URL + 'partners/edit/' + p.id + '/">Проверить</a></td>' +
            '</tr>');
    });
}

function approveCategory(id) {
    cabinetAjax({ajax_action: 'approve_category', id: id}).done(function () {
        showResult(true, 'Категория допущена к показу');
        load();
    });
}

function rejectCategory(id) {
    adminAskReason({title: 'Отклонить категорию'}, function (reason) {
        cabinetAjax({ajax_action: 'reject_category', id: id, reason: reason}).done(function () {
            showResult(true, 'Категория отклонена, партнёр получил уведомление');
            load();
        });
    });
}
