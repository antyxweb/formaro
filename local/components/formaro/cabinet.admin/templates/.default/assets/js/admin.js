/* Кабинет администратора (/admin/) — общий слой поверх common.js кабинета
   партнёра. Подключается после common.js и перед скриптом страницы:
   - помощники «по партнёрам» (имя, select фильтра, плашка партнёра);
   - администратор видит всё — ownOnly/visibleCategories без ограничения
     «свои» (общие экраны партнёра, например форма новости);
   - свои счётчики сайдбара/топбара, глобальный поиск, окно причины
     отклонения. */
var CABINET_URL = window.CABINET_BOOTSTRAP.cabinetUrl;
var ADMIN_PARTNERS = window.CABINET_BOOTSTRAP.partners || [];

function ownOnly(rows) { return rows; }
function visibleCategories(all) { return all; }
function isCategoryVisible() { return true; }
function canEditCategory() { return true; }
function loadAccountWidget() {}

/* ---------------- Партнёры ---------------- */
function partnerById(id) {
    id = Number(id) || 0;
    return ADMIN_PARTNERS.find(function (p) { return p.id === id; }) || null;
}
function partnerName(id) {
    var p = partnerById(id);
    return p ? p.name : (id ? 'Партнёр #' + id : '');
}
/** Плашка-ссылка на карточку партнёра; без партнёра — siteLabel (например «Площадка»). */
function partnerCell(id, siteLabel) {
    if (!id) return siteLabel ? '<span class="pill pill-gray">' + esc(siteLabel) + '</span>' : '<span class="text-muted-2">—</span>';
    return '<a class="partner-link" href="' + CABINET_URL + 'partners/edit/' + id + '/" onclick="event.stopPropagation();">' + esc(partnerName(id)) + '</a>';
}
/**
 * Наполнить select фильтра партнёров: «Все партнёры» (''), опционально
 * «Площадка» ('0' — записи без партнёра), затем партнёры по алфавиту.
 * ?partner=ID в адресе — выбрать сразу.
 */
function fillPartnerSelect(sel, opts) {
    opts = opts || {};
    var html = '<option value="">' + (opts.allLabel || 'Все партнёры') + '</option>';
    if (opts.siteLabel) html += '<option value="0">' + esc(opts.siteLabel) + '</option>';
    ADMIN_PARTNERS.slice().sort(function (a, b) { return a.name.localeCompare(b.name, 'ru'); }).forEach(function (p) {
        html += '<option value="' + p.id + '">' + esc(p.name) + (p.active ? '' : ' (не активен)') + '</option>';
    });
    var $s = $(sel).html(html);
    var fromUrl = getQueryParam('partner');
    if (fromUrl !== null && fromUrl !== '' && $s.find('option[value="' + fromUrl + '"]').length) $s.val(fromUrl);
    return $s;
}
/** Строка подходит под фильтр партнёра ('' — все, '0' — без партнёра). */
function matchPartner(rowPartnerId, filterValue) {
    if (filterValue === '' || filterValue === null || filterValue === undefined) return true;
    return (Number(rowPartnerId) || 0) === Number(filterValue);
}

/** Статус проверки партнёра (VERIFICATION_STATUS). */
function partnerStatusPill(status) {
    var map = {not_verified: ['Не проверен', 'pill-gray'], pending: ['На проверке', 'pill-yellow'], verified: ['Проверен', 'pill-green'], rejected: ['Отклонён', 'pill-red']};
    var m = map[status] || map.not_verified;
    return '<span class="pill ' + m[1] + '">' + m[0] + '</span>';
}

/* ---------------- Модерация: что ждёт администратора ---------------- */
/** Партнёр ждёт проверки: прислал данные (pending/черновик) или новый неактивный без проверки. */
function partnerNeedsModeration(p) {
    return p.verification_status === 'pending' || p.has_pending || (!p.active && p.verification_status === 'not_verified');
}
function categoryNeedsModeration(c) {
    return !c.is_system && c.status === 'pending';
}

