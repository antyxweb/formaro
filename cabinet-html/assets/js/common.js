/* ==========================================================
   Formaro Cabinet — общие хелперы для всех страниц прототипа.
   Тема, партиалы (сайдбар/шапка/футер через AJAX), утилиты,
   работа с моковыми JSON-данными (тоже через AJAX/$.getJSON).
   ========================================================== */

/* ---------------- Тема (авто / светлая / тёмная) ---------------- */
var THEME_KEY = 'formaro_theme_mode';

var THEME_ICONS = {
    auto: '<rect width="20" height="14" x="2" y="3" rx="2"></rect><line x1="8" x2="16" y1="21" y2="21"></line><line x1="12" x2="12" y1="17" y2="21"></line>',
    light: '<circle cx="12" cy="12" r="4"></circle><path d="M12 2v2"></path><path d="M12 20v2"></path><path d="m4.93 4.93 1.41 1.41"></path><path d="m17.66 17.66 1.41 1.41"></path><path d="M2 12h2"></path><path d="M20 12h2"></path><path d="m6.34 17.66-1.41 1.41"></path><path d="m19.07 4.93-1.41 1.41"></path>',
    dark: '<path d="M20.985 12.486a9 9 0 1 1-9.473-9.472c.405-.022.617.46.402.803a6 6 0 0 0 8.268 8.268c.344-.215.825-.004.803.401"></path>'
};
var THEME_LABELS = {auto: 'Системная', light: 'Светлая', dark: 'Тёмная'};
var THEME_ORDER = ['auto', 'light', 'dark'];

function resolveTheme(mode) {
    if (mode === 'light' || mode === 'dark') return mode;
    return (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
}
function applyTheme(mode) {
    document.documentElement.setAttribute('data-theme', resolveTheme(mode));
}
function getThemeMode() {
    return localStorage.getItem(THEME_KEY) || 'auto';
}
function setThemeMode(mode) {
    localStorage.setItem(THEME_KEY, mode);
    applyTheme(mode);
    $('#themeIcon').html(THEME_ICONS[mode]);
    $('#themeToggle').attr('title', 'Тема: ' + THEME_LABELS[mode] + ' (нажмите для смены)');
}
// Применяем тему немедленно (до подгрузки партиалов), чтобы не было "мигания"
applyTheme(getThemeMode());
if (window.matchMedia) {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
        if (getThemeMode() === 'auto') applyTheme('auto');
    });
}

/* ---------------- Утилиты ---------------- */
function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
function fmtDate(iso, withTime) {
    if (!iso) return '';
    var d = new Date(iso);
    if (isNaN(d.getTime())) return iso;
    var s = d.toLocaleDateString('ru-RU');
    if (withTime !== false) s += ' ' + d.toLocaleTimeString('ru-RU', {hour: '2-digit', minute: '2-digit'});
    return s;
}
/** Только дата "дд.мм.гггг" без времени — для чисто-датовых полей (период
    действия скидки/купона), чтобы не ловить сдвиг часового пояса от
    fmtDate() при разборе строки вида "2026-08-01" как UTC-полночи. */
function fmtDateShort(iso) {
    if (!iso) return '';
    var d = new Date(iso + 'T00:00:00');
    return ('0' + d.getDate()).slice(-2) + '.' + ('0' + (d.getMonth() + 1)).slice(-2) + '.' + d.getFullYear();
}
function fmtMoney(n) {
    n = Number(n) || 0;
    return n.toLocaleString('ru-RU') + ' ₽';
}
function slugifyClient(text) {
    var map = {а:'a',б:'b',в:'v',г:'g',д:'d',е:'e',ё:'e',ж:'zh',з:'z',и:'i',й:'y',к:'k',л:'l',м:'m',н:'n',о:'o',п:'p',р:'r',с:'s',т:'t',у:'u',ф:'f',х:'h',ц:'ts',ч:'ch',ш:'sh',щ:'sch',ъ:'',ы:'y',ь:'',э:'e',ю:'yu',я:'ya'};
    var lower = String(text).toLowerCase();
    var out = '';
    for (var i = 0; i < lower.length; i++) {
        var ch = lower[i];
        out += map.hasOwnProperty(ch) ? map[ch] : ch;
    }
    return out.replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').replace(/-+/g, '-');
}
/** Связка "название → код" с кнопкой-цепочкой (как в 1С-Битрикс): пока цепочка
    включена (иконка активна), код генерируется автоматически из названия;
    клик по иконке или ручной ввод в самом поле кода отключает автогенерацию.
    У уже сохранённых сущностей цепочку нужно сразу разомкнуть — для этого
    вызывается codeChain.setExisting() после подстановки сохранённого slug. */
function bindCodeChain(nameSel, codeSel, chainBtnSel) {
    var auto = true;
    function updateBtn() { $(chainBtnSel).toggleClass('active', auto); }
    $(chainBtnSel).on('click', function () { auto = !auto; updateBtn(); });
    $(codeSel).on('input', function () {
        auto = false;
        updateBtn();
        // Код для ссылки — только латиница, цифры, тире и нижнее подчёркивание;
        // всё остальное (кириллицу, пробелы, спецсимволы) сразу вырезаем при вводе
        var cleaned = this.value.replace(/[^A-Za-z0-9_-]/g, '');
        if (cleaned !== this.value) {
            var pos = this.selectionStart - (this.value.length - cleaned.length);
            this.value = cleaned;
            this.setSelectionRange(pos, pos);
        }
    });
    $(nameSel).on('input', function () { if (auto) $(codeSel).val(slugifyClient($(this).val())); });
    return {
        setExisting: function () { auto = false; updateBtn(); },
        setNew: function () { auto = true; updateBtn(); }
    };
}
function getQueryParam(name) {
    var m = new RegExp('[?&]' + name + '=([^&]*)').exec(window.location.search);
    return m ? decodeURIComponent(m[1].replace(/\+/g, ' ')) : null;
}
/* ---------------- Валидация полей форм ----------------
   Достаточно повесить data-validate="text|phone|email|password" на инпут —
   остальное (маска телефона, подсказка снизу, подсветка рамки, проверка при
   вводе/потере фокуса) подключается автоматически на любой странице. */
