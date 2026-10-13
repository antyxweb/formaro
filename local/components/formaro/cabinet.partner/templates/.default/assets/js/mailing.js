/* Вкладка «Рассылки» на странице уведомлений (MailingService): какие
   уведомления кабинета приходят письмом (переключатели сохраняются сразу —
   save_mailing), подписки на новости (unsubscribe_newsletter), «Отписаться
   от всех рассылок» (unsubscribe_all_mailing). Ссылка из письма открывает
   вкладку: ?tab=mailing. Данные — dsLoad('mailing'), письма уходят на e-mail
   текущего пользователя. */
$(function () {
    if (getQueryParam('tab') === 'mailing') {
        bootstrap.Tab.getOrCreateInstance(document.querySelector('#mainTabs button[data-bs-target="#tabMailing"]')).show();
    }

    var loaded = false;
    function load() {
        if (loaded) return;
        loaded = true;
        dsLoad('mailing').done(renderMailing).fail(function () { loaded = false; });
    }
    $('#mainTabs button[data-bs-target="#tabMailing"]').on('shown.bs.tab', load);
    if (getQueryParam('tab') === 'mailing') load();

    function renderMailing(state) {
        $('#mailingEmail').text(state.email);

        var html = '';
        state.groups.forEach(function (group) {
            group.items.forEach(function (item) {
                var id = 'mailing-' + item.channel.replace('.', '-');
                html += '<div class="form-check form-switch mb-3">'
                    + '<input class="form-check-input js-mailing-channel" type="checkbox" id="' + id + '" value="' + esc(item.channel) + '"' + (item.enabled ? ' checked' : '') + '>'
                    + '<label class="form-check-label" for="' + id + '">' + esc(item.label)
                    + (item.hint ? '<span class="d-block small text-muted-2">' + esc(item.hint) + '</span>' : '')
                    + '</label></div>';
            });
        });
        $('#mailingChannels').html(html + '<div class="small" id="mailingStatus" role="status"></div>');

        if (!state.newsletters.length) {
            $('#mailingNewsletters').html('<div class="p-3 text-muted-2">Вы не подписаны на новости. Подписаться можно в блоке «Будьте в курсе» на главной и на страницах поставщиков.</div>');
        } else {
            var rows = state.newsletters.map(function (item) {
                return '<tr><td>' + esc(item.title)
                    + (item.created ? '<div class="small text-muted-2">Подписка с ' + esc(item.created) + '</div>' : '')
                    + '</td><td class="text-end"><button type="button" class="btn btn-outline-secondary btn-sm js-newsletter-unsubscribe" data-id="' + item.id + '">Отписаться</button></td></tr>';
            }).join('');
            $('#mailingNewsletters').html('<div class="table-responsive"><table class="table align-middle mb-0"><tbody>' + rows + '</tbody></table></div>');
        }
    }

    $('#mailingChannels').on('change', '.js-mailing-channel', function () {
        var $input = $(this);
        var $all = $('#mailingChannels .js-mailing-channel');
        var channels = $all.filter(':checked').map(function () { return this.value; }).get();
        $all.prop('disabled', true);
        $('#mailingStatus').text('').removeClass('text-success');
        cabinetAjax({ajax_action: 'save_mailing', channels: channels}).done(function (state) {
            renderMailing(state);
            $('#mailingStatus').text('Сохранено').addClass('text-success');
        }).fail(function () {
            $all.prop('disabled', false);
            $input.prop('checked', !$input.prop('checked'));
        });
    });

    $('#mailingNewsletters').on('click', '.js-newsletter-unsubscribe', function () {
        var $btn = $(this).prop('disabled', true);
        cabinetAjax({ajax_action: 'unsubscribe_newsletter', id: $btn.data('id')})
            .done(renderMailing)
            .fail(function () { $btn.prop('disabled', false); });
    });

    $('#mailingUnsubscribeAllBtn').on('click', function () {
        showConfirm('Перестанем присылать письма о заказах, сообщениях, выплатах и новостях — и как партнёру, и как покупателю. Служебные письма (пароль, счета) будут приходить. В ленте «Уведомления» всё останется.', function () {
            cabinetAjax({ajax_action: 'unsubscribe_all_mailing'}).done(function (state) {
                renderMailing(state);
                showResult(true, 'Вы отписались от всех рассылок.');
            });
        }, {title: 'Отписаться от всех рассылок?', okText: 'Отписаться', danger: true});
    });
});
