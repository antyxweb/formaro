<?php
/**
 * Чаты покупателей с площадкой («Сообщения» на сайте) — здесь же отвечает
 * администратор; фильтр «Партнёр» → «Площадка» — только они.
 *
 * Чаты покупателей с партнёрами — только просмотр, фильтр по партнёру,
 * покупателю, заказу и товару. Сообщения с контактами, разговором об
 * оплате или сделке в обход площадки помечены (ChatModerationService),
 * «Только с нарушениями» — диалоги с такими сообщениями.
 *
 * @var array $arResult
 */
$activeKey = 'chat';
$pageTitle = 'Чаты с клиентами';
require __DIR__ . '/inc/layout_app_top.php';
?>
<div class="filter-bar d-flex flex-wrap gap-2 align-items-end">
    <div><label class="form-label small mb-1">Партнёр</label>
        <select class="form-select" id="partnerFilter" style="min-width:200px;"></select></div>
    <div><label class="form-label small mb-1">Клиент</label><input type="text" class="form-control" id="fClient" placeholder="Имя клиента" style="min-width:160px;"></div>
    <div><label class="form-label small mb-1">№ заказа</label><input type="text" class="form-control" id="fOrder" placeholder="F-100218" style="min-width:130px;"></div>
    <div><label class="form-label small mb-1">Товар</label><input type="text" class="form-control" id="fProduct" placeholder="Название товара" style="min-width:180px;"></div>
    <div>
        <label class="form-label small mb-1 d-block invisible">.</label>
        <div class="form-check form-switch mb-0" style="height:38px;">
            <input class="form-check-input" type="checkbox" id="alertsOnly">
            <label class="form-check-label" for="alertsOnly">Только с нарушениями</label>
        </div>
    </div>
    <button class="btn btn-outline-secondary filter-clear-btn" id="clearBtn" title="Сбросить фильтры"><i class="bi bi-x-lg"></i></button>
</div>

<div class="chat-wrap">
    <div class="chat-threads" id="threadsList"><div class="text-center text-muted-2 py-4">Загрузка…</div></div>
    <div class="chat-panel">
        <div class="chat-panel-head small text-muted-2 px-3 py-2 d-none" id="threadHead"></div>
        <div class="chat-scroll" id="messagesScroll"><div class="empty-state m-auto"><i class="bi bi-chat-dots"></i>Выберите диалог слева</div></div>
        <div class="px-3 py-2 small text-muted-2 border-top d-none" id="readOnlyNote"><i class="bi bi-eye"></i> Режим просмотра: отвечает покупателю партнёр в своём кабинете.</div>
        <div class="d-none" id="replyBox">
            <div class="attach-row">
                <button type="button" class="attach-btn" id="attachBtn" title="Прикрепить файлы"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 6-8.414 8.586a2 2 0 0 0 2.829 2.829l8.414-8.586a4 4 0 1 0-5.657-5.657l-8.379 8.551a6 6 0 1 0 8.485 8.485l8.379-8.551"></path></svg></button>
                <input type="file" id="replyFile" class="d-none" multiple>
                <span class="text-muted-2 small">Прикрепить файл</span>
            </div>
            <div id="attachPreviewWrap"></div>
            <div class="chat-input-row align-stretch">
                <textarea class="form-control autoheight-input" id="replyInput" rows="2" placeholder="Ответ покупателю от площадки…" style="flex:1;"></textarea>
                <button class="btn btn-primary btn-send-tall btn-send-wide" id="replySendBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3.714 3.048a.498.498 0 0 0-.683.627l2.843 7.627a2 2 0 0 1 0 1.396l-2.842 7.627a.498.498 0 0 0 .682.627l18-8.5a.5.5 0 0 0 0-.904z"></path><path d="M6 12h16"></path></svg></button>
            </div>
        </div>
    </div>
</div>
<?php
$pageScripts = ['chat.js'];
require __DIR__ . '/inc/layout_app_bottom.php';
