<?php
/** @var array $arResult */
$activeKey = 'chat';
$pageTitle = 'Чат с клиентами — Formaro Partner';
require __DIR__ . '/inc/layout_app_top.php';
?>
<div class="filter-bar d-flex flex-wrap gap-2 align-items-end">
    <div><label class="form-label small mb-1">Клиент</label><input type="text" class="form-control" id="fClient" placeholder="Имя клиента" style="min-width:160px;"></div>
    <div><label class="form-label small mb-1">№ заказа</label><input type="text" class="form-control" id="fOrder" placeholder="F-100218" style="min-width:130px;"></div>
    <div><label class="form-label small mb-1">Товар</label><input type="text" class="form-control" id="fProduct" placeholder="Название товара" style="min-width:180px;"></div>
    <button class="btn btn-outline-secondary" id="findBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 21-4.34-4.34"></path><circle cx="11" cy="11" r="8"></circle></svg></button>
    <button class="btn btn-outline-secondary filter-clear-btn" id="clearBtn" title="Сбросить фильтры"><i class="bi bi-x-lg"></i></button>
</div>

<div class="chat-wrap">
    <div class="chat-threads" id="threadsList"><div class="text-center text-muted-2 py-4">Загрузка…</div></div>
    <div class="chat-panel">
        <div class="chat-scroll" id="messagesScroll"><div class="empty-state m-auto"><i class="bi bi-chat-dots"></i>Выберите диалог слева</div></div>
        <div id="composeWrap" style="display:none;">
            <div class="attach-row">
                <button type="button" class="attach-btn" id="attachBtn" title="Прикрепить файлы"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 6-8.414 8.586a2 2 0 0 0 2.829 2.829l8.414-8.586a4 4 0 1 0-5.657-5.657l-8.379 8.551a6 6 0 1 0 8.485 8.485l8.379-8.551"></path></svg></button>
                <input type="file" id="chatFile" class="d-none" multiple>
                <span class="text-muted-2 small">Прикрепить файл</span>
            </div>
            <div id="attachPreviewWrap"></div>
            <div class="chat-input-row align-stretch">
                <textarea class="form-control autoheight-input" id="chatInput" rows="2" placeholder="Напишите сообщение…" style="flex:1;"></textarea>
                <button class="btn btn-primary btn-send-tall btn-send-wide" id="sendBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3.714 3.048a.498.498 0 0 0-.683.627l2.843 7.627a2 2 0 0 1 0 1.396l-2.842 7.627a.498.498 0 0 0 .682.627l18-8.5a.5.5 0 0 0 0-.904z"></path><path d="M6 12h16"></path></svg></button>
            </div>
        </div>
    </div>
</div>
<?php
$pageScripts = ['chat-clients.js'];
require __DIR__ . '/inc/layout_app_bottom.php';
