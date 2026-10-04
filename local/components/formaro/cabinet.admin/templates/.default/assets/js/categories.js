/* Категории в кабинете администратора: все категории площадки и партнёров.
   Без фильтров — дерево, с фильтрами — плоский список. Действия:
   редактировать, допустить/отклонить (категории партнёров на проверке),
   удалить (без подкатегорий и товаров). */
var allCategories = [];
var productCounts = {};
var pager = null;
var refreshClearBtn = null;

var ICONS = {
    edit: '<svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg>',
    approve: '<svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>',
    reject: '<svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="m4.9 4.9 14.2 14.2"></path></svg>',
    trash: '<svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>'
};

$(function () {
    fillPartnerSelect('#partnerFilter', {allLabel: 'Все', siteLabel: 'Площадка (общие)'});
    var statusParam = getQueryParam('status');
    if (statusParam) $('#statusFilter').val(statusParam);
    pager = initTablePager({root: '#categoriesTable', renderFn: renderPage, storageKey: 'admin_pagesize_categories'});

    $.when(dsLoad('categories'), dsLoad('products')).done(function (cats, products) {
        allCategories = cats;
        products.forEach(function (p) {
            (p.category_ids || []).forEach(function (id) { productCounts[id] = (productCounts[id] || 0) + 1; });
        });
        render();
    });

    $('#findBtn').on('click', render);
    $('#qInput').on('keypress', function (e) { if (e.which === 13) render(); });
    $('#partnerFilter, #statusFilter').on('change', render);
    refreshClearBtn = bindClearFilters('#clearBtn', '#qInput, #partnerFilter, #statusFilter', function () {
        $('#qInput').val(''); $('#partnerFilter').val(''); $('#statusFilter').val('');
        render();
    });
});

function flattenTree(rows) {
    var out = [];
    (function walk(parentId, depth) {
        if (depth > 12) return;
        categoryDirectChildren(rows, parentId).sort(categorySortCompare).forEach(function (c) {
            out.push(Object.assign({}, c, {depth: depth}));
            walk(c.id, depth + 1);
        });
    })(0, 0);
    return out;
}

function render() {
    if (refreshClearBtn) refreshClearBtn();
    var q = $('#qInput').val().trim().toLowerCase();
    var partner = $('#partnerFilter').val();
    var status = $('#statusFilter').val();
    var filtered = q || partner !== '' || status;

    var rows = filtered
        ? allCategories.filter(function (c) {
            if (q && c.name.toLowerCase().indexOf(q) === -1) return false;
            if (!matchPartner(c.partner_id, partner)) return false;
            if (status && c.status !== status) return false;
            return true;
        }).map(function (c) { return Object.assign({}, c, {depth: 0}); }).sort(categorySortCompare)
        : flattenTree(allCategories);
    pager.setRows(rows);
}

function renderPage(rows) {
    var $t = $('#categoriesBody').empty();
    if (!rows.length) { $t.html(emptyRow(6, 'Категорий не найдено.')); return; }
    var chevron = '<svg class="ic-inline cat-indent-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"></path></svg>';
    rows.forEach(function (c) {
        var indent = c.depth > 0 ? '<span class="cat-indent">' + Array(c.depth + 1).join(chevron) + '</span>' : '';
        var actions = '<a class="btn btn-sm btn-outline-secondary" href="' + CABINET_URL + 'categories/edit/' + c.id + '/" title="Редактировать">' + ICONS.edit + '</a> ';
        if (categoryNeedsModeration(c) || c.status === 'rejected') {
            actions += '<button type="button" class="btn btn-sm btn-outline-success" onclick="approveCategory(' + c.id + ')" title="Допустить к показу">' + ICONS.approve + '</button> ';
        }
        if (categoryNeedsModeration(c)) {
            actions += '<button type="button" class="btn btn-sm btn-outline-warning" onclick="rejectCategory(' + c.id + ')" title="Отклонить">' + ICONS.reject + '</button> ';
        }
        actions += '<button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteCategory(' + c.id + ')" title="Удалить">' + ICONS.trash + '</button>';
        $t.append(
            '<tr>' +
            '<td>' + indent + '<a href="' + CABINET_URL + 'categories/edit/' + c.id + '/">' + esc(c.name) + '</a></td>' +
            '<td class="text-muted-2 small">' + esc(c.parent_id ? categoryBreadcrumb(allCategories, c.parent_id) : '—') + '</td>' +
            '<td>' + partnerCell(c.partner_id, 'Площадка') + '</td>' +
            '<td>' + statusPill(c.status || 'active') + (c.reject_reason ? ' <i class="bi bi-info-circle text-muted-2" title="' + esc(c.reject_reason) + '"></i>' : '') + '</td>' +
            '<td>' + (productCounts[c.id] || 0) + '</td>' +
            '<td class="text-end text-nowrap">' + actions + '</td>' +
            '</tr>'
        );
    });
}

function replaceCategory(saved) {
    allCategories = allCategories.map(function (c) { return c.id === saved.id ? saved : c; });
    render();
    refreshSidebarCounts();
}

function approveCategory(id) {
    var c = allCategories.find(function (x) { return x.id === id; });
    showConfirm('Допустить категорию «' + (c ? c.name : '') + '» к показу на сайте?', function () {
        cabinetAjax({ajax_action: 'approve_category', id: id}).done(function (saved) {
            replaceCategory(saved);
            showResult(true, 'Категория допущена к показу');
        });
    }, {okText: 'Допустить'});
}

function rejectCategory(id) {
    adminAskReason({title: 'Отклонить категорию'}, function (reason) {
        cabinetAjax({ajax_action: 'reject_category', id: id, reason: reason}).done(function (saved) {
            replaceCategory(saved);
            showResult(true, 'Категория отклонена, партнёр получил уведомление');
        });
    });
}

function deleteCategory(id) {
    var c = allCategories.find(function (x) { return x.id === id; });
    if (categoryDirectChildren(allCategories, id).length || productCounts[id]) {
        showResult(false, 'Нельзя удалить категорию «' + (c ? c.name : '') + '» — в ней есть подкатегории или товары.');
        return;
    }
    showConfirm('Удалить категорию «' + (c ? c.name : '') + '»? Это действие необратимо.', function () {
        dsDeleteOne('categories', id).done(function () {
            allCategories = allCategories.filter(function (x) { return x.id !== id; });
            render();
            showResult(true, 'Категория удалена');
        });
    }, {danger: true, okText: 'Удалить'});
}
