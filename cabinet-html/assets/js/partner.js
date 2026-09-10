var logoDataUrl = '';
var coverDataUrl = '';
var partnerDocs = [];
var MAX_DOC_SIZE = 20 * 1024 * 1024; // 20 МБ — ограничение на файл документа

$(function () {
    loadPartials('partner', 'Данные партнёра');

    bindImagePreview('#logoFile', '#logoImg', function (url) { logoDataUrl = url; });
    bindImagePreview('#coverFile', '#coverImg', function (url) { coverDataUrl = url; });
    bindImageRemove('#logoRemoveBtn', '#logoImg', 'https://placehold.co/135x135?text=%20', function () { logoDataUrl = ''; });
    bindImageRemove('#coverRemoveBtn', '#coverImg', 'https://placehold.co/560x220?text=%20', function () { coverDataUrl = ''; });
    initRichText('#f_full_desc');
    bindAutoHeight('#f_short_desc');

    dsLoad('partner', 'data/partner.json').done(function (p) {
        $('#verificationPill').html(statusPill(p.verification_status));
        $('#f_name_full').val(p.name_full);
        $('#f_name_short').val(p.name_short);
        $('#f_short_desc').val(p.short_desc); autoHeightResize('#f_short_desc');
        $('#f_full_desc').val(p.full_desc);
        if ($.fn.trumbowyg) $('#f_full_desc').trumbowyg('html', p.full_desc || '');
        if (p.logo) { setBgImage('#logoImg', p.logo); logoDataUrl = p.logo; }
        if (p.image) { setBgImage('#coverImg', p.image); coverDataUrl = p.image; }
        $('#f_inn').val(p.legal.inn);
        $('#f_ogrn').val(p.legal.ogrn);
        $('#f_bik').val(p.legal.bik);
        $('#f_legal_address').val(p.legal.legal_address);
        $('#f_bank_name').val(p.legal.bank_name);
        $('#f_account').val(p.legal.account);
        $('#f_corr_account').val(p.legal.corr_account);
        $('#f_ceo_name').val(p.legal.ceo_name);
        $('#f_phone').val(p.contacts.phone);
        $('#f_email').val(p.contacts.email);
        $('#f_contact_person').val(p.contacts.contact_person);
        $('#f_contact_position').val(p.contacts.contact_position);
        partnerDocs = p.documents || [];
        renderDocs();
    });

    $('#docsFile').on('change', function () {
        var files = Array.prototype.slice.call(this.files);
        var tooBig = [];
        files.forEach(function (file) {
            if (file.size > MAX_DOC_SIZE) { tooBig.push(file.name); return; }
            partnerDocs.push({name: file.name, size: file.size, uploaded_at: new Date().toISOString()});
        });
        $(this).val('');
        saveDocs(function () {
            renderDocs();
            if (tooBig.length) {
                showResult(false, 'Слишком большой файл (макс. 20 МБ), не загружен: ' + tooBig.join(', '));
            } else {
                showResult(true, 'Файлы загружены');
            }
        });
    });

    $('#saveBtn').on('click', function () {
        dsLoad('partner', 'data/partner.json').done(function (p) {
            var updated = Object.assign({}, p, {
                name_full: $('#f_name_full').val(),
                name_short: $('#f_name_short').val(),
                short_desc: $('#f_short_desc').val(),
                full_desc: $.fn.trumbowyg ? $('#f_full_desc').trumbowyg('html') : $('#f_full_desc').val(),
                logo: logoDataUrl,
                image: coverDataUrl,
                legal: {
                    inn: $('#f_inn').val(), ogrn: $('#f_ogrn').val(), bik: $('#f_bik').val(),
                    legal_address: $('#f_legal_address').val(), bank_name: $('#f_bank_name').val(),
                    account: $('#f_account').val(), corr_account: $('#f_corr_account').val(),
                    ceo_name: $('#f_ceo_name').val()
                },
                contacts: {
                    phone: $('#f_phone').val(), email: $('#f_email').val(), contact_person: $('#f_contact_person').val(),
                    contact_position: $('#f_contact_position').val()
                }
            });
            dsSave('partner', updated);
            showResult(true, 'Данные партнёра сохранены');
        });
    });

    $('#passwordForm').on('submit', function () {
        if ($('#p_new').val() !== $('#p_new2').val()) { showResult(false, 'Новые пароли не совпадают'); return; }
        showResult(true, 'Пароль изменён');
        this.reset();
    });
});

function fmtFileSize(bytes) {
    if (bytes >= 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + ' МБ';
    if (bytes >= 1024) return Math.round(bytes / 1024) + ' КБ';
    return bytes + ' Б';
}
function docIcon(name) {
    var ext = (name.split('.').pop() || '').toLowerCase();
    if (ext === 'pdf') return 'bi-file-earmark-pdf';
    if (['doc', 'docx'].indexOf(ext) !== -1) return 'bi-file-earmark-word';
    if (['xls', 'xlsx'].indexOf(ext) !== -1) return 'bi-file-earmark-excel';
    if (['jpg', 'jpeg', 'png', 'gif'].indexOf(ext) !== -1) return 'bi-file-earmark-image';
    return 'bi-file-earmark-text';
}
function renderDocs() {
    $('#docsCountBadge').text(partnerDocs.length).toggle(partnerDocs.length > 0);
    var $l = $('#docsList').empty();
    if (partnerDocs.length === 0) {
        $l.append('<div class="empty-state"><i class="bi bi-file-earmark"></i>Документы ещё не загружены.</div>');
        return;
    }
    partnerDocs.forEach(function (d, idx) {
        $l.append(
            '<div class="doc-item">' +
              '<div class="doc-icon"><i class="bi ' + docIcon(d.name) + '"></i></div>' +
              '<div class="doc-info"><div class="doc-name">' + esc(d.name) + '</div><div class="doc-meta">' + fmtFileSize(d.size) + ' · ' + fmtDate(d.uploaded_at) + '</div></div>' +
              '<button type="button" class="btn btn-sm btn-outline-danger" onclick="removeDoc(' + idx + ')" title="Удалить"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>' +
            '</div>'
        );
    });
}
function removeDoc(idx) {
    showConfirm('Удалить документ «' + esc(partnerDocs[idx].name) + '»?', function () {
        partnerDocs.splice(idx, 1);
        saveDocs(renderDocs);
    }, {danger: true, okText: 'Удалить'});
}
/** Храним только метаданные файла (имя/размер/дата), не само содержимое —
    localStorage в браузере не рассчитан на хранение файлов до 20 МБ, в
    реальной системе здесь будет загрузка на сервер. */
function saveDocs(cb) {
    dsLoad('partner', 'data/partner.json').done(function (p) {
        dsSave('partner', Object.assign({}, p, {documents: partnerDocs}));
        if (cb) cb();
    });
}
