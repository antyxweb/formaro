/* Порт cabinet-html/assets/js/product-detail.js.
   Изменения относительно прототипа:
   - id товара — из ROUTE_ID (путь /products/edit/#ID#/), не из ?id=;
   - справочник цветов теперь из HL-блока ProductColors (entity
     "product_colors" в общем AJAX-контракте), названия доп.свойств —
     статический assets/data/property-names.json (не сущность, обычный файл);
   - "Предпросмотр" и "Создать скидку" отключены в разметке (витрины и
     скидок в этой фазе ещё нет) — ссылки на preview.html/discount-detail.html
     убраны;
   - после сохранения НОВОГО товара подхватываем настоящий id с сервера. */
var CABINET_URL = window.CABINET_BOOTSTRAP.cabinetUrl;

var productId = 0;
var allProducts = [];
var allCategoriesForSelect = [];
var previewDataUrl = '';
var gallery = [];
var props = [];
var customTags = [];
var FIXED_TAGS = ['new', 'bestseller', 'sale'];
var selectedCategoryIds = [];
var allColors = [];
var colorsByName = {};
var ALL_PROPERTY_NAMES = [];

function updateColorSwatch() {
    var hex = colorsByName[$('#f_color').val().trim().toLowerCase()];
    $('#f_color_swatch').css('background-color', hex || '');
}

function renderColorSuggestions() {
    var q = $('#f_color').val().trim().toLowerCase();
    var $dd = $('#colorSuggestions');
    if (!q) { $dd.removeClass('show').empty(); return; }
    var matches = allColors.filter(function (c) {
        return c.name.toLowerCase().indexOf(q) !== -1 || (c.name_en || '').toLowerCase().indexOf(q) !== -1;
    }).slice(0, 20);
    if (!matches.length) { $dd.removeClass('show').empty(); return; }
    var html = matches.map(function (c) {
        return '<div class="gsearch-item cursor-pointer" data-color="' + esc(c.name) + '">' +
            '<span class="color-swatch" style="background-color:' + c.hex + '"></span>' +
            '<span>' + esc(c.name) + (c.name_en ? ' <span class="text-muted-2">· ' + esc(c.name_en) + '</span>' : '') + '</span></div>';
    }).join('');
    $dd.html(html).addClass('show');
}

