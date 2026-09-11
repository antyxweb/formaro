/* Порт cabinet-html/assets/js/discount-detail.js.
   Изменения относительно прототипа:
   - id скидки — из ROUTE_ID (путь /discounts/edit/#ID#/), не из ?id=;
   - скидка теперь настоящая строка в БД (HL-блок CabinetDiscounts) —
     сохранение/удаление идёт через dsSaveOne/dsDeleteOne напрямую (сервер
     сам возвращает реальный id), больше не нужно гонять локальный массив
     "всех скидок" и вычитывать/перезаписывать общий discounts.json целиком. */
var CABINET_URL = window.CABINET_BOOTSTRAP.cabinetUrl;

var discountId = 0;
var allCategoriesForTarget = [];
var allProductsForTarget = [];
var selectedProductTargets = [];
var dateFrom = null, dateTo = null;

$(function () {
    discountId = (ROUTE_ID && ROUTE_ID !== 'new') ? parseInt(ROUTE_ID, 10) : 0;

    dateFrom = initSingleDatePicker('#f_date_from');
    dateTo = initSingleDatePicker('#f_date_to');

    $('#f_discount_type').on('change', updateValueUnit);
    $('#f_target_type').on('change', function () { renderTargetPicker([]); });

    dsLoad('categories').done(function (cats) {
        allCategoriesForTarget = visibleCategories(cats);
        dsLoad('products').done(function (prods) {
            allProductsForTarget = ownOnly(prods);

            if (discountId) {
                dsLoad('discounts').done(function (rows) {
                    var d = ownOnly(rows).find(function (x) { return x.id === discountId; });
                    if (!d) { showResult(false, 'Скидка не найдена'); return; }
                    $('#formTitle').text('Редактирование: ' + d.name);
                    $('#f_id').val(d.id);
                    $('#f_name').val(d.name);
                    $('#f_target_type').val(d.target_type);
                    $('#f_discount_type').val(d.discount_type);
                    $('#f_value').val(d.value);
                    $('#f_min_qty').val(d.min_qty || 0);
                    $('#f_min_amount').val(d.min_amount || 0);
                    $('#f_status').val(d.status || 'active');
                    if (d.date_from) dateFrom.setDate(d.date_from, true);
                    if (d.date_to) dateTo.setDate(d.date_to, true);
                    updateValueUnit();
                    renderTargetPicker(d.target_ids || []);
                    $('#deleteBtn').removeClass('d-none');
                });
            } else {
                updateValueUnit();
                // Пришли с детальной товара ("Создать скидку" на вкладке "Скидки") —
                // сразу предзаполняем цель этим товаром
                var prefillProductId = parseInt(getQueryParam('product_id'), 10);
                if (prefillProductId) {
                    $('#f_target_type').val('products');
                    renderTargetPicker([prefillProductId]);
                } else {
                    renderTargetPicker([]);
                }
            }
        });
    });

    $('#saveBtn').on('click', function () { doSave(true); });
    $('#applyBtn').on('click', function () { doSave(false); });
    $('#deleteBtn').on('click', doDelete);
});

function updateValueUnit() {
    $('#f_value_unit').text($('#f_discount_type').val() === 'percent' ? '%' : '₽');
}

function renderTargetPicker(preselectedIds) {
    var type = $('#f_target_type').val();
    var $wrap = $('#targetPickerWrap');
    var $tree = $('#f_target_tree');

    if (type === 'all') {
        $wrap.hide();
        return;
    }
    $wrap.show();
    $tree.empty();
    $tree.prev('.cbtree-search').remove();

    if (type === 'category') {
        $('#targetPickerLabel').text('Выберите категории');
        renderCategoryCheckboxTree('#f_target_tree', allCategoriesForTarget, preselectedIds, []);
    } else {
        $tree.removeClass('cbtree');
        $('#targetPickerLabel').text('Выберите товары');
        selectedProductTargets = preselectedIds.slice();
        $tree.html(
            '<div id="targetProductChips" class="mb-2"></div>' +
            '<div class="position-relative">' +
              '<input type="text" class="form-control" id="targetProductSearch" placeholder="Начните вводить название или артикул (от 3 символов)…" autocomplete="off">' +
              '<div class="gsearch-dropdown" id="targetProductResults"></div>' +
            '</div>'
        );
        renderProductChips();
        $('#targetProductSearch').on('input', debounce(function () {
            var q = $(this).val().trim();
            if (q.length < 3) { $('#targetProductResults').removeClass('show').empty(); return; }
            searchProductsForTarget(q);
        }, 250));
        $(document).on('click.targetsearch', function (e) {
            if (!$(e.target).closest('#targetProductSearch, #targetProductResults').length) $('#targetProductResults').removeClass('show');
        });
    }
}

