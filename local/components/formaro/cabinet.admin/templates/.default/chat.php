<?php
/**
 * Чаты покупателей с партнёрами — только просмотр, фильтр по партнёру,
 * покупателю, заказу и товару.
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
    <button class="btn btn-outline-secondary filter-clear-btn" id="clearBtn" title="Сбросить фильтры"><i class="bi bi-x-lg"></i></button>
</div>

<div class="chat-wrap">
    <div class="chat-threads" id="threadsList"><div class="text-center text-muted-2 py-4">Загрузка…</div></div>
    <div class="chat-panel">
        <div class="chat-panel-head small text-muted-2 px-3 py-2 d-none" id="threadHead"></div>
        <div class="chat-scroll" id="messagesScroll"><div class="empty-state m-auto"><i class="bi bi-chat-dots"></i>Выберите диалог слева</div></div>
        <div class="px-3 py-2 small text-muted-2 border-top d-none" id="readOnlyNote"><i class="bi bi-eye"></i> Режим просмотра: отвечает покупателю партнёр в своём кабинете.</div>
    </div>
</div>
<?php
$pageScripts = ['chat.js'];
require __DIR__ . '/inc/layout_app_bottom.php';
