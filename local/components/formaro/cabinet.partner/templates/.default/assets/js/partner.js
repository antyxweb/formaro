/* Порт cabinet-html/assets/js/partner.js.
   Изменения относительно прототипа:
   - форма уже заполнена сервером (profile.php рендерит текущие значения
     в value=""), поэтому начальный dsLoad('partner') для заполнения полей
     не нужен — оставлены только logoDataUrl/coverDataUrl, инициализированные
     из INITIAL_PARTNER (см. profile.php), чтобы "не менялось" отследить
     на сервере и не перезаливать файл без необходимости;
   - документы партнёра — реальные файлы через HL-блок CabinetPartnerDocuments
     (entity "partner_documents"), каждое действие (добавить/удалить) сразу
     идёт на сервер, а не копится в памяти для пакетного сохранения;
   - смена пароля теперь настоящая — обычная отправка формы на сервер
     (см. profile.php), поэтому фейковый JS-обработчик submit убран. */

var logoDataUrl = (window.INITIAL_PARTNER && window.INITIAL_PARTNER.logo) || '';
var coverDataUrl = (window.INITIAL_PARTNER && window.INITIAL_PARTNER.image) || '';
var partnerDocs = [];
var MAX_DOC_SIZE = 20 * 1024 * 1024; // 20 МБ

$(function () {
    bindImagePreview('#logoFile', '#logoImg', function (url) { logoDataUrl = url; });
    bindImagePreview('#coverFile', '#coverImg', function (url) { coverDataUrl = url; });
    bindImageRemove('#logoRemoveBtn', '#logoImg', 'https://placehold.co/135x135?text=%20', function () { logoDataUrl = ''; });
    bindImageRemove('#coverRemoveBtn', '#coverImg', 'https://placehold.co/560x220?text=%20', function () { coverDataUrl = ''; });
    initRichText('#f_full_desc');
    bindAutoHeight('#f_short_desc');
    autoHeightResize('#f_short_desc');

    loadDocs();

    $('#docsFile').on('change', function () {
        var files = Array.prototype.slice.call(this.files);
        var $input = $(this);
        var tooBig = [];
        var toUpload = files.filter(function (file) {
            if (file.size > MAX_DOC_SIZE) { tooBig.push(file.name); return false; }
            return true;
        });
        $input.val('');

        function finish() {
            loadDocs();
            if (tooBig.length) {
                showResult(false, 'Слишком большой файл (макс. 20 МБ), не загружен: ' + tooBig.join(', '));
            } else {
                showResult(true, 'Файлы загружены');
            }
        }

        if (!toUpload.length) { if (tooBig.length) finish(); return; }
        var pending = toUpload.length;
        toUpload.forEach(function (file) {
            var reader = new FileReader();
            reader.onload = function (e) {
                addPartnerDocument(file.name, e.target.result).always(function () {
                    if (--pending <= 0) finish();
                });
            };
            reader.readAsDataURL(file);
        });
    });

    $('#saveBtn').on('click', function () {
        var payload = {
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
        };
        dsSaveOne('partner', payload).done(function () {
            showResult(true, 'Данные партнёра сохранены');
        });
    });
});

function addPartnerDocument(name, dataUrl) {
    return cabinetAjax({ajax_action: 'add_document', name: name, file: dataUrl});
}
function deletePartnerDocument(id) {
    return cabinetAjax({ajax_action: 'delete_document', id: id});
}
function loadDocs() {
    dsLoad('partner_documents').done(function (docs) {
        partnerDocs = docs || [];
        renderDocs();
    });
}

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
    partnerDocs.forEach(function (d) {
        var nameHtml = d.url ? '<a href="' + d.url + '" target="_blank">' + esc(d.name) + '</a>' : esc(d.name);
        $l.append(
            '<div class="doc-item">' +
              '<div class="doc-icon"><i class="bi ' + docIcon(d.name) + '"></i></div>' +
              '<div class="doc-info"><div class="doc-name">' + nameHtml + '</div><div class="doc-meta">' + fmtFileSize(d.size) + ' · ' + fmtDate(d.uploaded_at) + '</div></div>' +
              '<button type="button" class="btn btn-sm btn-outline-danger" onclick="removeDoc(' + d.id + ')" title="Удалить"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>' +
            '</div>'
        );
    });
}
function removeDoc(id) {
    var d = partnerDocs.find(function (x) { return x.id === id; });
    showConfirm('Удалить документ «' + esc(d ? d.name : '') + '»?', function () {
        deletePartnerDocument(id).done(function () {
            showResult(true, 'Документ удалён');
            loadDocs();
        });
    }, {danger: true, okText: 'Удалить'});
}