function renderProductChips() {
    var $c = $('#targetProductChips').empty();
    if (!selectedProductTargets.length) { $c.append('<div class="empty-state py-3"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"></path><path d="M12 22V12"></path><polyline points="3.29 7 12 12 20.71 7"></polyline><path d="m7.5 4.27 9 5.15"></path></svg>Товары не выбраны</div>'); return; }
    selectedProductTargets.forEach(function (id) {
        var p = allProductsForTarget.find(function (x) { return x.id === id; });
        $c.append(
            '<div class="variant-item">' +
              (p ? bgThumbHtml(p.preview_image, 'thumb-sm') : '') +
              '<div class="variant-info">' +
                '<div class="variant-name">' + (p ? '<a href="' + CABINET_URL + 'products/edit/' + p.id + '/">' + esc(p.name) + '</a>' : ('#' + id)) + '</div>' +
                (p ? '<div class="text-muted-2 small">' + esc(p.sku || '—') + (p.color ? ' · ' + esc(p.color) : '') + (p.size ? ' · ' + esc(p.size) : '') + '</div>' : '') +
              '</div>' +
              '<button type="button" class="btn btn-sm btn-outline-danger" onclick="removeProductTarget(' + id + ')" title="Убрать"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>' +
            '</div>'
        );
    });
}
function removeProductTarget(id) {
    selectedProductTargets = selectedProductTargets.filter(function (x) { return x !== id; });
    renderProductChips();
}
function searchProductsForTarget(q) {
    var $results = $('#targetProductResults');
    var ql = q.toLowerCase();
    var matches = allProductsForTarget.filter(function (p) {
        return selectedProductTargets.indexOf(p.id) === -1 &&
            (p.name.toLowerCase().indexOf(ql) !== -1 || (p.sku || '').toLowerCase().indexOf(ql) !== -1);
    }).slice(0, 10);
    if (!matches.length) { $results.html('<div class="gsearch-empty">Ничего не найдено</div>').addClass('show'); return; }
    var html = matches.map(function (p) {
        return '<div class="gsearch-item cursor-pointer" onclick="addProductTarget(' + p.id + ')"><span>' + esc(p.name) + ' <span class="text-muted-2">· ' + esc(p.sku || '') + '</span></span></div>';
    }).join('');
    $results.html(html).addClass('show');
}
function addProductTarget(id) {
    if (selectedProductTargets.indexOf(id) === -1) selectedProductTargets.push(id);
    $('#targetProductSearch').val('');
    $('#targetProductResults').removeClass('show').empty();
    renderProductChips();
}

function doSave(goBack) {
    var name = $('#f_name').val().trim();
    if (!name) { showResult(false, 'Укажите название скидки'); return; }

    var targetType = $('#f_target_type').val();
    var targetIds = targetType === 'category' ? getCheckboxTreeSelected('#f_target_tree') :
        (targetType === 'products' ? selectedProductTargets.slice() : []);

    var wasNew = !discountId;
    var payload = {
        id: discountId || undefined,
        name: name,
        target_type: targetType,
        target_ids: targetIds,
        discount_type: $('#f_discount_type').val(),
        value: parseFloat($('#f_value').val()) || 0,
        min_qty: parseInt($('#f_min_qty').val(), 10) || 0,
        min_amount: parseFloat($('#f_min_amount').val()) || 0,
        date_from: $('#f_date_from').val() ? isoFromRuDate($('#f_date_from').val()) : '',
        date_to: $('#f_date_to').val() ? isoFromRuDate($('#f_date_to').val()) : '',
        status: $('#f_status').val()
    };

    dsSaveOne('discounts', payload).done(function (saved) {
        discountId = saved.id;
        $('#f_id').val(discountId);
        $('#formTitle').text('Редактирование: ' + name);
        $('#deleteBtn').removeClass('d-none');
        showResult(true, wasNew ? 'Скидка создана' : 'Скидка сохранена');
        if (goBack) setTimeout(function () { window.location.href = CABINET_URL + 'discounts/'; }, 700);
    });
}

function doDelete() {
    if (!discountId) return;
    var name = $('#f_name').val();
    showConfirm('Удалить скидку «' + name + '»? Это действие необратимо.', function () {
        dsDeleteOne('discounts', discountId).done(function () {
            showResult(true, 'Скидка удалена');
            setTimeout(function () { window.location.href = CABINET_URL + 'discounts/'; }, 700);
        });
    }, {danger: true, okText: 'Удалить'});
}
