<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arResult */

// Вёрстка — как «Ваши заказы»: слева чат, справа меню личного кабинета.
// Чат: список диалогов с продавцами и переписка выбранного (на телефоне —
// по очереди: список → диалог, «Назад»). Содержимое рисует script.js.
$backUrl = $_SERVER['REQUEST_URI'] ?? '/personal/messages/';
?>
<section class="section pt-4">
    <div class="section-body">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 col-xl-9">
                    <div class="personal-chat mr-xl-5 mb-4">
                        <?php if (!$arResult['AUTHORIZED']): ?>
                        <div class="chat-block bg-white py-4 px-3 px-lg-4">
                            <p class="mb-4">Войдите, чтобы переписываться с продавцами.</p>
                            <a href="/login/?backurl=<?= urlencode($backUrl) ?>" class="f-button c-primary">Войти</a>
                        </div>
                        <?php else: ?>
                        <div class="chat-block chat bg-white" id="personal-chat" data-sessid="<?= bitrix_sessid() ?>">
                            <aside class="chat__threads" aria-label="Диалоги с продавцами">
                                <div class="chat__threads-list js-chat-threads" role="list"></div>
                            </aside>
                            <div class="chat__dialog" aria-live="polite">
                                <div class="chat__head js-chat-head"></div>
                                <div class="chat__messages js-chat-messages"></div>
                                <form class="chat__compose js-chat-compose d-none" novalidate>
                                    <div class="chat__files js-chat-files"></div>
                                    <div class="chat__compose-row">
                                        <button type="button" class="chat__attach js-chat-attach" title="Прикрепить файл" aria-label="Прикрепить файл">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                        </button>
                                        <input type="file" class="d-none js-chat-file" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
                                        <label for="chat-text" class="sr-only">Сообщение</label>
                                        <textarea id="chat-text" class="form-control chat__input js-chat-text" rows="1" maxlength="4000" placeholder="Напишите сообщение…"></textarea>
                                        <button type="submit" class="f-button c-primary chat__send js-chat-send" aria-label="Отправить">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.714 3.048a.498.498 0 0 0-.683.627l2.843 7.627a2 2 0 0 1 0 1.396l-2.842 7.627a.498.498 0 0 0 .682.627l18-8.5a.5.5 0 0 0 0-.904z"/><path d="M6 12h16"/></svg>
                                            <span class="d-none d-sm-inline pl-2">Отправить</span>
                                        </button>
                                    </div>
                                    <small class="d-block text-danger js-chat-error" role="alert"></small>
                                </form>
                            </div>
                        </div>
                        <script>window.FORMARO_CHAT = <?= json_encode($arResult['DATA'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-12 col-xl-3">
                    <?php include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/personal_sidebar.php'; ?>
                </div>
            </div>
        </div>
    </div>
</section>