/* ---------------- Причина отклонения ---------------- */
function adminAskReason(opts, onConfirm) {
    var $modal = $('#reasonModal');
    $modal.find('.reason-title').text(opts.title || 'Причина отклонения');
    $modal.find('.reason-label').text(opts.label || 'Причина — её увидит партнёр');
    $modal.find('.reason-ok-btn').text(opts.okText || 'Отклонить')
        .toggleClass('btn-danger', !opts.primary).toggleClass('btn-primary', !!opts.primary);
    var $input = $('#reasonInput').val(opts.value || '').removeClass('is-invalid');
    $modal.find('.reason-ok-btn').off('click.reason').on('click.reason', function () {
        var text = $input.val().trim();
        if (!text && !opts.optional) { $input.addClass('is-invalid').focus(); return; }
        bootstrap.Modal.getOrCreateInstance($modal[0]).hide();
        onConfirm(text);
    });
    $modal.one('shown.bs.modal', function () { $input.focus(); });
    bootstrap.Modal.getOrCreateInstance($modal[0]).show();
}

/* ---------------- Счётчики сайдбара и топбара ---------------- */
function setSideCount(key, n) {
    $('.side-count[data-count="' + key + '"]').text(n).toggleClass('attention', n > 0).toggle(n > 0);
}
function refreshSidebarCounts() {
    $.when(dsLoadSilent('partners'), dsLoadSilent('categories')).done(function (partners, categories) {
        var n = (partners || []).filter(partnerNeedsModeration).length + (categories || []).filter(categoryNeedsModeration).length;
        setSideCount('moderation', n);
        $('#moderationDot').text(n).toggle(n > 0);
    });
    dsLoadSilent('transactions').done(function (rows) {
        setSideCount('withdrawals', (rows || []).filter(function (t) { return t.type === 'withdrawal' && t.status === 'pending'; }).length);
    });
    dsLoadSilent('notifications').done(function (rows) {
        var n = (rows || []).filter(function (r) { return !r.is_read; }).length;
        setSideCount('notifications', n);
        $('#notifDot').text(n).toggle(n > 0);
    });
    dsLoadSilent('tickets').done(function (rows) {
        var n = (rows || []).filter(function (t) { return t.status === 'open'; }).length;
        setSideCount('support', n);
        $('#supportDot').text(n).toggle(n > 0);
    });
}
function refreshTopbarCounts() {}

/* ---------------- Глобальный поиск ---------------- */
function runGlobalSearch(q, $results) {
    var ql = q.toLowerCase();
    function has(s) { return String(s || '').toLowerCase().indexOf(ql) !== -1; }
    $.when(dsLoad('partners'), dsLoad('categories'), dsLoad('orders'), dsLoad('news'), dsLoad('tickets')).done(function (partners, categories, orders, news, tickets) {
        var groups = [];
        var p = partners.filter(function (x) { return has(x.name) || has(x.name_full) || has(x.inn); }).slice(0, 5);
        var c = categories.filter(function (x) { return has(x.name); }).slice(0, 5);
        var o = orders.filter(function (x) { return has(x.order_number) || has(x.customer && x.customer.name); }).slice(0, 5);
        var n = news.filter(function (x) { return has(x.title); }).slice(0, 5);
        var t = tickets.filter(function (x) { return has(x.subject); }).slice(0, 5);
        if (p.length) groups.push({title: 'Партнёры', icon: GSEARCH_ICONS.categories, items: p.map(function (x) { return {label: x.name, sub: x.inn, url: CABINET_URL + 'partners/edit/' + x.id + '/'}; })});
        if (c.length) groups.push({title: 'Категории', icon: GSEARCH_ICONS.categories, items: c.map(function (x) { return {label: x.name, sub: x.partner_id ? partnerName(x.partner_id) : 'Площадка', url: CABINET_URL + 'categories/edit/' + x.id + '/'}; })});
        if (o.length) groups.push({title: 'Заказы', icon: GSEARCH_ICONS.orders, items: o.map(function (x) { return {label: x.order_number + ' — ' + x.customer.name, sub: partnerName(x.partner_id), url: CABINET_URL + 'orders/?id=' + x.id}; })});
        if (n.length) groups.push({title: 'Новости', icon: GSEARCH_ICONS.news, items: n.map(function (x) { return {label: x.title, url: CABINET_URL + 'news/edit/' + x.id + '/'}; })});
        if (t.length) groups.push({title: 'Обращения', icon: GSEARCH_ICONS.discounts, items: t.map(function (x) { return {label: x.subject, sub: partnerName(x.partner_id), url: CABINET_URL + 'support/' + x.id + '/'}; })});
        renderGlobalSearchResults(groups, $results);
    });
}

/* ---------------- Прочее ---------------- */
var EMPTY_ROW_ICON = '<svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg>';
function emptyRow(colspan, text) {
    return '<tr><td colspan="' + colspan + '"><div class="empty-state">' + EMPTY_ROW_ICON + esc(text) + '</div></td></tr>';
}