var VALIDATORS = {
    text: function (val) { return val.trim().length >= 3; },
    phone: function (val) { return /^\+7 \d{3} \d{3}-\d{2}-\d{2}$/.test(val.trim()); },
    email: function (val) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val.trim()); },
    password: function (val) { return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-zА-Яа-я0-9\s]).{6,}$/.test(val); },
    slug: function (val) { return /^[A-Za-z0-9_-]+$/.test(val.trim()); }
};
var VALIDATION_HINTS = {
    text: '',
    phone: 'Формат: +7 999 123-45-67',
    email: 'Например: partner@company.ru',
    password: 'Заглавные и строчные буквы, минимум 1 цифра и 1 спецсимвол',
    slug: 'Латиница, цифры, тире и нижнее подчёркивание'
};
var VALIDATION_ERRORS = {
    text: 'Слишком коротко — минимум 3 символа',
    phone: 'Проверьте номер — формат +7 999 123-45-67',
    email: 'Некорректный e-mail',
    password: 'Нужны заглавная, строчная буквы, цифра и спецсимвол',
    slug: 'Только латиница, цифры, тире и нижнее подчёркивание'
};

/** Живая маска телефона +7 XXX XXX-XX-XX — форматирует по мере ввода */
function bindPhoneMask($el) {
    $el.on('focus', function () { if (!this.value) this.value = '+7 '; });
    $el.on('input', function () {
        var digits = this.value.replace(/\D/g, '');
        if (digits.charAt(0) === '7' || digits.charAt(0) === '8') digits = digits.slice(1);
        digits = digits.slice(0, 10);
        var out = '+7';
        if (digits.length > 0) out += ' ' + digits.slice(0, 3);
        if (digits.length >= 4) out += ' ' + digits.slice(3, 6);
        if (digits.length >= 7) out += '-' + digits.slice(6, 8);
        if (digits.length >= 9) out += '-' + digits.slice(8, 10);
        this.value = out;
    });
}

function initFormValidation(root) {
    $(root || document).find('[data-validate]').each(function () {
        var $el = $(this);
        if ($el.data('validationBound')) return;
        $el.data('validationBound', true);
        var type = $el.data('validate');
        if (!VALIDATORS[type]) return;

        var $hint = $('<div class="field-hint">' + (VALIDATION_HINTS[type] || '') + '</div>');
        // Если поле лежит внутри .input-group (например, код со значком цепочки),
        // подсказку нужно вставлять ПОСЛЕ всей группы, а не внутрь неё —
        // иначе она становится ещё одним flex-элементом в одном ряду с полем.
        var $groupParent = $el.closest('.input-group');
        if ($groupParent.length) { $groupParent.after($hint); } else { $el.after($hint); }
        if (type === 'phone') bindPhoneMask($el);

        function check() {
            var val = $el.val().trim();
            if (!val) { $el.removeClass('is-valid is-invalid'); $hint.attr('class', 'field-hint').text(VALIDATION_HINTS[type] || ''); return; }
            var ok = VALIDATORS[type](val);
            $el.toggleClass('is-valid', ok).toggleClass('is-invalid', !ok);
            $hint.attr('class', 'field-hint ' + (ok ? 'field-hint-ok' : 'field-hint-err'))
                .html(ok ? '<i class="bi bi-check-circle-fill"></i> Всё верно' : '<i class="bi bi-exclamation-circle-fill"></i> ' + (VALIDATION_ERRORS[type] || 'Неверное значение'));
        }
        $el.on('input', check);
        $el.on('blur', check);
    });

    // Повтор пароля — отдельная проверка совпадения со вторым полем
    $(root || document).find('[data-validate-match]').each(function () {
        var $el = $(this);
        if ($el.data('validationBound')) return;
        $el.data('validationBound', true);
        var $other = $($el.data('validate-match'));
        var $hint = $('<div class="field-hint">Повторите пароль</div>');
        $el.after($hint);
        function check() {
            var val = $el.val();
            if (!val) { $el.removeClass('is-valid is-invalid'); $hint.attr('class', 'field-hint').text('Повторите пароль'); return; }
            var ok = val === $other.val();
            $el.toggleClass('is-valid', ok).toggleClass('is-invalid', !ok);
            $hint.attr('class', 'field-hint ' + (ok ? 'field-hint-ok' : 'field-hint-err'))
                .html(ok ? '<i class="bi bi-check-circle-fill"></i> Пароли совпадают' : '<i class="bi bi-exclamation-circle-fill"></i> Пароли не совпадают');
        }
        $el.on('input', check);
        $el.on('blur', check);
        $other.on('input', check);
    });
}
$(function () { initFormValidation(); });

function statusPill(status) {
    var map = {
        active: ['Активен', 'pill-green'], hidden: ['Скрыт', 'pill-gray'],
        new: ['Новый', 'pill-blue'], processing: ['В обработке', 'pill-yellow'],
        confirmed: ['Подтверждён', 'pill-blue'],
        shipped: ['Отправлен', 'pill-blue'], completed: ['Выполнен', 'pill-green'],
        cancelled: ['Отменён', 'pill-red'], pending: ['На проверке', 'pill-yellow'],
        verified: ['Подтверждён', 'pill-green'], rejected: ['Отклонён', 'pill-red'],
        open: ['Открыт', 'pill-blue'], answered: ['Отвечен', 'pill-yellow'], closed: ['Закрыт', 'pill-gray'],
        income: ['Поступление', 'pill-green'], commission: ['Списание', 'pill-red'],
        refund: ['Возврат', 'pill-yellow'], withdrawal: ['Вывод', 'pill-blue']
    };
    var m = map[status] || [status, 'pill-gray'];
    return '<span class="pill ' + m[1] + '">' + m[0] + '</span>';
}

/** Загружает JSON через AJAX ($.getJSON — это и есть тот самый Ajax-слой прототипа) */
function loadJSON(path) {
    return $.getJSON(path).fail(function () {
        console.error('Не удалось загрузить данные: ' + path + ' (если открываете файл напрямую как file://, браузер блокирует такие запросы — запустите локальный сервер, см. README)');
    });
}

/* ---------------- Псевдо-БД на localStorage поверх исходных JSON ----------------
   Прототип без бэкенда: при первом обращении данные подгружаются AJAX'ом из
   data/*.json, дальше все изменения (добавление/редактирование/удаление)
   пишутся в localStorage — так формы "Сохранить/Удалить" по-настоящему
   работают и переживают переход между страницами и перезагрузку. */
/* Версия схемы демо-данных: увеличивайте при изменении структуры JSON,
   чтобы старые закешированные в localStorage данные автоматически
   заменялись свежими, а не "застревали" в старом формате у тех, кто уже
   открывал прототип. */
var CABINET_DATA_VERSION = 18;