$(function () {
    productId = (ROUTE_ID && ROUTE_ID !== 'new') ? parseInt(ROUTE_ID, 10) : 0;

    dsLoad('product_colors').done(function (colors) {
        allColors = colors;
        colors.forEach(function (c) { colorsByName[c.name.toLowerCase()] = c.hex; });
        updateColorSwatch();
    });
    $('#f_color').on('input', function () { updateColorSwatch(); renderColorSuggestions(); });
    $('#f_color').on('focus', renderColorSuggestions);
    $('#colorSuggestions').on('click', '.gsearch-item', function () {
        $('#f_color').val($(this).data('color'));
        updateColorSwatch();
        $('#colorSuggestions').removeClass('show').empty();
    });

    $.getJSON(window.CABINET_BOOTSTRAP.assetsUrl + '/data/property-names.json').done(function (names) { ALL_PROPERTY_NAMES = names; });
    $('#propsContainer').on('input focus', '.prop-name', function () { renderPropNameSuggestions($(this)); });
    $('#propsContainer').on('click', '.gsearch-item', function () {
        var $wrap = $(this).closest('.prop-name-wrap');
        $wrap.find('.prop-name').val($(this).data('name'));
        $wrap.find('.prop-name-suggest').removeClass('show').empty();
        syncPropsFromInputs();
    });

    bindImagePreview('#previewFile', '#previewImg', function (url) { previewDataUrl = url; });
    bindImageRemove('#previewRemoveBtn', '#previewImg', 'https://placehold.co/240x180?text=%20', function () { previewDataUrl = ''; });
    initRichText('#f_full_desc');
    bindAutoHeight('#f_short_desc');

    var codeChain = bindCodeChain('#f_name', '#f_code', '#codeChainBtn');

    $('.tag-toggle input[type="checkbox"]').on('change', function () {
        $(this).closest('.tag-toggle').toggleClass('active', this.checked);
    });
    $('#addCustomTagBtn').on('click', addCustomTag);
    $('#customTagInput').on('keypress', function (e) { if (e.which === 13) { e.preventDefault(); addCustomTag(); } });

    $('#addPropBtn').on('click', function () { syncPropsFromInputs(); props.push({name: '', value: ''}); renderProps(); });
    $('#galleryFile').on('change', function () {
        var files = Array.prototype.slice.call(this.files);
        var $input = $(this);
        files.forEach(function (file) {
            var reader = new FileReader();
            reader.onload = function (e) { gallery.push(e.target.result); renderGallery(); };
            reader.readAsDataURL(file);
        });
        $input.val('');
    });

    dsLoad('categories').done(function (cats) {
        allCategoriesForSelect = visibleCategories(cats);
        dsLoad('products').done(function (prods) {
            allProducts = ownOnly(prods);
            if (productId) {
                var p = allProducts.find(function (x) { return x.id === productId; });
                if (!p) { showResult(false, 'Товар не найден'); return; }
                $('#deleteBtn').removeClass('d-none');
                $('#formTitle').text('Редактирование: ' + p.name);
                $('#f_id').val(p.id);
                $('#f_name').val(p.name);
                $('#f_code').val(p.slug); codeChain.setExisting();
                $('#f_price').val(p.price);
                $('#f_preorder').prop('checked', !!p.is_preorder);
                (p.tags || []).forEach(function (t) {
                    if (FIXED_TAGS.indexOf(t) !== -1) {
                        $('#tag_' + t).prop('checked', true).closest('.tag-toggle').addClass('active');
                    } else {
                        customTags.push(t);
                    }
                });
                renderCustomTags();
                $('#f_stock').val(p.stock || 0);
                $('#f_status').val(p.status || 'active');
                $('#f_short_desc').val(p.short_desc); autoHeightResize('#f_short_desc');
                $('#f_full_desc').val(p.full_desc);
                if ($.fn.trumbowyg) $('#f_full_desc').trumbowyg('html', p.full_desc || '');
                $('#f_sku').val(p.sku);
                $('#f_color').val(p.color);
                updateColorSwatch();
                $('#f_size').val(p.size);
                if (p.preview_image) { setBgImage('#previewImg', p.preview_image); previewDataUrl = p.preview_image; }
                gallery = Array.isArray(p.gallery) ? p.gallery.slice() : [];
                props = Array.isArray(p.custom_props) ? p.custom_props.slice() : [];
                selectedCategoryIds = Array.isArray(p.category_ids) ? p.category_ids.slice() : [];
            }
            renderCategoryCheckboxTree('#f_category_tree', allCategoriesForSelect, selectedCategoryIds, []);
            renderGallery();
            renderProps();
            updateVariantTabVisibility();
        });
    });

    $('#saveBtn').on('click', function () { doSave(true); });
    $('#applyBtn').on('click', function () { doSave(false); });
    $('#deleteBtn').on('click', doDelete);

    $('#variantSearch').on('input', debounce(function () {
        var q = $(this).val().trim();
        if (q.length < 3) { $('#variantSearchResults').removeClass('show').empty(); return; }
        searchProductsForVariant(q);
    }, 250));
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#variantSearch, #variantSearchResults').length) $('#variantSearchResults').removeClass('show');
        if (!$(e.target).closest('.color-input-wrap').length) $('#colorSuggestions').removeClass('show');
        if (!$(e.target).closest('.prop-name-wrap').length) $('.prop-name-suggest').removeClass('show').empty();
    });
});

function updateVariantTabVisibility() {
    if (productId) {
        $('#variantNewHint').hide();
        $('#variantEditor').show();
        renderVariantGroup();
    } else {
        $('#variantNewHint').show();
        $('#variantEditor').hide();
    }
    updateProductDiscountsTab();
}

