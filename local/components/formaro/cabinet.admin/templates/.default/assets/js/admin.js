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
    $s.each(function () { searchableSelect(this); });
    return $s;
}

/* ---------------- Выпадающий список с поиском ----------------
   Надстройка над обычным <select>: сам select остаётся в DOM (скрыт) и
   хранит значение — фильтры, bindClearFilters, формы читают и пишут его
   как раньше; виджет показывает выбранное и список с поиском. Значение,
   выставленное кодом через .val(), подхватывается (обёртка valHooks),
   disabled — тоже (MutationObserver на атрибуте). */
var SEARCHABLE_CLASS = 'js-searchable-select';

function searchableSelect(select) {
    var $select = $(select);
    if ($select.hasClass(SEARCHABLE_CLASS)) { searchableRefresh(select); return; }
    $select.addClass(SEARCHABLE_CLASS + ' d-none');
    var $w = $('<div class="ssel"><button type="button" class="form-select ssel-toggle"><span class="ssel-label"></span></button>' +
        '<div class="ssel-menu"><input type="text" class="form-control form-control-sm ssel-search" placeholder="Поиск…" autocomplete="off">' +
        '<div class="ssel-list" role="listbox"></div></div></div>');
    $w.attr('style', $select.attr('style') || '');
    $select.after($w);
    $select.data('ssel', $w);

    var $toggle = $w.find('.ssel-toggle');
    var $search = $w.find('.ssel-search');
    var $list = $w.find('.ssel-list');
    if (select.id) {
        $toggle.attr('id', select.id + '_ssel');
        $('label[for="' + select.id + '"]').attr('for', select.id + '_ssel');
    }

    function renderList() {
        var q = $search.val().trim().toLowerCase();
        var html = '';
        $select.find('option').each(function () {
            if (q && this.text.toLowerCase().indexOf(q) === -1) return;
            html += '<div class="ssel-item' + (this.value === $select.val() ? ' selected' : '') + '" role="option" data-value="' + esc(this.value) + '">' + esc(this.text) + '</div>';
        });
        $list.html(html || '<div class="ssel-empty">Ничего не найдено</div>');
        $list.find('.ssel-item').first().addClass('active');
    }
    function open() {
        if (select.disabled) return;
        $('.ssel.open').not($w).removeClass('open');
        $w.addClass('open');
        $search.val('');
        renderList();
        var $sel = $list.find('.ssel-item.selected');
        if ($sel.length) { $list.find('.active').removeClass('active'); $sel.addClass('active'); $list.scrollTop($sel[0].offsetTop - 40); }
        $search.trigger('focus');
    }
    function close() { $w.removeClass('open'); }
    function choose(value) {
        close();
        if (value !== $select.val()) $select.val(value).trigger('change');
        $toggle.trigger('focus');
    }
    function move(step) {
        var $items = $list.find('.ssel-item');
        var i = $items.index($items.filter('.active'));
        i = Math.max(0, Math.min($items.length - 1, i + step));
        $items.removeClass('active').eq(i).addClass('active');
        var el = $items[i];
        if (el) el.scrollIntoView({block: 'nearest'});
    }

    $toggle.on('click', function () { $w.hasClass('open') ? close() : open(); });
    $toggle.on('keydown', function (e) {
        if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); }
    });
    $search.on('input', renderList);
    $search.on('keydown', function (e) {
        if (e.key === 'ArrowDown') { e.preventDefault(); move(1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); move(-1); }
        else if (e.key === 'Enter') { e.preventDefault(); var $a = $list.find('.ssel-item.active'); if ($a.length) choose(String($a.data('value'))); }
        else if (e.key === 'Escape') { e.preventDefault(); close(); $toggle.trigger('focus'); }
        else if (e.key === 'Tab') { close(); }
    });
    $list.on('mousedown', '.ssel-item', function (e) { e.preventDefault(); choose(String($(this).data('value'))); });
    $list.on('mousemove', '.ssel-item', function () { $list.find('.active').removeClass('active'); $(this).addClass('active'); });
    $(document).on('mousedown', function (e) { if (!$(e.target).closest($w).length) close(); });
    $select.on('change', function () { searchableRefresh(select); });
    new MutationObserver(function () { searchableRefresh(select); }).observe(select, {attributes: true, attributeFilter: ['disabled']});
    searchableRefresh(select);
}

function searchableRefresh(select) {
    var $w = $(select).data('ssel');
    if (!$w) return;
    var opt = select.options[select.selectedIndex];
    $w.find('.ssel-label').text(opt ? opt.text : '');
    $w.toggleClass('disabled', !!select.disabled).find('.ssel-toggle').prop('disabled', !!select.disabled);
}

(function () {
    var hook = $.valHooks.select;
    var origSet = hook.set;
    hook.set = function (elem, value) {
        var result = origSet ? origSet.apply(this, arguments) : undefined;
        if ($(elem).hasClass(SEARCHABLE_CLASS)) setTimeout(function () { searchableRefresh(elem); }, 0);
        return result;
    };
})();
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