function dsKey(entity) { return 'cabinet_ds_' + entity; }
function dsLoad(entity, jsonPath) {
    var cached = localStorage.getItem(dsKey(entity));
    if (cached) {
        try {
            var parsed = JSON.parse(cached);
            if (parsed && parsed.__v === CABINET_DATA_VERSION) {
                return $.Deferred().resolve(parsed.data).promise();
            }
        } catch (e) { /* испорченный кэш — перечитаем файл */ }
    }
    return loadJSON(jsonPath).then(function (data) {
        localStorage.setItem(dsKey(entity), JSON.stringify({__v: CABINET_DATA_VERSION, data: data}));
        return data;
    });
}
function dsSave(entity, data) {
    localStorage.setItem(dsKey(entity), JSON.stringify({__v: CABINET_DATA_VERSION, data: data}));
    return data;
}
function dsNextId(rows) {
    return rows.reduce(function (max, r) { return Math.max(max, r.id || 0); }, 0) + 1;
}
function dsReset(entity) { localStorage.removeItem(dsKey(entity)); }

/* ---------------- Текущий партнёр (мультитенантность) ----------------
   201 системных категорий (is_system: true) — общие для всех партнёров,
   их нельзя редактировать/удалять. Партнёр может добавлять свои категории
   в существующую структуру (is_system: false, partner_id: <его id>) —
   они видны только ему. Товары и новости тоже привязаны к partner_id.
   В прототипе без бэкенда "текущий партнёр" — фиксированный id (реальный
   логин/сессии здесь не моделируются), но вся фильтрация построена так,
   что при подключении бэкенда достаточно будет отдавать сюда id из сессии. */
var CURRENT_PARTNER_ID = 1;

function isSystemCategory(c) { return !!c.is_system; }
/** Категория видна текущему партнёру: системная ИЛИ принадлежит ему самому */
function isCategoryVisible(c) { return isSystemCategory(c) || c.partner_id === CURRENT_PARTNER_ID; }
/** Категории, доступные текущему партнёру (системные + свои, БЕЗ чужих) */
function visibleCategories(all) { return all.filter(isCategoryVisible); }
/** Партнёр может редактировать/удалять только свои категории, не системные */
function canEditCategory(c) { return !isSystemCategory(c) && c.partner_id === CURRENT_PARTNER_ID; }
/** Товары/новости — просто фильтр по своему partner_id */
function ownOnly(rows) { return rows.filter(function (r) { return r.partner_id === CURRENT_PARTNER_ID; }); }

/** Сохраняет отредактированный список СВОИХ товаров/новостей, не потеряв чужие
    партнёрские записи (аналог saveVisibleCategories, но для плоских сущностей
    без системных записей — просто фильтр по partner_id). */
function saveOwnRows(entity, jsonPath, ownRows, cb) {
    dsLoad(entity, jsonPath).done(function (full) {
        var foreign = full.filter(function (r) { return r.partner_id !== CURRENT_PARTNER_ID; });
        var saved = dsSave(entity, foreign.concat(ownRows));
        if (cb) cb(saved);
    });
}
/** Сохраняет отредактированный видимый список категорий (система+свои), не
    потеряв чужие партнёрские категории — подтягивает актуальный полный список
    из хранилища и подставляет в него только видимую часть. */
function saveVisibleCategories(visibleList, cb) {
    dsLoad('categories', 'data/categories.json').done(function (full) {
        var hidden = full.filter(function (c) { return !isCategoryVisible(c); });
        var saved = dsSave('categories', hidden.concat(visibleList));
        if (cb) cb(saved);
    });
}

/* ---------------- Дерево категорий (один родитель на категорию) ----------------
   Категория хранит parent_id: number — 0 = корневая категория. Простое дерево
   (не DAG): у категории ровно один родитель, поэтому дерево категорий отображается
   как обычный select. Множественная привязка есть только у ТОВАРОВ
   (product.category_ids[]) — для неё используется дерево на чекбоксах ниже. */
/** Сортировка категорий как на сайте (поле sort_order, шаг 100 — оставляет
    зазоры для вставки новых категорий между существующими), а не по алфавиту */
function categorySortCompare(a, b) {
    return (a.sort_order || 0) - (b.sort_order || 0);
}
function categoryDirectChildren(categories, parentId) {
    return categories.filter(function (c) { return (c.parent_id || 0) === parentId; });
}
function categoryRoots(categories) {
    return categories.filter(function (c) { return !(c.parent_id > 0); });
}
/** Все id потомков (BFS) */
function categoryDescendantIds(categories, id) {
    var result = [], queue = [id], visited = {};
    while (queue.length) {
        var cur = queue.pop();
        categoryDirectChildren(categories, cur).forEach(function (c) {
            if (!visited[c.id]) {
                visited[c.id] = true;
                result.push(c.id);
                queue.push(c.id);
            }
        });
    }
    return result;
}
/** Хлебная крошка "Родитель / Подкатегория / ..." */
function categoryBreadcrumb(categories, id) {
    var byId = {}; categories.forEach(function (c) { byId[c.id] = c; });
    var parts = [], guard = 0;
    while (id && byId[id] && guard < 20) {
        parts.unshift(byId[id].name);
        id = byId[id].parent_id || 0;
        guard++;
    }
    return parts.join(' / ');
}
/** Плоский список категорий в порядке дерева (с отступами) для <select> —
    excludeIds исключает категорию-саму-себя и её потомков (чтобы не вложить
    категорию саму в себя при выборе родителя). */
function buildCategoryTreeOptions(categories, excludeIds, maxDepth) {
    excludeIds = excludeIds || [];
    maxDepth = maxDepth || 12;
    var out = [];
    function pushNode(c, depth) {
        if (excludeIds.indexOf(c.id) !== -1) return;
        out.push({id: c.id, name: c.name, label: Array(depth + 1).join('— ') + c.name, depth: depth});
        if (depth < maxDepth) {
            categoryDirectChildren(categories, c.id)
                .sort(categorySortCompare)
                .forEach(function (child) { pushNode(child, depth + 1); });
        }
    }
    categoryRoots(categories).sort(categorySortCompare).forEach(function (c) { pushNode(c, 0); });
    return out;
}

/**
 * Дерево категорий на чекбоксах (для выбора родителя категории и категорий товара).
 * При отметке узла каскадно отмечаются/снимаются все его потомки в этом же дереве —
 * так и для родителя категории, и для категорий товара работает единый компонент.
 * excludeIds — категории, которые не должны предлагаться (сама категория + её потомки).
 * Сверху добавляется поле поиска по названию — чтобы не листать весь список
 * (в каталоге больше 200 категорий), при поиске показывается плоский список
 * совпадений с полным путём вместо дерева.
 */
