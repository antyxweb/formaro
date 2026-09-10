var allNotifications = [];
var pager = null;

var NOTIF_TYPES = {
    order: {icon: '<svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2.05 2.05 1.099-.028a1 1 0 0 1 1.008.815l2.69 14.347A1 1 0 0 0 7.83 18H18"></path><path d="M4.563 5h16.435a1 1 0 0 1 .981 1.204l-1.026 6.226A2 2 0 0 1 18.962 14H6.25"></path><circle cx="18" cy="20" r="2"></circle><circle cx="8" cy="20" r="2"></circle></svg>', color: 'blue', label: 'Заказы'},
    chat: {icon: '<svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 10a2 2 0 0 1-2 2H6.828a2 2 0 0 0-1.414.586l-2.202 2.202A.71.71 0 0 1 2 14.286V4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path><path d="M20 9a2 2 0 0 1 2 2v10.286a.71.71 0 0 1-1.212.502l-2.202-2.202A2 2 0 0 0 17.172 19H10a2 2 0 0 1-2-2v-1"></path></svg>', color: 'green', label: 'Чат'},
    product: {icon: '<svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"></path><path d="M12 22V12"></path><polyline points="3.29 7 12 12 20.71 7"></polyline><path d="m7.5 4.27 9 5.15"></path></svg>', color: 'orange', label: 'Товары'},
    finance: {icon: '<svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"></path><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"></path></svg>', color: 'purple', label: 'Финансы'},
    support: {icon: '<svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="m4.93 4.93 4.24 4.24"></path><path d="m14.83 9.17 4.24-4.24"></path><path d="m14.83 14.83 4.24 4.24"></path><path d="m9.17 14.83-4.24 4.24"></path><circle cx="12" cy="12" r="4"></circle></svg>', color: 'gray', label: 'Техподдержка'},
    system: {icon: '<svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4"></path><path d="M12 8h.01"></path></svg>', color: 'gray', label: 'Система'}
};

$(function () {
    loadPartials('notifications', 'Уведомления');
    pager = initTablePager({root: '#notifCard', renderFn: renderPage, storageKey: 'pagesize_notifications'});

    dsLoad('notifications', 'data/notifications.json').done(function (rows) {
        allNotifications = rows;
        render();
    });

    $('#typeFilter').on('change', render);
    $('#unreadOnlyToggle').on('change', render);

    $('#markAllBtn').on('click', function () {
        allNotifications = allNotifications.map(function (n) { return Object.assign({}, n, {is_read: true}); });
        dsSave('notifications', allNotifications);
        render();
    });
});

function render() {
    var type = $('#typeFilter').val();
    var unreadOnly = $('#unreadOnlyToggle').is(':checked');

    var rows = allNotifications.filter(function (n) {
        if (type && n.type !== type) return false;
        if (unreadOnly && n.is_read) return false;
        return true;
    }).sort(function (a, b) { return new Date(b.created_at) - new Date(a.created_at); });

    var unreadTotal = allNotifications.filter(function (n) { return !n.is_read; }).length;
    $('#unreadCountLabel').text(unreadTotal > 0 ? '(' + unreadTotal + ')' : '');
    $('#markAllBtn').prop('disabled', unreadTotal === 0);

    pager.setRows(rows);
}

function renderPage(rows) {
    var $l = $('#listBody').empty();
    if (rows.length === 0) { $l.html('<div class="empty-state"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.268 21a2 2 0 0 0 3.464 0"></path><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"></path></svg>Уведомлений нет.</div>'); return; }
    rows.forEach(function (n) {
        var meta = NOTIF_TYPES[n.type] || {icon: '', color: 'gray', label: n.type || ''};
        $l.append(
            '<div class="notif-item' + (n.is_read ? '' : ' unread') + '" onclick="markRead(' + n.id + ')">' +
              '<div class="notif-icon notif-icon-' + meta.color + '">' + meta.icon + '</div>' +
              '<div class="notif-body">' +
                '<div class="notif-title">' + esc(n.title) + (n.is_read ? '' : ' <span class="notif-dot"></span>') + '</div>' +
                '<div class="notif-message">' + esc(n.message) + '</div>' +
                '<div class="notif-meta">' + esc(meta.label) + ' · ' + fmtDate(n.created_at) + '</div>' +
              '</div>' +
            '</div>'
        );
    });
}

function markRead(id) {
    var n = allNotifications.find(function (x) { return x.id === id; });
    if (!n || n.is_read) return;
    allNotifications = allNotifications.map(function (x) { return x.id === id ? Object.assign({}, x, {is_read: true}) : x; });
    dsSave('notifications', allNotifications);
    render();
}
