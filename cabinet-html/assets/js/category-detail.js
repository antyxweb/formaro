var categoryId = 0;
var allCategories = [];
var previewDataUrl = '';
var fullDataUrl = '';
var readOnly = false;

$(function () {
    loadPartials('categories', 'Категория');

    var idParam = getQueryParam('id');
    categoryId = (idParam && idParam !== 'new') ? parseInt(idParam, 10) : 0;
    var parentParam = parseInt(getQueryParam('parent_id'), 10) || 0;

    bindImagePreview('#previewFile', '#previewImg', function (url) { previewDataUrl = url; });
    bindImagePreview('#fullFile', '#fullImg', function (url) { fullDataUrl = url; });
    bindImageRemove('#previewRemoveBtn', '#previewImg', 'https://placehold.co/200x200?text=%20', function () { previewDataUrl = ''; });
    bindImageRemove('#fullRemoveBtn', '#fullImg', 'https://placehold.co/200x200?text=%20', function () { fullDataUrl = ''; });
    initRichText('#f_full_desc');
    bindAutoHeight('#f_short_desc');

    var codeChain = bindCodeChain('#f_name', '#f_code', '#codeChainBtn');

    dsLoad('categories', 'data/categories.json').done(function (allLoaded) {
        // видим системные + свои категории; чужие партнёрские скрыты (и как самостоятельная
        // запись, и как вариант родителя)
        allCategories = visibleCategories(allLoaded);

        if (categoryId) {
            var c = allCategories.find(function (x) { return x.id === categoryId; });
            if (!c) { showResult(false, 'Категория не найдена'); return; }

            readOnly = isSystemCategory(c);
            fillParentTree();
            $('#previewBtn').attr('href', 'preview.html?type=category&id=' + c.id);
            $('#deleteBtn').toggleClass('d-none', readOnly);
            $('#formTitle').text((readOnly ? 'Просмотр: ' : 'Редактирование: ') + c.name);
            $('#f_id').val(c.id);
            $('#f_name').val(c.name);
            $('#f_code').val(c.slug); codeChain.setExisting();
            $('#f_short_desc').val(c.short_desc); autoHeightResize('#f_short_desc');
            $('#f_full_desc').val(c.full_desc);
            if ($.fn.trumbowyg) $('#f_full_desc').trumbowyg('html', c.full_desc || '');
            $('#f_status').val(c.status || 'active');
            if (c.preview_image) { setBgImage('#previewImg', c.preview_image); previewDataUrl = c.preview_image; }
            if (c.full_image) { setBgImage('#fullImg', c.full_image); fullDataUrl = c.full_image; }

            if (readOnly) applyReadOnlyMode();
        } else {
            renderCategoryRadioTree('#f_parent_tree', allCategories, parentParam || 0, []);
        }
    });

    $('#saveBtn').on('click', function () { doSave(true); });
    $('#applyBtn').on('click', function () { doSave(false); });
    $('#deleteBtn').on('click', doDelete);
});

/** Системную категорию нельзя редактировать — блокируем поля и прячем
    кнопки сохранения, оставляем только просмотр и предпросмотр на сайте. */
function applyReadOnlyMode() {
    $('#systemBanner').removeClass('d-none');
    $('#categoryForm').find('input, textarea, select, button').not('#previewFile, #fullFile').prop('disabled', true);
    $('#previewFile, #fullFile').closest('.img-field').find('.img-actions').hide();
    if ($.fn.trumbowyg) $('#f_full_desc').trumbowyg('disable');
    $('#saveBtn, #applyBtn').hide();
}

function fillParentTree() {
    // при редактировании исключаем саму категорию и всех её потомков — иначе получится цикл
    var excludeIds = categoryId ? [categoryId].concat(categoryDescendantIds(allCategories, categoryId)) : [];
    var current = categoryId ? allCategories.find(function (x) { return x.id === categoryId; }) : null;
    var selectedId = current ? (current.parent_id || 0) : 0;
    renderCategoryRadioTree('#f_parent_tree', allCategories, selectedId, excludeIds);
}

function doSave(goBack) {
    if (readOnly) return; // системную категорию сохранить нельзя — кнопки скрыты, но подстрахуемся
    var name = $('#f_name').val().trim();
    if (!name) { showResult(false, 'Укажите название категории'); return; }

    var parentId = getCategoryRadioSelected('#f_parent_tree');

    if (categoryId) {
        var forbidden = [categoryId].concat(categoryDescendantIds(allCategories, categoryId));
        if (forbidden.indexOf(parentId) !== -1) {
            showResult(false, 'Нельзя сделать категорию подкатегорией самой себя или своей же подкатегории');
            return;
        }
    }

    var fullDesc = $.fn.trumbowyg ? $('#f_full_desc').trumbowyg('html') : $('#f_full_desc').val();
    var payload = {
        parent_id: parentId,
        name: name,
        slug: $('#f_code').val().trim() || slugifyClient(name),
        short_desc: $('#f_short_desc').val(),
        full_desc: fullDesc,
        preview_image: previewDataUrl,
        full_image: fullDataUrl,
        status: $('#f_status').val(),
        is_system: false,
        partner_id: CURRENT_PARTNER_ID
    };

    if (categoryId) {
        allCategories = allCategories.map(function (c) { return c.id === categoryId ? Object.assign({}, c, payload) : c; });
    } else {
        payload.id = dsNextId(allCategories);
        payload.created_at = new Date().toISOString();
        allCategories.push(payload);
        categoryId = payload.id;
        $('#f_id').val(categoryId);
        $('#formTitle').text('Редактирование: ' + name);
        $('#previewBtn').attr('href', 'preview.html?type=category&id=' + categoryId);
    }
    saveVisibleCategories(allCategories, function () {
        showResult(true, categoryId ? 'Категория сохранена' : 'Категория создана');
        if (goBack) {
            setTimeout(function () { window.location.href = 'categories.html'; }, 700);
        } else {
            fillParentTree();
        }
    });
}

function doDelete() {
    if (readOnly || !categoryId) return; // системную нельзя, у новой ещё нечего удалять
    var hasChildren = categoryDirectChildren(allCategories, categoryId).length > 0;
    if (hasChildren) {
        showResult(false, 'Нельзя удалить категорию — в ней есть подкатегории. Сначала удалите или перенесите их.');
        return;
    }
    dsLoad('products', 'data/products.json').done(function (prods) {
        var hasProducts = ownOnly(prods).some(function (p) { return (p.category_ids || []).indexOf(categoryId) !== -1; });
        if (hasProducts) {
            showResult(false, 'Нельзя удалить категорию — в ней есть товары. Сначала перенесите их в другую категорию.');
            return;
        }
        showConfirm('Удалить категорию «' + $('#f_name').val() + '»? Это действие необратимо.', function () {
            allCategories = allCategories.filter(function (c) { return c.id !== categoryId; });
            saveVisibleCategories(allCategories, function () {
                showResult(true, 'Категория удалена');
                setTimeout(function () { window.location.href = 'categories.html'; }, 700);
            });
        }, {danger: true, okText: 'Удалить'});
    });
}