function renderCategoryCheckboxTree(containerSel, categories, selectedIds, excludeIds) {
    excludeIds = excludeIds || [];
    var $c = $(containerSel).addClass('cbtree');

    // Состояние выбора храним отдельно от DOM — иначе при переключении между
    // деревом и результатами поиска потерялись бы отметки на скрытых узлах.
    var selected = {};
    (selectedIds || []).forEach(function (id) { selected[id] = true; });
    $c.data('cbtreeSelected', selected);

    var $search = $c.prev('.cbtree-search');
    if ($search.length === 0) {
        $search = $('<input type="text" class="form-control form-control-sm mb-2 cbtree-search" placeholder="Поиск по названию категории…" autocomplete="off">');
        $c.before($search);
    }

    function nodeHtml(c, depth) {
        var children = categoryDirectChildren(categories, c.id)
            .filter(function (ch) { return excludeIds.indexOf(ch.id) === -1; })
            .sort(categorySortCompare);
        var checked = !!selected[c.id];
        var html = '<div class="cbtree-node" style="padding-left:' + (depth * 20) + 'px;">' +
            '<label class="cbtree-label"><input type="checkbox" class="cbtree-check" data-id="' + c.id + '" ' + (checked ? 'checked' : '') + '> ' + esc(c.name) + '</label>' +
            '</div>';
        children.forEach(function (ch) { html += nodeHtml(ch, depth + 1); });
        return html;
    }

    function matchHtml(c) {
        var checked = !!selected[c.id];
        return '<div class="cbtree-node">' +
            '<label class="cbtree-label"><input type="checkbox" class="cbtree-check" data-id="' + c.id + '" ' + (checked ? 'checked' : '') + '> ' +
            esc(c.name) + ' <span class="text-muted-2 small">— ' + esc(categoryBreadcrumb(categories, c.parent_id) || 'корневая') + '</span></label>' +
            '</div>';
    }

    function render(query) {
        var html;
        if (query) {
            var ql = query.toLowerCase();
            var matches = categories.filter(function (c) {
                return excludeIds.indexOf(c.id) === -1 && c.name.toLowerCase().indexOf(ql) !== -1;
            }).sort(categorySortCompare);
            html = matches.length
                ? matches.map(matchHtml).join('')
                : '<div class="cbtree-empty">Ничего не найдено</div>';
        } else {
            var roots = categoryRoots(categories).filter(function (c) { return excludeIds.indexOf(c.id) === -1; })
                .sort(categorySortCompare);
            html = roots.map(function (c) { return nodeHtml(c, 0); }).join('') || '<div class="cbtree-empty">Нет доступных категорий</div>';
        }
        $c.html(html);
    }

    $search.off('input.cbtree').on('input.cbtree', function () { render($(this).val().trim()); });

    $c.off('change.cbtree').on('change.cbtree', '.cbtree-check', function () {
        var id = parseInt($(this).data('id'), 10);
        var isChecked = $(this).is(':checked');
        var affected = [id].concat(categoryDescendantIds(categories, id));
        affected.forEach(function (aid) { selected[aid] = isChecked; });
        // Перерисовываем текущий вид (дерево или результаты поиска), чтобы
        // каскадная отметка потомков была видна сразу, если они сейчас показаны.
        render($search.val().trim());
    });

    render('');
}
function getCheckboxTreeSelected(containerSel) {
    var selected = $(containerSel).data('cbtreeSelected') || {};
    return Object.keys(selected).filter(function (id) { return selected[id]; }).map(Number);
}

/**
 * То же дерево с поиском, но для полей с ОДНИМ родителем (радиокнопки вместо
 * чекбоксов, без каскадного выбора потомков — просто выбор одного пункта).
 * Используется для поля "Родительский раздел" у категории.
 */
function renderCategoryRadioTree(containerSel, categories, selectedId, excludeIds) {
    excludeIds = excludeIds || [];
    var $c = $(containerSel).addClass('cbtree');
    $c.data('cbtreeSelectedRadio', selectedId || 0);

    var $search = $c.prev('.cbtree-search');
    if ($search.length === 0) {
        $search = $('<input type="text" class="form-control form-control-sm mb-2 cbtree-search" placeholder="Поиск по названию категории…" autocomplete="off">');
        $c.before($search);
    }
    var groupName = $c.data('cbtreeRadioGroup') || ('catRadio' + Math.random().toString(36).slice(2));
    $c.data('cbtreeRadioGroup', groupName);

    function rootOptionHtml() {
        var checked = ($c.data('cbtreeSelectedRadio') || 0) === 0;
        return '<div class="cbtree-node"><label class="cbtree-label"><input type="radio" name="' + groupName + '" class="cbtree-radio" data-id="0" ' + (checked ? 'checked' : '') + '> — Нет, корневая категория —</label></div>';
    }
    function nodeHtml(c, depth) {
        var children = categoryDirectChildren(categories, c.id)
            .filter(function (ch) { return excludeIds.indexOf(ch.id) === -1; })
            .sort(categorySortCompare);
        var checked = ($c.data('cbtreeSelectedRadio') || 0) === c.id;
        var html = '<div class="cbtree-node" style="padding-left:' + (depth * 20) + 'px;">' +
            '<label class="cbtree-label"><input type="radio" name="' + groupName + '" class="cbtree-radio" data-id="' + c.id + '" ' + (checked ? 'checked' : '') + '> ' + esc(c.name) + '</label></div>';
        children.forEach(function (ch) { html += nodeHtml(ch, depth + 1); });
        return html;
    }
    function matchHtml(c) {
        var checked = ($c.data('cbtreeSelectedRadio') || 0) === c.id;
        return '<div class="cbtree-node">' +
            '<label class="cbtree-label"><input type="radio" name="' + groupName + '" class="cbtree-radio" data-id="' + c.id + '" ' + (checked ? 'checked' : '') + '> ' +
            esc(c.name) + ' <span class="text-muted-2 small">— ' + esc(categoryBreadcrumb(categories, c.parent_id) || 'корневая') + '</span></label>' +
            '</div>';
    }
    function render(query) {
        var html;
        if (query) {
            var ql = query.toLowerCase();
            var matches = categories.filter(function (c) {
                return excludeIds.indexOf(c.id) === -1 && c.name.toLowerCase().indexOf(ql) !== -1;
            }).sort(categorySortCompare);
            html = matches.length ? matches.map(matchHtml).join('') : '<div class="cbtree-empty">Ничего не найдено</div>';
        } else {
            var roots = categoryRoots(categories).filter(function (c) { return excludeIds.indexOf(c.id) === -1; }).sort(categorySortCompare);
            html = rootOptionHtml() + roots.map(function (c) { return nodeHtml(c, 0); }).join('');
        }
        $c.html(html);
    }

    $search.off('input.cbtree').on('input.cbtree', function () { render($(this).val().trim()); });
    $c.off('change.cbtreeRadio').on('change.cbtreeRadio', '.cbtree-radio', function () {
        $c.data('cbtreeSelectedRadio', parseInt($(this).data('id'), 10));
    });

    render('');
}
function getCategoryRadioSelected(containerSel) {
    return $(containerSel).data('cbtreeSelectedRadio') || 0;
}

