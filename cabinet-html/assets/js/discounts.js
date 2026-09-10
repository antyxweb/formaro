var allDiscounts = [];
var allCoupons = [];
var allCategoriesForDiscounts = [];
var allProductsForDiscounts = [];
var dPager = null, cPager = null;
var dRefreshClearBtn = null, cRefreshClearBtn = null;

$(function () {
    loadPartials('discounts', 'Скидки и купоны');

    dPager = initTablePager({root: '#discountsTable', renderFn: renderDiscountsPage, storageKey: 'pagesize_discounts'});
    cPager = initTablePager({root: '#couponsTable', renderFn: renderCouponsPage, storageKey: 'pagesize_coupons'});

    bindSelectAll('#dCheckAll', '.row-check-discount');
    bindSelectAll('#cCheckAll', '.row-check-coupon');

    dsLoad('categories', 'data/categories.json').done(function (cats) {
        allCategoriesForDiscounts = visibleCategories(cats);
        dsLoad('products', 'data/products.json').done(function (prods) {
            allProductsForDiscounts = ownOnly(prods);
            dsLoad('discounts', 'data/discounts.json').done(function (data) {
                allDiscounts = ownOnly(data.discounts || []);
                allCoupons = ownOnly(data.coupons || []);
                renderDiscounts();
                renderCoupons();
            });
        });
    });

    // Если пришли по ссылке "?tab=coupons" (например, из детальной купона) —
    // сразу открываем вкладку "Купоны", а не дефолтную "Скидки"
    if (getQueryParam('tab') === 'coupons') {
        bootstrap.Tab.getOrCreateInstance(document.querySelector('#mainTabs button[data-bs-target="#tabCoupons"]')).show();
    }

    $('#dFindBtn').on('click', renderDiscounts);
    $('#dQInput').on('keypress', function (e) { if (e.which === 13) renderDiscounts(); });
    $('#dTargetFilter, #dStatusFilter').on('change', renderDiscounts);
    dRefreshClearBtn = bindClearFilters('#dClearBtn', '#dQInput, #dTargetFilter, #dStatusFilter', function () {
        $('#dQInput').val(''); $('#dTargetFilter').val(''); $('#dStatusFilter').val('');
        renderDiscounts();
    });

    $('#cFindBtn').on('click', renderCoupons);
    $('#cQInput').on('keypress', function (e) { if (e.which === 13) renderCoupons(); });
    $('#cUsageFilter, #cStatusFilter').on('change', renderCoupons);
    cRefreshClearBtn = bindClearFilters('#cClearBtn', '#cQInput, #cUsageFilter, #cStatusFilter', function () {
        $('#cQInput').val(''); $('#cUsageFilter').val(''); $('#cStatusFilter').val('');
        renderCoupons();
    });

    $(document).on('change', '.row-check-discount', function () {
        $('#dBulkDeleteBtn').toggleClass('d-none', getSelectedIds('.row-check-discount').length === 0);
    });
    $('#dBulkDeleteBtn').on('click', function () {
        var ids = getSelectedIds('.row-check-discount').map(Number);
        if (!ids.length) return;
        showConfirm('Удалить выбранные скидки (' + ids.length + ')?', function () {
            allDiscounts = allDiscounts.filter(function (d) { return ids.indexOf(d.id) === -1; });
            saveDiscountsData(function () { showResult(true, 'Скидки удалены'); renderDiscounts(); });
        }, {danger: true, okText: 'Удалить'});
    });

    $(document).on('change', '.row-check-coupon', function () {
        $('#cBulkDeleteBtn').toggleClass('d-none', getSelectedIds('.row-check-coupon').length === 0);
    });
    $('#cBulkDeleteBtn').on('click', function () {
        var ids = getSelectedIds('.row-check-coupon').map(Number);
        if (!ids.length) return;
        showConfirm('Удалить выбранные купоны (' + ids.length + ')?', function () {
            allCoupons = allCoupons.filter(function (c) { return ids.indexOf(c.id) === -1; });
            saveDiscountsData(function () { showResult(true, 'Купоны удалены'); renderCoupons(); });
        }, {danger: true, okText: 'Удалить'});
    });
});