function updateProductDiscountsTab() {
    if (!productId) {
        $('#productDiscountsNewHint').show();
        $('#productDiscountsWrap').hide();
        return;
    }
    $('#productDiscountsNewHint').hide();
    $('#productDiscountsWrap').show();
    $('#createDiscountBtn')
        .removeClass('disabled').removeAttr('aria-disabled').removeAttr('tabindex').removeAttr('title')
        .attr('href', CABINET_URL + 'discounts/edit/new/?product_id=' + productId);

    dsLoad('discounts').done(function (rows) {
        var p = allProducts.find(function (x) { return x.id === productId; });
        var catIds = p ? (p.category_ids || []) : [];
        var matches = ownOnly(rows || []).filter(function (d) {
            if (d.target_type === 'all') return true;
            if (d.target_type === 'products') return (d.target_ids || []).indexOf(productId) !== -1;
            if (d.target_type === 'category') return (d.target_ids || []).some(function (cid) { return catIds.indexOf(cid) !== -1; });
            return false;
        });

        $('#discountCountBadge').text(matches.length).toggle(matches.length > 0);
        $('#productDiscountsEmpty').toggle(matches.length === 0);
        var $list = $('#productDiscountsList').empty();
        matches.forEach(function (d) {
            var valueLabel = d.discount_type === 'percent' ? ('−' + d.value + '%') : ('−' + fmtMoney(d.value));
            var conditions = [];
            if (d.min_qty) conditions.push('от ' + d.min_qty + ' шт.');
            if (d.min_amount) conditions.push('от ' + fmtMoney(d.min_amount));
            var period = (d.date_from ? fmtDateShort(d.date_from) : '') + (d.date_from || d.date_to ? ' – ' : '') + (d.date_to ? fmtDateShort(d.date_to) : '');
            $list.append(
                '<a href="' + CABINET_URL + 'discounts/edit/' + d.id + '/" class="discount-item">' +
                  '<div class="discount-value">' + esc(valueLabel) + '</div>' +
                  '<div class="discount-info">' +
                    '<div class="discount-name">' + esc(d.name) + '</div>' +
                    '<div class="discount-meta">' + (conditions.length ? 'Условия: ' + esc(conditions.join(', ')) + ' · ' : '') + esc(period) + '</div>' +
                  '</div>' +
                  statusPill(d.status) +
                '</a>'
            );
        });
    });
}

function currentVariantGroupId() {
    var p = allProducts.find(function (x) { return x.id === productId; });
    return p ? (p.variant_group_id || null) : null;
}

function renderVariantGroup() {
    var gid = currentVariantGroupId();
    var members = gid ? allProducts.filter(function (p) { return p.id !== productId && p.variant_group_id === gid; }) : [];

    $('#variantCountBadge').text(members.length).toggle(members.length > 0);
    $('#variantGroupEmpty').toggle(members.length === 0);
    var $list = $('#variantGroupList').empty();
    members.forEach(function (p) {
        $list.append(
            '<div class="variant-item">' +
              bgThumbHtml(p.preview_image, 'thumb-sm') +
              '<div class="variant-info">' +
                '<div class="variant-name"><a href="' + CABINET_URL + 'products/edit/' + p.id + '/">' + esc(p.name) + '</a></div>' +
                '<div class="text-muted-2 small">' + esc(p.sku || '—') + (p.color ? ' · ' + esc(p.color) : '') + (p.size ? ' · ' + esc(p.size) : '') + '</div>' +
              '</div>' +
              '<button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFromVariantGroup(' + p.id + ')" title="Убрать из группы"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>' +
            '</div>'
        );
    });
}

function searchProductsForVariant(q) {
    var $results = $('#variantSearchResults');
    $results.html('<div class="gsearch-empty">Ищем…</div>').addClass('show');
    setTimeout(function () {
        var ql = q.toLowerCase();
        var gid = currentVariantGroupId();
        var matches = allProducts.filter(function (p) {
            if (p.id === productId) return false;
            if (gid && p.variant_group_id === gid) return false;
            return p.name.toLowerCase().indexOf(ql) !== -1 || (p.sku || '').toLowerCase().indexOf(ql) !== -1;
        }).slice(0, 10);
        if (matches.length === 0) { $results.html('<div class="gsearch-empty">Ничего не найдено</div>'); return; }
        var html = matches.map(function (p) {
            return '<div class="gsearch-item cursor-pointer" onclick="addToVariantGroup(' + p.id + ')"><span>' + esc(p.name) +
                ' <span class="text-muted-2">· ' + esc(p.sku || '') + (p.color ? ' · ' + esc(p.color) : '') + (p.size ? ' · ' + esc(p.size) : '') + '</span></span></div>';
        }).join('');
        $results.html(html);
    }, 200);
}