/** Автоматически растягивает textarea/поле ввода по высоте контента (до max-height из CSS) */
function bindAutoHeight(sel) {
    $(document).on('input', sel, function () { autoHeightResize(this); });
    $(sel).each(function () { autoHeightResize(this); });
}
/** Пересчитать высоту поля вручную (например, после того как контейнер с
    полем был показан из display:none — пока элемент скрыт, scrollHeight
    равен 0, и авто-высота "залипает" маленькой до первого ввода текста). */
function autoHeightResize(sel) {
    $(sel).each(function () {
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';
    });
}

/** Инициализация rich-text редактора (Trumbowyg) на textarea — общая точка входа,
    чтобы при переносе в другую систему (например, в модуль 1С-Битрикс) достаточно
    было поменять реализацию только этой функции. */
/** Регистрируем свои текстовые кнопки форматирования блоков (абзац/заголовки) —
    в базовой поставке Trumbowyg их нет. Регистрация должна быть выполнена один
    раз, до первого вызова .trumbowyg(), поэтому делаем это здесь же, при загрузке
    common.js (которая всегда подключается ПОСЛЕ trumbowyg.min.js на страницах,
    где редактор используется). */
if (window.jQuery && $.trumbowyg && !$.trumbowyg.btnsDef.formatP) {
    var blockBtn = function (tag, label, title) {
        return {
            fn: function (trumbowyg) { trumbowyg.execCmd('formatBlock', tag, true, true); },
            text: label,
            hasIcon: false,
            title: title
        };
    };
    $.trumbowyg.btnsDef.formatP = blockBtn('p', 'Обычный текст', 'Обычный текст');
    $.trumbowyg.btnsDef.formatH1 = blockBtn('h1', 'Заголовок 1', 'Заголовок 1 уровня');
    $.trumbowyg.btnsDef.formatH2 = blockBtn('h2', 'Заголовок 2', 'Заголовок 2 уровня');
    $.trumbowyg.btnsDef.formatH3 = blockBtn('h3', 'Заголовок 3', 'Заголовок 3 уровня');
    $.trumbowyg.btnsDef.formatH4 = blockBtn('h4', 'Заголовок 4', 'Заголовок 4 уровня');
    $.trumbowyg.btnsDef.formatQuote = blockBtn('blockquote', 'Цитата', 'Цитата');
    // Группируем все варианты формата текста в один выпадающий список в панели
    $.trumbowyg.btnsDef.formatting = {
        dropdown: ['formatP', 'formatH1', 'formatH2', 'formatH3', 'formatH4', 'formatQuote'],
        hasIcon: false,
        text: 'Формат',
        title: 'Формат текста (заголовки, абзац, цитата)'
    };
}

function initRichText(sel) {
    if (!$.fn.trumbowyg) { return; }
    $(sel).trumbowyg({
        lang: 'ru',
        btns: [
            ['viewHTML'],
            ['formatting'],
            ['bold', 'italic', 'underline'],
            ['unorderedList', 'orderedList'],
            ['link'],
            ['removeformat']
        ],
        removeformatPasted: true
    });
}

/** Читает файл и превращает в объект вложения {name, type, data} — используется
    в чате с клиентами и в техподдержке для превью прикреплённых файлов/картинок */
function attachmentFromFile(file, cb) {
    if (!file) { cb(null); return; }
    var reader = new FileReader();
    reader.onload = function (e) {
        cb({name: file.name, type: file.type.indexOf('image') === 0 ? 'image' : 'file', data: e.target.result});
    };
    reader.readAsDataURL(file);
}

/** Читает выбранный файл как data URL (без сервера) и показывает превью —
    используется всеми формами с картинками/галереями в прототипе */
/** Ставит картинку как background-image элементу (используется вместо <img src>
    во всех превью/детальных/галерейных блоках — п.14: "все изображения — div с
    фоном на 4:3", чтобы вёрстка была единообразной. */
