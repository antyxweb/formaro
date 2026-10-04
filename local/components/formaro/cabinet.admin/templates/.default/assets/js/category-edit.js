/* Форма категории в кабинете администратора (по образцу category-detail.js
   кабинета партнёра): любая категория — общая или партнёрская; владельца
   выбирают только у новой. Модерация партнёрской категории — допустить /
   отклонить с причиной (партнёру уходит уведомление). */
var categoryId = 0;
var category = null;
var allCategories = [];
var previewDataUrl = '';
var fullDataUrl = '';
var PLACEHOLDER = 'https://placehold.co/200x200?text=%20';

$(function () {
    categoryId = (ROUTE_ID && ROUTE_ID !== 'new') ? parseInt(ROUTE_ID, 10) : 0;

    bindImagePreview('#previewFile', '#previewImg', function (url) { previewDataUrl = url; });
    bindImagePreview('#fullFile', '#fullImg', function (url) { fullDataUrl = url; });
    bindImageRemove('#previewRemoveBtn', '#previewImg', PLACEHOLDER, function () { previewDataUrl = ''; });
    bindImageRemove('#fullRemoveBtn', '#fullImg', PLACEHOLDER, function () { fullDataUrl = ''; });
    initRichText('#f_full_desc');
    bindAutoHeight('#f_short_desc');
    fillPartnerSelect('#f_partner', {allLabel: 'Площадка (общая категория)'});
    var codeChain = bindCodeChain('#f_name', '#f_code');

    dsLoad('categories').done(function (rows) {
        allCategories = rows;
        if (!categoryId) {
            $('#f_partner').val(getQueryParam('partner') || '');
            renderParentTree(parseInt(getQueryParam('parent_id'), 10) || 0);
            return;
        }
        category = rows.find(function (c) { return c.id === categoryId; });
        if (!category) { showResult(false, 'Категория не найдена'); return; }

        $('#formTitle').text('Редактирование: ' + category.name);
        $('#deleteBtn').removeClass('d-none');
        $('#f_id').val(category.id);
        $('#f_name').val(category.name);
        $('#f_code').val(category.slug); codeChain.setExisting();
        $('#codeChainBtn').removeClass('d-none');
        bindCodeCopyBtn('#codeChainBtn', function () { return category.public_url; });
        if (category.public_url) $('#previewBtn').attr('href', category.public_url).removeClass('disabled').removeAttr('aria-disabled tabindex');
        $('#f_status').val(category.active ? 'active' : 'hidden');
        $('#f_partner').val(category.partner_id || '').prop('disabled', true);
        $('#ownerHint').text(category.partner_id ? 'Категория партнёра — видна только ему и на сайте после допуска.' : 'Общая категория площадки, видна всем партнёрам.');
        $('#f_short_desc').val(category.short_desc); autoHeightResize('#f_short_desc');
        $('#f_full_desc').val(category.full_desc);
        if ($.fn.trumbowyg) $('#f_full_desc').trumbowyg('html', category.full_desc || '');
        if (category.preview_image) { setBgImage('#previewImg', category.preview_image); previewDataUrl = category.preview_image; }
        if (category.full_image) { setBgImage('#fullImg', category.full_image); fullDataUrl = category.full_image; }
        renderParentTree(category.parent_id || 0);
        renderModeration();
    });

    $('#saveBtn').on('click', function () { doSave(true); });
    $('#applyBtn').on('click', function () { doSave(false); });
    $('#deleteBtn').on('click', doDelete);
    $('#approveBtn').on('click', approve);
    $('#rejectBtn').on('click', reject);
});

function renderParentTree(selectedId) {
    var exclude = categoryId ? [categoryId].concat(categoryDescendantIds(allCategories, categoryId)) : [];
    renderCategoryRadioTree('#f_parent_tree', allCategories, selectedId, exclude, false);
}

function renderModeration() {
    if (!category || !category.partner_id) { $('#moderationCard').addClass('d-none'); return; }
    var text = category.approved
        ? 'Категория партнёра «' + partnerName(category.partner_id) + '» допущена к показу на сайте.'
        : (category.status === 'rejected'
            ? 'Отклонена. Причина: ' + category.reject_reason
            : 'Категория партнёра «' + partnerName(category.partner_id) + '» ждёт проверки — на сайте не показывается.');
    $('#moderationCard').removeClass('d-none');
    $('#moderationPill').html(statusPill(category.approved ? 'verified' : category.status));
    $('#moderationText').text(text);
    $('#approveBtn').toggleClass('d-none', !!category.approved);
    $('#rejectBtn').toggleClass('d-none', category.status === 'rejected');
}

function approve() {
    cabinetAjax({ajax_action: 'approve_category', id: categoryId}).done(function (saved) {
        category = saved;
        renderModeration();
        refreshSidebarCounts();
        showResult(true, 'Категория допущена к показу');
    });
}

function reject() {
    adminAskReason({title: 'Отклонить категорию', value: category ? category.reject_reason : ''}, function (reason) {
        cabinetAjax({ajax_action: 'reject_category', id: categoryId, reason: reason}).done(function (saved) {
            category = saved;
            renderModeration();
            refreshSidebarCounts();
            showResult(true, 'Категория отклонена, партнёр получил уведомление');
        });
    });
}

function doSave(goBack) {
    var name = $('#f_name').val().trim();
    if (!name) { showResult(false, 'Укажите название категории'); return; }
    var parentId = getCategoryRadioSelected('#f_parent_tree') || 0;
    if (categoryId && [categoryId].concat(categoryDescendantIds(allCategories, categoryId)).indexOf(parentId) !== -1) {
        showResult(false, 'Нельзя сделать категорию подкатегорией самой себя или своей подкатегории');
        return;
    }
    var wasNew = !categoryId;
    dsSaveOne('categories', {
        id: categoryId || 0,
        parent_id: parentId,
        name: name,
        slug: $('#f_code').val().trim() || slugifyClient(name),
        short_desc: $('#f_short_desc').val(),
        full_desc: $.fn.trumbowyg ? $('#f_full_desc').trumbowyg('html') : $('#f_full_desc').val(),
        preview_image: previewDataUrl,
        full_image: fullDataUrl,
        active: $('#f_status').val() !== 'hidden',
        partner_id: wasNew ? (Number($('#f_partner').val()) || 0) : undefined
    }).done(function (saved) {
        showResult(true, wasNew ? 'Категория создана' : 'Категория сохранена');
        if (goBack) { setTimeout(function () { window.location.href = CABINET_URL + 'categories/'; }, 700); return; }
        if (wasNew) { window.location.href = CABINET_URL + 'categories/edit/' + saved.id + '/'; return; }
        category = saved;
        allCategories = allCategories.map(function (c) { return c.id === saved.id ? saved : c; });
        renderModeration();
    });
}

function doDelete() {
    if (!categoryId) return;
    if (categoryDirectChildren(allCategories, categoryId).length) {
        showResult(false, 'Нельзя удалить категорию — в ней есть подкатегории.');
        return;
    }
    showConfirm('Удалить категорию «' + $('#f_name').val() + '»? Это действие необратимо.', function () {
        dsDeleteOne('categories', categoryId).done(function () {
            showResult(true, 'Категория удалена');
            setTimeout(function () { window.location.href = CABINET_URL + 'categories/'; }, 700);
        });
    }, {danger: true, okText: 'Удалить'});
}