/** discounts.json хранит ОБЕ сущности одним объектом {discounts, coupons} —
    при сохранении подтягиваем актуальный файл, чтобы не потерять чужие
    партнёрские записи (тот же паттерн, что и для товаров/новостей). */
function saveDiscountsData(cb) {
    dsLoad('discounts', 'data/discounts.json').done(function (full) {
        var foreignDiscounts = (full.discounts || []).filter(function (d) { return d.partner_id !== CURRENT_PARTNER_ID; });
        var foreignCoupons = (full.coupons || []).filter(function (c) { return c.partner_id !== CURRENT_PARTNER_ID; });
        var saved = dsSave('discounts', {
            discounts: foreignDiscounts.concat(allDiscounts),
            coupons: foreignCoupons.concat(allCoupons)
        });
        if (cb) cb(saved);
    });
}
function discountValueLabel(d) { return d.discount_type === 'percent' ? d.value + '%' : fmtMoney(d.value); }
/** Показывает только первое название цели + "+N", если целей несколько —
    иначе длинный список категорий/товаров ломает читаемость строки таблицы. */
function truncatedNamesLabel(names) {
    if (!names.length) return '—';
    return names[0] + (names.length > 1 ? ' +' + (names.length - 1) : '');
}
function discountTargetLabel(d) {
    if (d.target_type === 'all') return 'Все товары';
    if (d.target_type === 'category') {
        var names = (d.target_ids || []).map(function (id) {
            var c = allCategoriesForDiscounts.find(function (x) { return x.id === id; });
            return c ? c.name : null;
        }).filter(Boolean);
        return 'Категории: ' + truncatedNamesLabel(names);
    }
    if (d.target_type === 'products') {
        var pnames = (d.target_ids || []).map(function (id) {
            var p = allProductsForDiscounts.find(function (x) { return x.id === id; });
            return p ? p.name : null;
        }).filter(Boolean);
        return 'Товары: ' + truncatedNamesLabel(pnames);
    }
    return '—';
}
function discountTargetFullList(d) {
    if (d.target_type === 'category') {
        return (d.target_ids || []).map(function (id) {
            var c = allCategoriesForDiscounts.find(function (x) { return x.id === id; });
            return c ? c.name : null;
        }).filter(Boolean).join(', ');
    }
    if (d.target_type === 'products') {
        return (d.target_ids || []).map(function (id) {
            var p = allProductsForDiscounts.find(function (x) { return x.id === id; });
            return p ? p.name : null;
        }).filter(Boolean).join(', ');
    }
    return '';
}
function discountConditionsLabel(d) {
    var parts = [];
    if (d.min_qty) parts.push('От ' + d.min_qty + ' шт.');
    if (d.min_amount) parts.push('От ' + fmtMoney(d.min_amount));
    return parts.length ? parts.join(', ') : '—';
}

function renderDiscounts() {
    if (dRefreshClearBtn) dRefreshClearBtn();
    var q = $('#dQInput').val().trim().toLowerCase();
    var target = $('#dTargetFilter').val();
    var status = $('#dStatusFilter').val();

    var rows = allDiscounts.filter(function (d) {
        if (q && d.name.toLowerCase().indexOf(q) === -1) return false;
        if (target && d.target_type !== target) return false;
        if (status && d.status !== status) return false;
        return true;
    }).sort(function (a, b) { return b.id - a.id; });

    dPager.setRows(rows);
}