function setBgImage(sel, url) {
    $(sel).css('background-image', url ? "url('" + String(url).replace(/'/g, "%27") + "')" : 'none');
}
/** HTML div-блока с фоновой картинкой — для рендера в списках/таблицах.
    cls — размерный класс (thumb-sm/thumb-detail/...). */
function bgThumbHtml(url, cls, extra) {
    var safeUrl = (url || 'https://placehold.co/240x180?text=%20').replace(/'/g, "%27");
    return '<div class="' + cls + ' bg-thumb" style="background-image:url(\'' + safeUrl + '\');" ' + (extra || '') + '></div>';
}

function bindImagePreview(fileInputSel, imgSel, onLoaded) {
    $(document).on('change', fileInputSel, function () {
        var file = this.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function (e) {
            setBgImage(imgSel, e.target.result);
            if (onLoaded) onLoaded(e.target.result);
        };
        reader.readAsDataURL(file);
    });
}
/** Кнопка "Удалить фото" рядом с превью/детальной картинкой — сбрасывает
    картинку на плейсхолдер и очищает сохранённое значение */
function bindImageRemove(btnSel, imgSel, placeholderUrl, onRemoved) {
    $(document).on('click', btnSel, function () {
        setBgImage(imgSel, placeholderUrl);
        if (onRemoved) onRemoved('');
    });
}

/** Простой debounce — для поисковых полей (глобальный поиск, товар в заказе) */
function debounce(fn, delay) {
    var timer = null;
    return function () {
        var args = arguments, ctx = this;
        clearTimeout(timer);
        timer = setTimeout(function () { fn.apply(ctx, args); }, delay);
    };
}

/** Единое поле "Период" с выбором одной даты ИЛИ диапазона (flatpickr).
    Возвращает объект с методом getRange() -> {from: Date|null, to: Date|null}. */
function initDateRangePicker(sel, onChange) {
    if (!window.flatpickr) return {getRange: function () { return {from: null, to: null}; }, clear: function () {}};
    var fp = flatpickr(sel, {
        mode: 'range',
        dateFormat: 'd.m.Y',
        locale: (flatpickr.l10ns && flatpickr.l10ns.ru) ? 'ru' : undefined,
        onChange: function () { if (onChange) onChange(); },
        // При закрытии с незавершённым диапазоном (выбрана только одна дата из двух)
        // flatpickr иногда сам стирает отображаемое значение поля — восстанавливаем его.
        onClose: function (selectedDates, dateStr, instance) {
            if (selectedDates.length === 1) {
                instance.input.value = instance.formatDate(selectedDates[0], 'd.m.Y');
            }
        }
    });
    return {
        getRange: function () {
            var dates = fp.selectedDates || [];
            if (dates.length === 0) return {from: null, to: null};
            if (dates.length === 1) {
                var d = new Date(dates[0]);
                var end = new Date(d); end.setHours(23, 59, 59, 999);
                return {from: d, to: end};
            }
            var to = new Date(dates[1]); to.setHours(23, 59, 59, 999);
            return {from: dates[0], to: to};
        },
        clear: function () { fp.clear(); }
    };
}

/** Одиночный date-picker (не диапазон) — для полей "Действует с/по" у скидок
    и купонов. Возвращает сам flatpickr-инстанс, чтобы можно было вызвать
    setDate() при подстановке уже сохранённого значения. */
function initSingleDatePicker(sel) {
    if (!window.flatpickr) return null;
    return flatpickr(sel, {
        dateFormat: 'd.m.Y',
        locale: (flatpickr.l10ns && flatpickr.l10ns.ru) ? 'ru' : undefined
    });
}
/** "31.12.2026" -> "2026-12-31" (для хранения в данных) */
function isoFromRuDate(ruDate) {
    var parts = ruDate.split('.');
    if (parts.length !== 3) return '';
    return parts[2] + '-' + parts[1] + '-' + parts[0];
}

/** Кнопка "Сбросить" рядом с поиском в фильтрах: видна только если что-то
    заполнено/выбрано, по клику сбрасывает поля и перерисовывает список.
    watchSel — селектор всех полей фильтра на странице (для отслеживания
    заполненности и появления/скрытия кнопки), resetFn — что делать при сбросе
    (очистить поля, включая нестандартные виджеты типа flatpickr, и вызвать рендер). */
function bindClearFilters(clearBtnSel, watchSel, resetFn) {
    var $btn = $(clearBtnSel);
    var defaults = {};
    $(watchSel).each(function () { defaults[this.id || Math.random()] = $(this).val(); $(this).data('__default', $(this).val()); });
    function hasValue() {
        var active = false;
        $(watchSel).each(function () {
            var v = $(this).val();
            var def = $(this).data('__default');
            var isArr = Array.isArray(v);
            var changed = isArr ? (v.join() !== (def || []).join()) : (String(v || '') !== String(def || ''));
            if (changed) active = true;
        });
        return active;
    }
    function refresh() { $btn.toggleClass('show', hasValue()); }
    $(document).on('input change', watchSel, refresh);
    $btn.on('click', function () { resetFn(); refresh(); });
    refresh();
    return refresh;
}

/**
 * Футер таблицы: пагинация + выбор количества строк на странице (20/50/100/500,
 * запоминается в localStorage). .table-footer-bar лежит СОСЕДОМ с .table-responsive
 * внутри .card-body (НЕ в <tfoot> — иначе overflow-x таблицы ломает sticky футера):
 *   <div class="card"><div class="card-body p-0">
 *     <div class="table-responsive"><table id="myTable">…</table></div>
 *     <div class="table-footer-bar">
 *       <div class="tf-left">…кнопка "Удалить выбранные" и счётчик…</div>
 *       <div class="tf-right">
 *         <div class="tf-pagesize">Показывать <select class="tf-pagesize-select">…</select></div>
 *         <div class="tf-pager"></div>
 *       </div>
 *     </div>
 *   </div></div>
 * opts: {root: '#myTable', renderFn: function(pageRows){...}, storageKey: 'products_pagesize', countSel: '#tfCount'}
 */
function initTablePager(opts) {
    var $root = $(opts.root);
    // Футер таблицы (.tf-*) вынесен из <tfoot> в соседний .table-footer-bar внутри
    // .card — поэтому ищем контролы в рамках карточки, а не самой таблицы.
    var $scope = $root.closest('.card');
    if (!$scope.length) $scope = $root;
    var $pageSizeSelect = $scope.find('.tf-pagesize-select');
    var $pager = $scope.find('.tf-pager');
    var $count = opts.countSel ? $(opts.countSel) : $scope.find('.tf-count');
    var pageSize = parseInt(localStorage.getItem(opts.storageKey) || '20', 10);
    var currentPage = 1;
    var allRows = [];

    $pageSizeSelect.val(String(pageSize));
    $pageSizeSelect.on('change', function () {
        pageSize = parseInt($(this).val(), 10) || 20;
        localStorage.setItem(opts.storageKey, String(pageSize));
        currentPage = 1;
        render();
    });

    function totalPages() { return Math.max(1, Math.ceil(allRows.length / pageSize)); }

    function render() {
        var tp = totalPages();
        currentPage = Math.min(Math.max(1, currentPage), tp);
        var start = (currentPage - 1) * pageSize;
        opts.renderFn(allRows.slice(start, start + pageSize));

        if ($count.length) {
            var from = allRows.length ? start + 1 : 0;
            var to = Math.min(start + pageSize, allRows.length);
            $count.text(allRows.length ? ('Показано ' + from + '–' + to + ' из ' + allRows.length) : 'Ничего не найдено');
        }

        $pager.empty();
        if (tp <= 1) return;
        function btn(label, page, disabled, active) {
            return '<button type="button" class="pager-btn' + (active ? ' active' : '') + '" ' +
                (disabled ? 'disabled' : ('data-page="' + page + '"')) + '>' + label + '</button>';
        }
        var CHEVRON_LEFT = '<svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"></path></svg>';
        var CHEVRON_RIGHT = '<svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"></path></svg>';
        var html = btn(CHEVRON_LEFT, currentPage - 1, currentPage === 1);
        var windowSize = 5;
        var startP = Math.max(1, Math.min(currentPage - 2, tp - windowSize + 1));
        startP = Math.max(1, startP);
        var endP = Math.min(tp, startP + windowSize - 1);
        for (var i = startP; i <= endP; i++) html += btn(String(i), i, false, i === currentPage);
        html += btn(CHEVRON_RIGHT, currentPage + 1, currentPage === tp);
        $pager.html(html);
        $pager.find('.pager-btn[data-page]').on('click', function () {
            currentPage = parseInt($(this).data('page'), 10);
            render();
        });
    }

    return {
        setRows: function (rows) { allRows = rows; currentPage = 1; render(); },
        refresh: render
    };
}

/* ---------------- Модалки подтверждения / результата ---------------- */
function showResult(success, message) {
    var $modal = $('#resultModal');
    if ($modal.length === 0) { alert(message); return; }
    $modal.find('.modal-header').toggleClass('bg-success text-white', !!success).toggleClass('bg-danger text-white', !success);
    $modal.find('.result-icon').html(success ? '<i class="bi bi-check-circle-fill"></i>' : '<i class="bi bi-x-circle-fill"></i>');
    $modal.find('.result-title').text(success ? 'Готово' : 'Ошибка');
    $modal.find('.result-message').text(message || (success ? 'Действие выполнено успешно.' : 'Что-то пошло не так.'));
    bootstrap.Modal.getOrCreateInstance($modal[0]).show();
}
function showConfirm(message, onConfirm, opts) {
    opts = opts || {};
    var $modal = $('#confirmModal');
    if ($modal.length === 0) { if (confirm(message)) onConfirm(); return; }
    $modal.find('.confirm-message').text(message);
    $modal.find('.confirm-title').text(opts.title || 'Подтвердите действие');
    var $btn = $modal.find('.confirm-ok-btn');
    $btn.toggleClass('btn-danger', !!opts.danger).toggleClass('btn-primary', !opts.danger);
    $btn.text(opts.okText || 'Подтвердить');
    $btn.off('click.confirmModal').on('click.confirmModal', function () {
        bootstrap.Modal.getOrCreateInstance($modal[0]).hide();
        onConfirm();
    });
    bootstrap.Modal.getOrCreateInstance($modal[0]).show();
}

/* ---------------- Чекбокс "выбрать все" в таблицах ---------------- */
function bindSelectAll(headCheckboxSel, rowCheckboxSel) {
    $(document).on('change', headCheckboxSel, function () {
        $(rowCheckboxSel).prop('checked', $(this).is(':checked')).trigger('change');
    });
    $(document).on('change', rowCheckboxSel, function () {
        var total = $(rowCheckboxSel).length;
        var checked = $(rowCheckboxSel + ':checked').length;
        $(headCheckboxSel).prop('checked', total > 0 && total === checked);
    });
}
function getSelectedIds(rowCheckboxSel) {
    return $(rowCheckboxSel + ':checked').map(function () { return $(this).val(); }).get();
}

/* ---------------- Партиалы: сайдбар / шапка / футер (через AJAX) ---------------- */
function loadPartials(activeKey, pageTitle) {
    if ($('#sidebarBackdrop').length === 0) {
        $('body').append('<div class="sidebar-backdrop" id="sidebarBackdrop"></div>');
        $('#sidebarBackdrop').on('click', closeMobileSidebar);
    }
    $('#sidebarPlaceholder').load('partials/sidebar.html', function () {
        $('.side-link[data-key="' + activeKey + '"]').addClass('active');
        $('#sidebarCloseBtn').on('click', closeMobileSidebar);
        $('.sidebar .side-link').on('click', closeMobileSidebar);
        refreshSidebarCounts();
    });
    $('#topbarPlaceholder').load('partials/topbar.html', function () {
        $('#topbarTitle').text(pageTitle || '');
        document.title = (pageTitle ? pageTitle + ' — ' : '') + 'Formaro Partner';
        initTopbarWidgets();
        refreshTopbarCounts();
    });
    $('#footerPlaceholder').load('partials/footer.html', function () {
        $('.footer-year').text(new Date().getFullYear());
    });
}

function initTopbarWidgets() {
    setThemeMode(getThemeMode());
    $('#themeToggle').on('click', function () {
        var next = THEME_ORDER[(THEME_ORDER.indexOf(getThemeMode()) + 1) % THEME_ORDER.length];
        setThemeMode(next);
    });
    $('#accountBtn').on('click', function (e) {
        e.stopPropagation();
        if (window.innerWidth < 768) {
            window.location.href = 'partner.html';
            return;
        }
        $('#accountDropdown').toggleClass('show');
    });
    $(document).on('click', function () { $('#accountDropdown').removeClass('show'); });
    $('#sidebarToggle').on('click', function () { openMobileSidebar(); });
    initGlobalSearch();
    loadAccountWidget();
}

/** Заполняет блок аккаунта в шапке реальными данными партнёра (имя, аватар,
    значок верификации) — раньше было захардкожено статичным текстом. */
function loadAccountWidget() {
    dsLoad('partner', 'data/partner.json').done(function (p) {
        var name = p.name_short || p.name_full || 'Партнёр';
        $('#accNameText').text(name);
        var $avatar = $('#accountAvatar');
        if (p.logo) {
            $avatar.removeClass('avatar-initials').css('background-image', 'url(' + p.logo + ')').text('');
        } else {
            var initials = name.trim().slice(0, 2).toUpperCase();
            $avatar.addClass('avatar-initials').css('background-image', 'none').text(initials);
        }
        $('#accVerifiedIcon').toggle(p.verification_status === 'verified');
    });
}

function openMobileSidebar() {
    $('.sidebar').addClass('show');
    $('#sidebarBackdrop').addClass('show');
}
function closeMobileSidebar() {
    $('.sidebar').removeClass('show');
    $('#sidebarBackdrop').removeClass('show');
}

/* ---------------- Глобальный поиск в шапке (Ajax по всем сущностям) ---------------- */
function initGlobalSearch() {
    var $input = $('#globalSearch');
    var $results = $('#globalSearchResults');
    if ($input.length === 0) return;

    $input.on('input', debounce(function () {
        var q = $(this).val().trim();
        if (q.length < 3) { $results.removeClass('show').empty(); return; }
        runGlobalSearch(q, $results);
    }, 250));

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.topbar-search').length) $results.removeClass('show');
    });
    $input.on('focus', function () { if ($results.children().length) $results.addClass('show'); });
}

