var allCategories = [];
var allProductsForCount = [];
var refreshClearBtn = null;
var pager = null;
var onlyMine = false;

$(function () {
    loadPartials('categories', 'Категории');
    bindSelectAll('#checkAll', '.row-check-cat');
    pager = initTablePager({root: '#categoriesTable', renderFn: renderPage, storageKey: 'pagesize_categories'});

    dsLoad('categories', 'data/categories.json').done(function (cats) {
        // видим системные категории (общие для всех) + свои; чужие партнёрские — скрыты
        allCategories = visibleCategories(cats);
        dsLoad('products', 'data/products.json').done(function (prods) {
            allProductsForCount = ownOnly(prods);
            renderCategories();
        });
    });

    $('#findBtn').on('click', renderCategories);
    $('#qInput').on('keypress', function (e) { if (e.which === 13) renderCategories(); });
    refreshClearBtn = bindClearFilters('#clearBtn', '#qInput', function () { $('#qInput').val(''); renderCategories(); });

    $('#onlyMineToggle').on('change', function () {
        onlyMine = $(this).is(':checked');
        renderCategories();
    });

    $(document).on('change', '.row-check-cat', function () {
        $('#bulkDeleteBtn').toggleClass('d-none', getSelectedIds('.row-check-cat').length === 0);
    });
    $('#bulkDeleteBtn').on('click', function () {
        var ids = getSelectedIds('.row-check-cat').map(Number);
        if (!ids.length) return;
        showConfirm('Удалить выбранные категории (' + ids.length + ')? Системные категории, категории с товарами или подкатегориями будут пропущены.', function () {
            var blocked = 0;
            ids.forEach(function (id) {
                if (canDeleteCategory(id)) {
                    allCategories = allCategories.filter(function (c) { return c.id !== id; });
                } else {
                    blocked++;
                }
            });
            saveVisibleCategories(allCategories);
            showResult(blocked === 0, blocked === 0 ? 'Категории удалены' : (ids.length - blocked) + ' удалено, ' + blocked + ' пропущено (системные/есть товары или подкатегории)');
            renderCategories();
        }, {danger: true, okText: 'Удалить'});
    });
});

function productsCount(catId) { return allProductsForCount.filter(function (p) { return (p.category_ids || []).indexOf(catId) !== -1; }).length; }
function canDeleteCategory(id) {
    var c = allCategories.find(function (x) { return x.id === id; });
    if (!c || !canEditCategory(c)) return false;
    return productsCount(id) === 0 && categoryDirectChildren(allCategories, id).length === 0;
}

/** Дерево категорий в плоском списке "сверху вниз" (родитель сразу перед
    своими подкатегориями), в порядке как на сайте (sort_order), с отступом
    по названию — как в остальных выпадающих списках категорий в приложении. */
function flattenCategoryTree() {
    var out = [];
    function walk(parentId, depth) {
        if (depth > 12) return; // защита от случайных циклов в данных
        categoryDirectChildren(allCategories, parentId)
            .sort(categorySortCompare)
            .forEach(function (c) {
                out.push(Object.assign({}, c, {depth: depth}));
                walk(c.id, depth + 1);
            });
    }
    walk(0, 0);
    return out;
}

function renderCategories() {
    if (refreshClearBtn) refreshClearBtn();
    var q = $('#qInput').val().trim().toLowerCase();

    var rows;
    if (q) {
        rows = allCategories.filter(function (c) { return c.name.toLowerCase().indexOf(q) !== -1; })
            .map(function (c) { return Object.assign({}, c, {depth: 0}); })
            .sort(categorySortCompare);
    } else {
        rows = flattenCategoryTree();
    }
    if (onlyMine) rows = rows.filter(function (c) { return !isSystemCategory(c); });
    pager.setRows(rows);
}

function renderPage(rows) {
    var $t = $('#categoriesBody').empty();
    if (rows.length === 0) {
        $t.html('<tr><td colspan="6"><div class="empty-state"><i class="bi bi-diagram-3"></i>Категорий не найдено.</div></td></tr>');
        $('#bulkDeleteBtn').addClass('d-none');
        return;
    }
    rows.forEach(function (c) {
        var CHEVRON = '<svg class="ic-inline cat-indent-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"></path></svg>';
        var indent = c.depth > 0 ? '<span class="cat-indent">' + Array(c.depth + 1).join(CHEVRON) + '</span>' : '';
        var parentLabel = c.parent_id ? categoryBreadcrumb(allCategories, c.parent_id) : '—';
        var system = isSystemCategory(c);
        var nameCell = system
            ? indent + '<span class="text-muted-2">' + esc(c.name) + '</span>'
            : indent + '<a href="category-detail.html?id=' + c.id + '">' + esc(c.name) + '</a> <span class="pill pill-blue" title="Добавлена вами, видна только вам">Моя</span>';
        var actions = system
            ? '<a class="btn btn-sm btn-outline-secondary" href="products.html?category_id=' + c.id + '" title="Товары в этой категории"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"></path><path d="M12 22V12"></path><polyline points="3.29 7 12 12 20.71 7"></polyline><path d="m7.5 4.27 9 5.15"></path></svg></a>'
            : '<a class="btn btn-sm btn-outline-secondary" href="category-detail.html?id=' + c.id + '" title="Редактировать"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg></a> ' +
              '<a class="btn btn-sm btn-outline-secondary" href="products.html?category_id=' + c.id + '" title="Товары в этой категории"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"></path><path d="M12 22V12"></path><polyline points="3.29 7 12 12 20.71 7"></polyline><path d="m7.5 4.27 9 5.15"></path></svg></a> ' +
              '<button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteCategory(' + c.id + ')" title="Удалить"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>';
        $t.append(
            '<tr>' +
            '<td><input type="checkbox" class="form-check-input row-check-cat" value="' + c.id + '" ' + (system ? 'disabled title="Системную категорию нельзя выбрать для удаления"' : '') + '></td>' +
            '<td>' + nameCell + '</td>' +
            '<td class="text-muted-2 small">' + esc(parentLabel) + '</td>' +
            '<td>' + (system ? '<span class="text-muted-2">—</span>' : statusPill(c.status || 'active')) + '</td>' +
            '<td>' + productsCount(c.id) + '</td>' +
            '<td class="text-end">' + actions + '</td>' +
            '</tr>'
        );
    });
}

function deleteCategory(id) {
    var cat = allCategories.find(function (c) { return c.id === id; });
    if (cat && isSystemCategory(cat)) {
        showResult(false, 'Это системная категория площадки — её нельзя удалить.');
        return;
    }
    if (!canDeleteCategory(id)) {
        showResult(false, 'Нельзя удалить категорию «' + (cat ? cat.name : '') + '» — есть подкатегории или товары.');
        return;
    }
    showConfirm('Удалить категорию «' + (cat ? cat.name : '') + '»?', function () {
        allCategories = allCategories.filter(function (c) { return c.id !== id; });
        saveVisibleCategories(allCategories);
        showResult(true, 'Категория удалена');
        renderCategories();
    }, {danger: true, okText: 'Удалить'});
}