function addToVariantGroup(otherId) {
    var gid = currentVariantGroupId() || productId;
    allProducts = allProducts.map(function (p) {
        if (p.id === productId || p.id === otherId) return Object.assign({}, p, {variant_group_id: gid});
        return p;
    });
    saveOwnRows('products', 'data/products.json', allProducts, function () {
        $('#variantSearch').val('');
        $('#variantSearchResults').removeClass('show').empty();
        renderVariantGroup();
        showResult(true, 'Товар добавлен в группу вариантов');
    });
}

function removeFromVariantGroup(otherId) {
    var gid = currentVariantGroupId();
    if (!gid) return;
    allProducts = allProducts.map(function (p) {
        return (p.id === otherId && p.variant_group_id === gid) ? Object.assign({}, p, {variant_group_id: null}) : p;
    });
    var remaining = allProducts.filter(function (p) { return p.variant_group_id === gid; });
    if (remaining.length <= 1) {
        allProducts = allProducts.map(function (p) {
            return p.variant_group_id === gid ? Object.assign({}, p, {variant_group_id: null}) : p;
        });
    }
    saveOwnRows('products', 'data/products.json', allProducts, function () {
        renderVariantGroup();
        showResult(true, 'Товар убран из группы вариантов');
    });
}

function renderGallery() {
    var $c = $('#galleryContainer').empty();
    if (gallery.length === 0) $c.append('<div class="text-muted-2 small">Фото ещё не добавлены.</div>');
    gallery.forEach(function (url, idx) {
        $c.append('<div class="gallery-item">' + bgThumbHtml(url, 'gimg') + '<button type="button" class="btn btn-sm btn-outline-danger rm" onclick="removeGalleryImage(' + idx + ')"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button></div>');
    });
}
function removeGalleryImage(idx) { gallery.splice(idx, 1); renderGallery(); }

function renderProps() {
    var $c = $('#propsContainer').empty();
    if (props.length === 0) {
        $c.append('<div class="props-table-empty">Дополнительных свойств нет — например, «Материал», «Вес».</div>');
        return;
    }
    props.forEach(function (pr, idx) {
        $c.append(
            '<div class="props-table-row">' +
              '<div class="prop-name-wrap">' +
                '<input type="text" class="form-control prop-name" placeholder="Например, Материал" value="' + esc(pr.name) + '" data-idx="' + idx + '" autocomplete="off">' +
                '<div class="gsearch-dropdown prop-name-suggest"></div>' +
              '</div>' +
              '<input type="text" class="form-control prop-value" placeholder="Например, Хлопок" value="' + esc(pr.value) + '" data-idx="' + idx + '">' +
              '<button type="button" class="btn btn-sm btn-outline-danger" onclick="removeProp(' + idx + ')"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>' +
            '</div>'
        );
    });
}

function renderPropNameSuggestions($input) {
    var q = $input.val().trim().toLowerCase();
    var $dd = $input.closest('.prop-name-wrap').find('.prop-name-suggest');
    if (!q) { $dd.removeClass('show').empty(); return; }
    var matches = ALL_PROPERTY_NAMES.filter(function (n) {
        return n.toLowerCase().indexOf(q) !== -1;
    }).slice(0, 15);
    if (!matches.length) { $dd.removeClass('show').empty(); return; }
    var html = matches.map(function (n) {
        return '<div class="gsearch-item cursor-pointer" data-name="' + esc(n) + '">' + esc(n) + '</div>';
    }).join('');
    $dd.html(html).addClass('show');
}
function removeProp(idx) { syncPropsFromInputs(); props.splice(idx, 1); renderProps(); }
function syncPropsFromInputs() {
    $('.prop-name').each(function () { var i = $(this).data('idx'); if (props[i]) props[i].name = $(this).val(); });
    $('.prop-value').each(function () { var i = $(this).data('idx'); if (props[i]) props[i].value = $(this).val(); });
}

