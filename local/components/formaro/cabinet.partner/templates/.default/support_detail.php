<?php
/** @var array $arResult */
$activeKey = 'support';
$pageTitle = 'Обращение — Formaro Partner';
$routeId = $arResult['VARIABLES']['ID'] ?? 0;
require __DIR__ . '/inc/layout_app_top.php';
?>
<a href="<?= $arResult['SEF_FOLDER'] ?>support/" class="btn btn-link px-0 mb-2"><i class="bi bi-arrow-left"></i> К списку обращений</a>

<div class="card">
    <div class="card-header"><span id="ticketSubject">—</span> <span id="ticketStatusPill"></span></div>
    <div class="chat-scroll" id="ticketMessages" style="max-height:460px;"></div>
    <div class="attach-row">
        <button type="button" class="attach-btn" id="attachBtn" title="Прикрепить файлы"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 6-8.414 8.586a2 2 0 0 0 2.829 2.829l8.414-8.586a4 4 0 1 0-5.657-5.657l-8.379 8.551a6 6 0 1 0 8.485 8.485l8.379-8.551"></path></svg></button>
        <input type="file" id="replyFile" class="d-none" multiple>
        <span class="text-muted-2 small">Прикрепить файл</span>
    </div>
    <div id="attachPreviewWrap"></div>
    <div class="chat-input-row align-stretch">
        <textarea class="form-control autoheight-input" id="replyInput" rows="2" placeholder="Введите сообщение…" style="flex:1;"></textarea>
        <button class="btn btn-primary btn-send-tall btn-send-wide" id="replySendBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3.714 3.048a.498.498 0 0 0-.683.627l2.843 7.627a2 2 0 0 1 0 1.396l-2.842 7.627a.498.498 0 0 0 .682.627l18-8.5a.5.5 0 0 0 0-.904z"></path><path d="M6 12h16"></path></svg></button>
    </div>
</div>
<script>var ROUTE_ID = <?= json_encode($routeId) ?>;</script>
<?php
$pageScripts = ['support-detail.js'];
require __DIR__ . '/inc/layout_app_bottom.php';
