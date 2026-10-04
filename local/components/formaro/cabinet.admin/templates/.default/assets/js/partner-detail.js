/* Карточка партнёра — модерация данных компании (PartnerRepository::
   getForModeration): current — что сейчас на сайте, pending — что партнёр
   прислал на проверку (null — ничего). Одобрить — черновик переносится на
   сайт, статус «Проверен»; отклонить — статус «Отклонён» с причиной. */
var card = null;

var FIELDS = [
    ['Полное название', function (d) { return d.name_full; }],
    ['Краткое название', function (d) { return d.name_short; }],
    ['Логотип', function (d) { return d.logo; }, 'image'],
    ['Обложка', function (d) { return d.image; }, 'image'],
    ['Краткое описание', function (d) { return d.short_desc; }],
    ['Полное описание', function (d) { return d.full_desc; }, 'html'],
    ['ИНН', function (d) { return d.legal.inn; }],
    ['ОГРН', function (d) { return d.legal.ogrn; }],
    ['Юридический адрес', function (d) { return d.legal.legal_address; }],
    ['Банк', function (d) { return d.legal.bank_name; }],
    ['БИК', function (d) { return d.legal.bik; }],
    ['Расчётный счёт', function (d) { return d.legal.account; }],
    ['Корр. счёт', function (d) { return d.legal.corr_account; }],
    ['Руководитель', function (d) { return d.legal.ceo_name; }],
    ['Телефон', function (d) { return d.contacts.phone; }],
    ['E-mail', function (d) { return d.contacts.email; }],
    ['Контактное лицо', function (d) { return d.contacts.contact_person; }],
    ['Должность', function (d) { return d.contacts.contact_position; }]
];

$(function () {
    cabinetAjax({ajax_action: 'partner_card', id: ROUTE_ID}).done(function (data) {
        if (!data) { showResult(false, 'Партнёр не найден'); return; }
        render(data);
    });
    $('#approveBtn').on('click', approve);
    $('#rejectBtn').on('click', reject);
    $('#activeToggle').on('change', function () {
        var active = $(this).is(':checked');
        cabinetAjax({ajax_action: 'set_partner_active', id: ROUTE_ID, active: active ? 'Y' : 'N'}).done(function (data) {
            render(data);
            showResult(true, active ? 'Партнёр показывается на сайте' : 'Партнёр скрыт с сайта');
        }).fail(function () { $('#activeToggle').prop('checked', !active); });
    });
});

function plainText(html) { return $('<div>').html(html || '').text().trim(); }

function cellHtml(value, type) {
    if (!value) return '<span class="text-muted-2">—</span>';
    if (type === 'image') return '<div class="moderation-thumb" style="background-image:url(\'' + esc(value) + '\')"></div>';
    var text = type === 'html' ? plainText(value) : String(value);
    return '<span class="reason-note">' + esc(text.length > 600 ? text.slice(0, 600) + '…' : text) + '</span>';
}

function render(data) {
    card = data;
    var cur = data.current;
    var pend = data.pending;
    document.title = data.name;
    $('#topbarTitle').text(data.name);
    $('#pName').text(data.name);
    $('#pNameFull').text(data.name_full !== data.name ? data.name_full : '');
    $('#pLogo').css('background-image', data.logo ? 'url(' + data.logo + ')' : 'none');
    $('#pStatus').html(partnerStatusPill(data.verification_status) +
        (data.has_pending ? ' <span class="pill pill-yellow">Есть изменения на проверке</span>' : ''));
    $('#activeToggle').prop('checked', data.active);
    $('#publicLink').attr('href', data.public_url).toggleClass('d-none', !data.active);
    $('#rejectNote').toggleClass('d-none', !(data.verification_status === 'rejected' && data.moderation_comment))
        .text('Отклонено. Причина: ' + (data.moderation_comment || ''));

    var canModerate = !!pend || data.verification_status !== 'verified';
    $('#moderationActions').toggleClass('d-none', !canModerate);
    $('#rejectBtn').toggleClass('d-none', data.verification_status === 'rejected' && !pend);
    $('#dataTitle').text(pend ? 'Данные компании — изменения на проверке' : 'Данные компании');
    $('#pendingHead').toggle(!!pend);

    var $b = $('#diffBody').empty();
    FIELDS.forEach(function (f) {
        var a = f[1](cur) || '';
        var row = '<tr><td class="text-muted-2 small">' + f[0] + '</td><td>' + cellHtml(a, f[2]) + '</td>';
        if (pend) {
            var b = f[1](pend) || '';
            row += '<td class="' + (a !== b ? 'is-changed' : '') + '">' + cellHtml(b, f[2]) + '</td>';
        }
        $b.append(row + '</tr>');
    });

    var $u = $('#usersBody').empty();
    if (!data.users.length) $u.html('<tr><td class="text-muted-2">Нет привязанных учётных записей</td></tr>');
    data.users.forEach(function (u) {
        $u.append('<tr><td><div class="fw-semibold">' + esc(u.name) + (u.active ? '' : ' <span class="pill pill-gray">Заблокирован</span>') + '</div>' +
            '<div class="small text-muted-2">' + esc([u.email, u.phone].filter(Boolean).join(' · ')) + '</div></td></tr>');
    });
}

function approve() {
    var msg = card.pending
        ? 'Одобрить изменения партнёра «' + card.name + '»? Данные сразу появятся на сайте.'
        : 'Подтвердить проверку партнёра «' + card.name + '»?';
    showConfirm(msg, function () {
        cabinetAjax({ajax_action: 'approve_partner', id: ROUTE_ID}).done(function (data) {
            render(data);
            refreshSidebarCounts();
            showResult(true, data.active ? 'Партнёр проверен' : 'Партнёр проверен. Чтобы он появился на сайте, включите «Показывать на сайте».');
        });
    }, {okText: 'Одобрить'});
}

function reject() {
    adminAskReason({title: 'Отклонить данные партнёра', value: card.moderation_comment}, function (reason) {
        cabinetAjax({ajax_action: 'reject_partner', id: ROUTE_ID, reason: reason}).done(function (data) {
            render(data);
            refreshSidebarCounts();
            showResult(true, 'Данные отклонены, партнёр получил уведомление');
        });
    });
}