function doSave(goBack) {
    var name = $('#f_name').val().trim();
    var categoryIds = getCheckboxTreeSelected('#f_category_tree');
    if (!name) { showResult(false, 'Укажите название товара'); return; }
    if (categoryIds.length === 0) { showResult(false, 'Выберите хотя бы одну категорию'); return; }
    syncPropsFromInputs();
    var cleanProps = props.filter(function (p) { return p.name.trim() && p.value.trim(); });
    var fullDesc = $.fn.trumbowyg ? $('#f_full_desc').trumbowyg('html') : $('#f_full_desc').val();

    var isNew = !productId;
    var payload = {
        category_ids: categoryIds,
        name: name,
        slug: $('#f_code').val().trim() || slugifyClient(name),
        short_desc: $('#f_short_desc').val(),
        full_desc: fullDesc,
        sku: $('#f_sku').val(),
        color: $('#f_color').val(),
        size: $('#f_size').val(),
        price: parseInt($('#f_price').val(), 10) || 0,
        is_preorder: $('#f_preorder').is(':checked'),
        tags: $('.tag-toggle input[type="checkbox"]:checked').map(function () { return this.value; }).get().concat(customTags),
        stock: parseInt($('#f_stock').val(), 10) || 0,
        status: $('#f_status').val(),
        preview_image: previewDataUrl,
        gallery: gallery,
        custom_props: cleanProps,
        partner_id: CURRENT_PARTNER_ID
    };

    if (productId) {
        allProducts = allProducts.map(function (p) { return p.id === productId ? Object.assign({}, p, payload) : p; });
    } else {
        payload.id = dsNextId(allProducts);
        productId = payload.id;
        allProducts.push(payload);
        $('#f_id').val(productId);
        $('#formTitle').text('Редактирование: ' + name);
    }
    saveOwnRows('products', 'data/products.json', allProducts, function (savedRows) {
        if (isNew) {
            var saved = savedRows[savedRows.length - 1];
            if (saved && saved.id) { productId = saved.id; $('#f_id').val(productId); }
        }
        showResult(true, isNew ? 'Товар создан' : 'Товар сохранён');
        updateVariantTabVisibility();
        if (goBack) setTimeout(function () { window.location.href = CABINET_URL + 'products/'; }, 700);
    });
}

function doDelete() {
    if (!productId) return;
    var p = allProducts.find(function (x) { return x.id === productId; });
    showConfirm('Удалить товар «' + (p ? p.name : '') + '»? Это действие необратимо.', function () {
        allProducts = allProducts.filter(function (x) { return x.id !== productId; });
        saveOwnRows('products', 'data/products.json', allProducts, function () {
            showResult(true, 'Товар удалён');
            setTimeout(function () { window.location.href = CABINET_URL + 'products/'; }, 700);
        });
    }, {danger: true, okText: 'Удалить'});
}

function renderCustomTags() {
    var $wrap = $('#customTagsWrap').empty();
    customTags.forEach(function (t, idx) {
        $wrap.append(
            '<span class="tag-toggle active">' + esc(t) +
            ' <span class="tag-remove cursor-pointer" onclick="removeCustomTag(' + idx + ')">✕</span></span>'
        );
    });
}
function addCustomTag() {
    var val = $('#customTagInput').val().trim();
    if (!val) return;
    if (FIXED_TAGS.indexOf(val.toLowerCase()) !== -1 || customTags.some(function (t) { return t.toLowerCase() === val.toLowerCase(); })) {
        showResult(false, 'Такая метка уже добавлена');
        return;
    }
    customTags.push(val);
    $('#customTagInput').val('');
    renderCustomTags();
}
function removeCustomTag(idx) {
    customTags.splice(idx, 1);
    renderCustomTags();
}
