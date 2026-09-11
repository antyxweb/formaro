/* Порт cabinet-html/assets/js/products.js — ссылки на *.html заменены на
   SEF-роуты компонента (CABINET_BOOTSTRAP.cabinetUrl), "Предпросмотр"
   отключён (публичной витрины пока нет). Остальная логика без изменений. */
var CABINET_URL = window.CABINET_BOOTSTRAP.cabinetUrl;

var allProducts = [];
var allCategoriesForFilter = [];
var refreshClearBtn = null;
var pager = null;

$(function () {
    bindSelectAll('#checkAll', '.row-check-prod');
    pager = initTablePager({root: '#productsTable', renderFn: renderPage, storageKey: 'pagesize_products'});

    $('#findBtn').on('click', renderProducts);
    $('#qInput').on('keypress', function (e) { if (e.which === 13) renderProducts(); });
    $('#catFilter, #statusFilter').on('change', renderProducts);
    refreshClearBtn = bindClearFilters('#clearBtn', '#qInput, #catFilter, #statusFilter', function () {
        $('#qInput').val(''); $('#catFilter').val('0'); $('#statusFilter').val('');
        renderProducts();
    });

    dsLoad('categories').done(function (cats) {
        allCategoriesForFilter = visibleCategories(cats);
        fillCategoryFilter();
        var catIdParam = getQueryParam('category_id');
        if (catIdParam) { $('#catFilter').val(catIdParam); if (refreshClearBtn) refreshClearBtn(); }
        dsLoad('products').done(function (prods) {
            allProducts = ownOnly(prods);
            renderProducts();
        });
    });

    $(document).on('change', '.row-check-prod', function () {
        $('#bulkDeleteBtn').toggleClass('d-none', getSelectedIds('.row-check-prod').length === 0);
    });
    $('#bulkDeleteBtn').on('click', function () {
        var ids = getSelectedIds('.row-check-prod').map(Number);
        if (!ids.length) return;
        showConfirm('Удалить выбранные товары (' + ids.length + ')?', function () {
            allProducts = allProducts.filter(function (p) { return ids.indexOf(p.id) === -1; });
            saveOwnRows('products', 'data/products.json', allProducts, function () {
                showResult(true, 'Товары удалены');
                renderProducts();
            });
        }, {danger: true, okText: 'Удалить'});
    });
});

function fillCategoryFilter() {
    var $sel = $('#catFilter');
    buildCategoryTreeOptions(allCategoriesForFilter, []).forEach(function (o) {
        $sel.append('<option value="' + o.id + '">' + esc(o.label) + '</option>');
    });
}
function categoryPath(catIds) {
    if (!catIds || !catIds.length) return '—';
    return catIds.map(function (id) { return categoryBreadcrumb(allCategoriesForFilter, id); }).join('; ');
}

function renderProducts() {
    if (refreshClearBtn) refreshClearBtn();
    var q = $('#qInput').val().trim().toLowerCase();
    var catId = parseInt($('#catFilter').val(), 10) || 0;
    var status = $('#statusFilter').val();

    var rows = allProducts.filter(function (p) {
        var catIds = p.category_ids || [];
        if (catId) {
            var matchIds = [catId].concat(categoryDescendantIds(allCategoriesForFilter, catId));
            if (!catIds.some(function (id) { return matchIds.indexOf(id) !== -1; })) return false;
        }
        if (status && p.status !== status) return false;
        if (q && (p.name + ' ' + p.sku).toLowerCase().indexOf(q) === -1) return false;
        return true;
    }).sort(function (a, b) { return b.id - a.id; });

    pager.setRows(rows);
}

function renderPage(rows) {
    var $t = $('#productsBody').empty();
    if (rows.length === 0) {
        $t.html('<tr><td colspan="8"><div class="empty-state"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"></path><path d="M12 22V12"></path><polyline points="3.29 7 12 12 20.71 7"></polyline><path d="m7.5 4.27 9 5.15"></path></svg>Товаров не найдено.</div></td></tr>');
        $('#bulkDeleteBtn').addClass('d-none');
        return;
    }
    rows.forEach(function (p) {
        $t.append(
            '<tr>' +
            '<td><input type="checkbox" class="form-check-input row-check-prod" value="' + p.id + '"></td>' +
            '<td><a href="' + CABINET_URL + 'products/edit/' + p.id + '/">' + esc(p.name) + '</a></td>' +
            '<td class="text-muted-2 small">' + esc(p.sku || '—') + '</td>' +
            '<td class="text-muted-2 small">' + esc(categoryPath(p.category_ids)) + '</td>' +
            '<td>' + fmtMoney(p.price) + '</td>' +
            '<td' + (p.stock === 0 ? ' class="text-red"' : '') + '>' + (p.stock || 0) + '</td>' +
            '<td>' + statusPill(p.status) + '</td>' +
            '<td class="text-end">' +
              '<a class="btn btn-sm btn-outline-secondary" href="' + CABINET_URL + 'products/edit/' + p.id + '/" title="Редактировать"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg></a> ' +
              '<button class="btn btn-sm btn-outline-danger" onclick="deleteProduct(' + p.id + ')" title="Удалить"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>' +
            '</td>' +
            '</tr>'
        );
    });
}

function deleteProduct(id) {
    var p = allProducts.find(function (x) { return x.id === id; });
    showConfirm('Удалить товар «' + (p ? p.name : '') + '»?', function () {
        allProducts = allProducts.filter(function (x) { return x.id !== id; });
        saveOwnRows('products', 'data/products.json', allProducts, function () {
            showResult(true, 'Товар удалён');
            renderProducts();
        });
    }, {danger: true, okText: 'Удалить'});
}
