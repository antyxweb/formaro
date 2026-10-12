/* Импорт товаров из файла (YML/XLSX/CSV): загрузка → сопоставление категорий →
   запись порциями (ProductImportService на сервере). */
var CABINET_URL = window.CABINET_BOOTSTRAP.cabinetUrl;
var SKIP_CATEGORY = -1;

var importSource = 'file';
var importData = null;   // ответ import_prepare
var importStopped = false;
var importRunning = false;
var itemsPager = null;

$(function () {
    $('[data-src]').on('shown.bs.tab', function () { importSource = $(this).data('src'); });
    itemsPager = initTablePager({root: '#itemsCard', renderFn: renderItemsPage, storageKey: 'pagesize_import'});
    $('#itemsSearch').on('input', debounce(renderItems, 200));
    $('#itemsFilter').on('change', renderItems);
    $('#optUpdate').on('change', function () { if (importData) itemsPager.refresh(); });
    $('#parseBtn').on('click', parseFile);
    $('#importUrl').on('keydown', function (e) { if (e.key === 'Enter') parseFile(); });
    $('#importFile').on('change', function () { if (this.files.length) parseFile(); });
    $('#cancelBtn, #againBtn').on('click', resetImport);
    $('#runBtn').on('click', startImport);
    $('#stopBtn').on('click', function () { importStopped = true; $(this).prop('disabled', true).text('Останавливаем…'); });
    $('#csvTemplateBtn').on('click', function (e) { e.preventDefault(); downloadCsvTemplate(); });
    $('#xlsxTemplateBtn').on('click', function (e) { e.preventDefault(); downloadXlsxTemplate(); });
    $('#xmlTemplateBtn').on('click', function (e) { e.preventDefault(); downloadXmlTemplate(); });
    $(window).on('beforeunload', function () { if (importRunning) return 'Импорт ещё идёт'; });
});