function runGlobalSearch(q, $results) {
    var ql = q.toLowerCase();
    var groups = [];

    $.when(
        dsLoad('categories', 'data/categories.json'),
        dsLoad('products', 'data/products.json'),
        dsLoad('orders', 'data/orders.json'),
        dsLoad('news', 'data/news.json'),
        dsLoad('discounts', 'data/discounts.json')
    ).done(function (categories, products, orders, news, discountsData) {
        var discounts = ownOnly(discountsData.discounts || []);
        var coupons = ownOnly(discountsData.coupons || []);

        var catMatches = categories.filter(function (c) { return c.name.toLowerCase().indexOf(ql) !== -1; }).slice(0, 5);
        var prodMatches = products.filter(function (p) { return p.name.toLowerCase().indexOf(ql) !== -1 || (p.sku || '').toLowerCase().indexOf(ql) !== -1; }).slice(0, 5);
        var orderMatches = orders.filter(function (o) { return o.order_number.toLowerCase().indexOf(ql) !== -1 || o.customer.name.toLowerCase().indexOf(ql) !== -1; }).slice(0, 5);
        var newsMatches = news.filter(function (n) { return n.title.toLowerCase().indexOf(ql) !== -1; }).slice(0, 5);
        var discountMatches = discounts.filter(function (d) { return d.name.toLowerCase().indexOf(ql) !== -1; }).slice(0, 5);
        var couponMatches = coupons.filter(function (c) { return c.code.toLowerCase().indexOf(ql) !== -1; }).slice(0, 5);

        if (catMatches.length) groups.push({title: 'Категории', icon: GSEARCH_ICONS.categories, items: catMatches.map(function (c) { return {label: c.name, url: 'products.html?category_id=' + c.id}; })});
        if (prodMatches.length) groups.push({title: 'Товары', icon: GSEARCH_ICONS.products, items: prodMatches.map(function (p) { return {label: p.name, sub: p.sku, url: 'product-detail.html?id=' + p.id}; })});
        if (orderMatches.length) groups.push({title: 'Заказы', icon: GSEARCH_ICONS.orders, items: orderMatches.map(function (o) { return {label: o.order_number + ' — ' + o.customer.name, url: 'order-detail.html?id=' + o.id}; })});
        if (newsMatches.length) groups.push({title: 'Новости', icon: GSEARCH_ICONS.news, items: newsMatches.map(function (n) { return {label: n.title, url: 'news-detail.html?id=' + n.id}; })});
        if (discountMatches.length) groups.push({title: 'Скидки', icon: GSEARCH_ICONS.discounts, items: discountMatches.map(function (d) { return {label: d.name, url: 'discount-detail.html?id=' + d.id}; })});
        if (couponMatches.length) groups.push({title: 'Купоны', icon: GSEARCH_ICONS.discounts, items: couponMatches.map(function (c) { return {label: c.code, url: 'coupon-detail.html?id=' + c.id}; })});

        renderGlobalSearchResults(groups, $results);
    });
}

