/* Дополнение формы новости кабинета партнёра (partner:news-detail.js) для
   администратора: раздел сайта и партнёр. Новости раздела «Партнёры» —
   от имени выбранного партнёра, остальные разделы — новости площадки.
   collectNews() из news-detail.js дополняется section_id/partner_id
   (doSave() вызывает её по имени). Дата публикации — любая, и прошлая. */
var NEWS_PARTNERS_SECTION_CODE = 'partners';
var newsSections = [];

collectNews = (function (original) {
    return function () {
        return Object.assign(original(), {
            section_id: Number($('#f_section').val()) || 0,
            partner_id: isPartnersSection() ? (Number($('#f_partner').val()) || 0) : 0
        });
    };
})(collectNews);

function isPartnersSection() {
    var s = newsSections.find(function (x) { return x.id === Number($('#f_section').val()); });
    return !!s && s.code === NEWS_PARTNERS_SECTION_CODE;
}

$(function () {
    if (newsDatePicker) newsDatePicker.set('minDate', null);
    $('#catalogProductSearch').attr('placeholder', 'Начните вводить название или артикул товара (от 3 символов)…');
    fillPartnerSelect('#f_partner', {allLabel: '— выберите партнёра —'});
    $('#f_section').on('change', function () { $('#partnerField').toggleClass('d-none', !isPartnersSection()); });

    var newsId = (ROUTE_ID && ROUTE_ID !== 'new') ? parseInt(ROUTE_ID, 10) : 0;
    $.when(dsLoad('news_sections'), newsId ? dsLoad('news') : null).done(function (sections, rows) {
        newsSections = sections;
        $('#f_section').html(sections.map(function (s) { return '<option value="' + s.id + '">' + esc(s.name) + '</option>'; }).join(''));
        var n = newsId ? (rows || []).find(function (x) { return x.id === newsId; }) : null;
        if (n) {
            $('#f_section').val(n.section_id);
            $('#f_partner').val(n.partner_id || '');
            if (n.public_url && n.status === 'active') {
                $('.detail-actionbar .flex-spacer').after('<a href="' + esc(n.public_url) + '" class="btn btn-outline-secondary" target="_blank"><i class="bi bi-box-arrow-up-right"></i> На сайте</a>');
            }
        } else {
            var partner = getQueryParam('partner');
            var preset = partner ? sections.find(function (s) { return s.code === NEWS_PARTNERS_SECTION_CODE; }) : sections.find(function (s) { return s.code !== NEWS_PARTNERS_SECTION_CODE; });
            if (preset) $('#f_section').val(preset.id);
        }
        $('#f_section').trigger('change');
    });
});