function parseFile() {
    var fd = new FormData();
    fd.append('ajax_action', 'import_prepare');
    if (window.BX && BX.bitrix_sessid) fd.append('sessid', BX.bitrix_sessid());
    if (importSource === 'file') {
        var file = $('#importFile')[0].files[0];
        if (!file) { showResult(false, 'Выберите файл'); return; }
        fd.append('file', file);
    } else {
        var url = $('#importUrl').val().trim();
        if (!/^https?:\/\//i.test(url)) { showResult(false, 'Укажите ссылку вида https://…'); return; }
        fd.append('url', url);
    }

    // У файла кнопка — <label> с input внутри: меняем только текст, input не трогаем.
    var $btn = importSource === 'file' ? $('#importFileBtn') : $('#parseBtn');
    var $text = $btn.contents().filter(function () { return this.nodeType === 3 && this.nodeValue.trim(); }).first();
    var label = $text.length ? $text[0].nodeValue : '';
    var $svg = $btn.children('svg').addClass('d-none');
    $btn.addClass('disabled').prop('disabled', true).prepend('<span class="spinner-border spinner-border-sm import-spinner"></span>');
    if ($text.length) $text[0].nodeValue = ' Читаем файл…';

    $.when(
        $.ajax({url: CABINET_URL, method: 'POST', data: fd, processData: false, contentType: false, dataType: 'json'}),
        dsLoad('categories')
    ).then(function (prepared, categories) {
        var resp = prepared[0];
        if (!resp || !resp.success) { showResult(false, (resp && resp.error) || 'Не удалось прочитать файл'); return; }
        importData = resp.data;
        renderMapping(visibleCategories(categories));
    }, function (xhr) {
        if (xhr && xhr.status !== undefined) showResult(false, 'Не удалось загрузить файл' + (xhr.status ? ' (код ' + xhr.status + ')' : ''));
    }).always(function () {
        $btn.removeClass('disabled').prop('disabled', false).find('.import-spinner').remove();
        $svg.removeClass('d-none');
        if ($text.length) $text[0].nodeValue = label;
        $('#importFile').val('');
    });
}

// Свойства из файла, которых нет в карточке товара, — уйдут в «Дополнительные свойства».
function renderPropsSummary(props) {
    var names = Object.keys(props);
    if (!names.length) return '';
    names.sort(function (a, b) { return props[b] - props[a]; });
    return '<div class="small text-muted-2 mt-1">В «Дополнительные свойства» товаров попадут: ' +
        names.map(function (n) { return esc(n); }).join(', ') + '</div>';
}

function renderMapping(categories) {
    var d = importData;
    var saved = d.saved || {};
    var formats = {yml: 'YML', csv: 'CSV', xlsx: 'Excel (XLSX)'};

    $('#importSummary').html(
        '<div><b>' + esc(d.file) + '</b> · ' + formats[d.format] + '</div>' +
        '<div>Товаров в файле: <b>' + d.total + '</b>' +
        (d.existing ? ', из них уже есть у вас (по артикулу): <b>' + d.existing + '</b>' : '') + '</div>' +
        renderPropsSummary(d.props || {}) +
        (d.limited ? '<div class="text-warning small">Прочитаны первые ' + d.total + ' товаров — остальные разделите на отдельный файл.</div>' : '')
    );

    $('#itemsSearch').val('');
    $('#itemsFilter').val('');
    renderItems();

    var options = buildCategoryTreeOptions(categories);
    var byName = {};
    options.forEach(function (o) { byName[o.name.toLowerCase()] = o.id; });
    var ids = options.map(function (o) { return o.id; });

    $('#mappingBody').html(d.categories.map(function (c) {
        var selected = 0;
        var remembered = saved.mapping ? saved.mapping[c.id] : undefined;
        if (remembered !== undefined && (Number(remembered) === SKIP_CATEGORY || Number(remembered) === 0 || ids.indexOf(Number(remembered)) !== -1)) {
            selected = Number(remembered);
        } else if (byName[c.name.toLowerCase()]) {
            selected = byName[c.name.toLowerCase()];
        }
        var opts = '<option value="0">— Без категории —</option><option value="' + SKIP_CATEGORY + '">Не импортировать</option>' +
            options.map(function (o) {
                return '<option value="' + o.id + '">' + esc(o.label) + '</option>';
            }).join('');
        opts = opts.replace('value="' + selected + '">', 'value="' + selected + '" selected>');
        return '<tr><td>' + esc(c.name) + (c.path && c.path !== c.name ? '<div class="small text-muted-2">' + esc(c.path) + '</div>' : '') + '</td>' +
            '<td class="text-end">' + c.count + '</td>' +
            '<td><select class="form-select form-select-sm mapping-select" data-cat="' + esc(c.id) + '">' + opts + '</select></td></tr>';
    }).join(''));

    if (saved.update_existing !== undefined) $('#optUpdate').prop('checked', !!saved.update_existing);
    if (saved.images !== undefined) $('#optImages').prop('checked', !!saved.images);
    if (saved.status) $('#optStatus').val(saved.status);
    if (saved.default_stock !== undefined) $('#optStock').val(saved.default_stock);

    $('#runBtn').text('Импортировать ' + d.total + ' ' + plural(d.total, 'товар', 'товара', 'товаров'));
    $('#importStepFile').addClass('d-none');
    $('#importStepMap').removeClass('d-none');
}

/** Все товары файла — с поиском, фильтром «новые / обновятся» и постраничным выводом. */
function renderItems() {
    var q = $('#itemsSearch').val().trim().toLowerCase();
    var filter = $('#itemsFilter').val();
    itemsPager.setRows(importData.items.filter(function (it) {
        if (filter === 'new' && it.exists) return false;
        if (filter === 'exists' && !it.exists) return false;
        return !q || it.name.toLowerCase().indexOf(q) !== -1 || (it.sku || '').toLowerCase().indexOf(q) !== -1;
    }));
}

function renderItemsPage(rows) {
    var update = $('#optUpdate').prop('checked');
    $('#itemsBody').html(rows.map(function (s) {
        // Заглушка с иконкой; картинка по ссылке из файла ложится поверх, не загрузилась — убираем.
        var thumb = '<div class="import-thumb"><i class="bi bi-image"></i>' +
            (s.picture ? '<img src="' + esc(s.picture) + '" alt="" loading="lazy" referrerpolicy="no-referrer" onerror="this.remove()">' : '') + '</div>';
        var mark = s.exists
            ? (update ? '<span class="badge bg-warning-subtle text-warning">Обновится</span>' : '<span class="badge bg-secondary-subtle text-secondary">Пропустится</span>')
            : '<span class="badge bg-success-subtle text-success">Новый</span>';
        return '<tr><td>' + thumb + '</td><td>' + esc(s.name) + '</td><td class="text-nowrap">' + esc(s.sku || '—') + '</td>' +
            '<td class="text-muted-2">' + esc(s.category || '—') + '</td>' +
            '<td class="text-end text-nowrap">' + fmtMoney(s.price) + '</td><td class="text-end">' + (s.stock === null ? '—' : s.stock) + '</td>' +
            '<td>' + mark + '</td></tr>';
    }).join('') || '<tr><td colspan="7" class="text-center text-muted-2 py-4">Ничего не найдено</td></tr>');
}

function collectOptions() {
    var mapping = {};
    $('.mapping-select').each(function () { mapping[$(this).data('cat')] = Number($(this).val()); });
    return {
        mapping: mapping,
        update_existing: $('#optUpdate').prop('checked'),
        images: $('#optImages').prop('checked'),
        status: $('#optStatus').val(),
        default_stock: Math.max(0, parseInt($('#optStock').val(), 10) || 0),
        url: importSource === 'url' ? $('#importUrl').val().trim() : undefined
    };
}

function startImport() {
    var options = collectOptions();
    var limit = options.images ? 5 : 25;
    var totals = {created: 0, updated: 0, skipped: 0, errors: 0};
    var problems = [];

    importStopped = false;
    importRunning = true;
    $('#importStepMap').addClass('d-none');
    $('#importStepRun').removeClass('d-none');
    $('#runDoneBtns').addClass('d-none');
    $('#stopBtn').removeClass('d-none').prop('disabled', false).text('Остановить');
    $('#runErrors').addClass('d-none');
    $('#runErrorsBody').empty();
    updateProgress(0, importData.total, totals);

    function batch(offset) {
        cabinetAjax({
            ajax_action: 'import_run', token: importData.token, offset: offset, limit: limit,
            options: JSON.stringify(options)
        }).then(function (r) {
            totals.created += r.created;
            totals.updated += r.updated;
            totals.skipped += r.skipped;
            totals.errors += r.errors.length;
            problems = problems.concat(r.errors.map(function (e) { return $.extend({fatal: true}, e); }), r.warnings);
            renderProblems(problems);
            updateProgress(r.next_offset, r.total, totals);
            if (r.done || importStopped) {
                finishImport(options, r.done ? 'Готово' : 'Остановлено на ' + r.next_offset + ' из ' + r.total);
            } else {
                batch(r.next_offset);
            }
        }, function () {
            finishImport(options, 'Импорт прерван из-за ошибки');
        });
    }
    batch(0);
}

function updateProgress(done, total, totals) {
    var pct = total ? Math.round(done / total * 100) : 100;
    $('#runBar').css('width', pct + '%');
    $('#runCounter').text(done + ' из ' + total);
    $('#statCreated').text(totals.created);
    $('#statUpdated').text(totals.updated);
    $('#statSkipped').text(totals.skipped);
    $('#statErrors').text(totals.errors);
}

function renderProblems(problems) {
    if (!problems.length) return;
    $('#runErrors').removeClass('d-none');
    $('#runErrorsBody').html(problems.slice(0, 300).map(function (p) {
        return '<tr><td class="text-nowrap">' + esc(p.sku || '—') + '</td><td>' + esc(p.name) + '</td>' +
            '<td class="' + (p.fatal ? 'text-danger' : 'text-muted-2') + '">' + esc(p.error) + '</td></tr>';
    }).join(''));
}

function finishImport(options, status) {
    importRunning = false;
    $('#runStatus').text(status);
    $('#stopBtn').addClass('d-none');
    $('#runDoneBtns').removeClass('d-none');
    $.post(CABINET_URL, {
        ajax_action: 'import_finish', token: importData.token, options: JSON.stringify(options),
        sessid: window.BX && BX.bitrix_sessid ? BX.bitrix_sessid() : ''
    });
}

function resetImport() {
    importData = null;
    $('#importFile').val('');
    $('#importStepMap, #importStepRun').addClass('d-none');
    $('#importStepFile').removeClass('d-none');
}

function plural(n, one, few, many) {
    var m10 = n % 10, m100 = n % 100;
    if (m10 === 1 && m100 !== 11) return one;
    if (m10 >= 2 && m10 <= 4 && (m100 < 12 || m100 > 14)) return few;
    return many;
}

// Одни и те же строки для шаблонов CSV и XLSX.
var TEMPLATE_ROWS = [
    ['Артикул', 'Название', 'Цена', 'Остаток', 'Категория', 'Описание', 'Картинки', 'Цвет', 'Размер', 'Бренд', 'Материал'],
    ['SP-1001', 'Футболка базовая', '450', '120', 'Футболки', 'Хлопок 100%, плотность 160 г/м²', 'https://example.ru/img/sp-1001.jpg', 'Белый', '48', 'Reppa', 'Хлопок']
];

function downloadXlsxTemplate() {
    cabinetAjax({ajax_action: 'import_template_xlsx', rows: JSON.stringify(TEMPLATE_ROWS)}).then(function (data) {
        var bin = atob(data.base64), bytes = new Uint8Array(bin.length);
        for (var i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
        downloadTemplate(bytes, 'reppa-import-template.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });
}

function downloadCsvTemplate() {
    var rows = TEMPLATE_ROWS;
    var csv = '﻿' + rows.map(function (r) {
        return r.map(function (v) { return '"' + String(v).replace(/"/g, '""') + '"'; }).join(';');
    }).join('\r\n');
    downloadTemplate(csv, 'reppa-import-template.csv', 'text/csv;charset=utf-8');
}

/** Шаблон YML: две категории, товар с картинками и характеристиками и два
 *  варианта одного товара (group_id). */
function downloadXmlTemplate() {
    var xml = [
        '<?xml version="1.0" encoding="UTF-8"?>',
        '<yml_catalog date="' + new Date().toISOString().slice(0, 16).replace('T', ' ') + '">',
        '  <shop>',
        '    <name>Мой магазин</name>',
        '    <currencies><currency id="RUR" rate="1"/></currencies>',
        '    <categories>',
        '      <category id="1">Спецодежда</category>',
        '      <category id="2" parentId="1">Футболки</category>',
        '    </categories>',
        '    <offers>',
        '      <offer id="1001" available="true" group_id="10">',
        '        <name>Футболка базовая белая</name>',
        '        <vendorCode>SP-1001</vendorCode>',
        '        <vendor>Reppa</vendor>',
        '        <price>450</price>',
        '        <currencyId>RUR</currencyId>',
        '        <categoryId>2</categoryId>',
        '        <picture>https://example.ru/img/sp-1001.jpg</picture>',
        '        <picture>https://example.ru/img/sp-1001-back.jpg</picture>',
        '        <description><![CDATA[<p>Хлопок 100%, плотность 160 г/м²</p>]]></description>',
        '        <param name="Цвет">Белый</param>',
        '        <param name="Размер">48</param>',
        '        <param name="Материал">Хлопок</param>',
        '        <weight>0.2</weight>',
        '        <barcode>4600000010015</barcode>',
        '        <country_of_origin>Россия</country_of_origin>',
        '        <count>120</count>',
        '      </offer>',
        '      <offer id="1002" available="true" group_id="10">',
        '        <name>Футболка базовая чёрная</name>',
        '        <vendorCode>SP-1002</vendorCode>',
        '        <vendor>Reppa</vendor>',
        '        <price>450</price>',
        '        <currencyId>RUR</currencyId>',
        '        <categoryId>2</categoryId>',
        '        <picture>https://example.ru/img/sp-1002.jpg</picture>',
        '        <param name="Цвет">Чёрный</param>',
        '        <param name="Размер">48</param>',
        '        <count>80</count>',
        '      </offer>',
        '    </offers>',
        '  </shop>',
        '</yml_catalog>'
    ].join('\r\n');
    downloadTemplate(xml, 'reppa-import-template.xml', 'application/xml;charset=utf-8');
}

function downloadTemplate(content, fileName, type) {
    var a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([content], {type: type}));
    a.download = fileName;
    document.body.appendChild(a);
    a.click();
    a.remove();
}