function renderDiscountsPage(rows) {
    var $t = $('#discountsBody').empty();
    if (rows.length === 0) {
        $t.html('<tr><td colspan="8"><div class="empty-state"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" x2="5" y1="5" y2="19"></line><circle cx="6.5" cy="6.5" r="2.5"></circle><circle cx="17.5" cy="17.5" r="2.5"></circle></svg>Скидок не найдено.</div></td></tr>');
        $('#dBulkDeleteBtn').addClass('d-none');
        return;
    }
    rows.forEach(function (d) {
        $t.append(
            '<tr>' +
            '<td><input type="checkbox" class="form-check-input row-check-discount" value="' + d.id + '"></td>' +
            '<td><a href="discount-detail.html?id=' + d.id + '">' + esc(d.name) + '</a></td>' +
            '<td class="text-muted-2 small" title="' + esc(discountTargetFullList(d)) + '">' + esc(discountTargetLabel(d)) + '</td>' +
            '<td>' + esc(discountValueLabel(d)) + '</td>' +
            '<td class="text-muted-2 small">' + esc(discountConditionsLabel(d)) + '</td>' +
            '<td class="text-muted-2 small">' + fmtDateShort(d.date_from) + ' – ' + fmtDateShort(d.date_to) + '</td>' +
            '<td>' + statusPill(d.status) + '</td>' +
            '<td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="discount-detail.html?id=' + d.id + '" title="Редактировать"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg></a> ' +
            '<button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteDiscount(' + d.id + ')" title="Удалить"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button></td>' +
            '</tr>'
        );
    });
}

function deleteDiscount(id) {
    var d = allDiscounts.find(function (x) { return x.id === id; });
    showConfirm('Удалить скидку «' + (d ? d.name : '') + '»?', function () {
        allDiscounts = allDiscounts.filter(function (x) { return x.id !== id; });
        saveDiscountsData(function () { showResult(true, 'Скидка удалена'); renderDiscounts(); });
    }, {danger: true, okText: 'Удалить'});
}

function renderCoupons() {
    if (cRefreshClearBtn) cRefreshClearBtn();
    var q = $('#cQInput').val().trim().toLowerCase();
    var usage = $('#cUsageFilter').val();
    var status = $('#cStatusFilter').val();

    var rows = allCoupons.filter(function (c) {
        if (q && c.code.toLowerCase().indexOf(q) === -1) return false;
        if (usage && c.usage_type !== usage) return false;
        if (status && c.status !== status) return false;
        return true;
    }).sort(function (a, b) { return b.id - a.id; });

    cPager.setRows(rows);
}

function renderCouponsPage(rows) {
    var $t = $('#couponsBody').empty();
    if (rows.length === 0) {
        $t.html('<tr><td colspan="8"><div class="empty-state"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" x2="5" y1="5" y2="19"></line><circle cx="6.5" cy="6.5" r="2.5"></circle><circle cx="17.5" cy="17.5" r="2.5"></circle></svg>Купонов не найдено.</div></td></tr>');
        $('#cBulkDeleteBtn').addClass('d-none');
        return;
    }
    rows.forEach(function (c) {
        $t.append(
            '<tr>' +
            '<td><input type="checkbox" class="form-check-input row-check-coupon" value="' + c.id + '"></td>' +
            '<td><a href="coupon-detail.html?id=' + c.id + '" class="font-monospace">' + esc(c.code) + '</a></td>' +
            '<td>' + esc(discountValueLabel(c)) + '</td>' +
            '<td>' + (c.usage_type === 'single' ? 'Одноразовый' : 'Многоразовый') + '</td>' +
            '<td class="text-muted-2 small">' + (c.used_count || 0) + '</td>' +
            '<td class="text-muted-2 small">' + fmtDateShort(c.date_from) + ' – ' + fmtDateShort(c.date_to) + '</td>' +
            '<td>' + statusPill(c.status) + '</td>' +
            '<td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="coupon-detail.html?id=' + c.id + '" title="Редактировать"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"></path><path d="m15 5 4 4"></path></svg></a> ' +
            '<button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteCoupon(' + c.id + ')" title="Удалить"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button></td>' +
            '</tr>'
        );
    });
}

function deleteCoupon(id) {
    var c = allCoupons.find(function (x) { return x.id === id; });
    showConfirm('Удалить купон «' + (c ? c.code : '') + '»?', function () {
        allCoupons = allCoupons.filter(function (x) { return x.id !== id; });
        saveDiscountsData(function () { showResult(true, 'Купон удалён'); renderCoupons(); });
    }, {danger: true, okText: 'Удалить'});
}