var GSEARCH_ICONS = {
    categories: '<svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-2.5a1 1 0 0 1-.8-.4l-.9-1.2A1 1 0 0 0 15 3h-2a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1Z"></path><path d="M20 21a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1h-2.9a1 1 0 0 1-.88-.55l-.42-.85a1 1 0 0 0-.92-.6H13a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1Z"></path><path d="M3 5a2 2 0 0 0 2 2h3"></path><path d="M3 3v13a2 2 0 0 0 2 2h3"></path></svg>',
    products: '<svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"></path><path d="M12 22V12"></path><polyline points="3.29 7 12 12 20.71 7"></polyline><path d="m7.5 4.27 9 5.15"></path></svg>',
    orders: '<svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2.05 2.05 1.099-.028a1 1 0 0 1 1.008.815l2.69 14.347A1 1 0 0 0 7.83 18H18"></path><path d="M4.563 5h16.435a1 1 0 0 1 .981 1.204l-1.026 6.226A2 2 0 0 1 18.962 14H6.25"></path><circle cx="18" cy="20" r="2"></circle><circle cx="8" cy="20" r="2"></circle></svg>',
    news: '<svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18h-5"></path><path d="M18 14h-8"></path><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-4 0v-9a2 2 0 0 1 2-2h2"></path><rect width="8" height="4" x="10" y="6" rx="1"></rect></svg>',
    discounts: '<svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" x2="5" y1="5" y2="19"></line><circle cx="6.5" cy="6.5" r="2.5"></circle><circle cx="17.5" cy="17.5" r="2.5"></circle></svg>'
};
function renderGlobalSearchResults(groups, $results) {
    if (groups.length === 0) {
        $results.html('<div class="gsearch-empty">Ничего не найдено</div>').addClass('show');
        return;
    }
    var html = '';
    groups.forEach(function (g) {
        html += '<div class="gsearch-group"><div class="gsearch-group-title">' + (g.icon || '') + ' ' + g.title + '</div>';
        g.items.forEach(function (it) {
            html += '<a class="gsearch-item" href="' + it.url + '"><span>' + esc(it.label) + (it.sub ? ' <span class="text-muted-2">· ' + esc(it.sub) + '</span>' : '') + '</span></a>';
        });
        html += '</div>';
    });
    $results.html(html).addClass('show');
}

function refreshSidebarCounts() {
    dsLoad('orders', 'data/orders.json').done(function (rows) {
        var n = rows.filter(function (o) { return o.status === 'new'; }).length;
        $('.side-count[data-count="orders-new"]').text(n).toggleClass('attention', n > 0).toggle(n > 0);
    });
    dsLoad('notifications', 'data/notifications.json').done(function (rows) {
        var n = rows.filter(function (r) { return !r.is_read; }).length;
        $('.side-count[data-count="notifications"]').text(n).toggleClass('attention', n > 0).toggle(n > 0);
    });
    dsLoad('chat', 'data/chat.json').done(function (threads) {
        var n = threads.reduce(function (sum, t) {
            return sum + t.messages.filter(function (m) { return m.sender === 'client' && m.is_read === false; }).length;
        }, 0);
        $('.side-count[data-count="chat"]').text(n).toggleClass('attention', n > 0).toggle(n > 0);
    });
    dsLoad('tickets', 'data/tickets.json').done(function (data) {
        var n = data.tickets.filter(function (t) { return t.status !== 'closed'; }).length;
        $('.side-count[data-count="support"]').text(n).toggleClass('attention', n > 0).toggle(n > 0);
    });
    dsLoad('finance', 'data/finance.json').done(function (data) {
        $('#sidebarBalanceValue').text(fmtMoney(data.available_balance));
    });
}

function refreshTopbarCounts() {
    dsLoad('notifications', 'data/notifications.json').done(function (rows) {
        var n = rows.filter(function (r) { return !r.is_read; }).length;
        $('#notifDot').text(n).toggle(n > 0);
    });
    dsLoad('chat', 'data/chat.json').done(function (threads) {
        var n = threads.reduce(function (sum, t) {
            return sum + t.messages.filter(function (m) { return m.sender === 'client' && m.is_read === false; }).length;
        }, 0);
        $('#chatDot').text(n).toggle(n > 0);
    });
    dsLoad('tickets', 'data/tickets.json').done(function (data) {
        var n = data.tickets.filter(function (t) { return t.status !== 'closed'; }).length;
        $('#supportDot').text(n).toggle(n > 0);
    });
}
