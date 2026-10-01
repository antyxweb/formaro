<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arResult */
/** @global CMain $APPLICATION */

// Вёрстка — как «Ваши заказы»: слева чат, справа меню личного кабинета.
// Чат: список диалогов с продавцами и переписка выбранного (на телефоне —
// по очереди: список → диалог, «Назад»). Содержимое рисует script.js.
?>
<section class="section pt-4">
    <div class="section-body">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 col-xl-9">
                    <div class="personal-chat mr-xl-5 mb-4">
                        <?php if (!$arResult['AUTHORIZED']): ?>
                        <?php $APPLICATION->IncludeComponent('formaro:auth.form', '', ['TITLE' => 'Войдите, чтобы переписываться с продавцами.'], false, ['HIDE_ICONS' => 'Y']); ?>
                        <?php else: ?>
                        <div class="chat-block chat bg-white" id="personal-chat" data-sessid="<?= bitrix_sessid() ?>">
                            <aside class="chat__threads" aria-label="Диалоги с продавцами">
                                <div class="chat__threads-list js-chat-threads" role="list"></div>
                            </aside>
                            <div class="chat__dialog" aria-live="polite">
                                <div class="chat__head js-chat-head"></div>
                                <div class="chat__messages js-chat-messages"></div>
                                <?php // Как в «Чате с клиентами» кабинета партнёра: сверху — «Прикрепить файл», ниже — поле и квадратная кнопка отправки (со скошенным углом, как кнопки сайта). ?>
                                <form class="chat__compose js-chat-compose d-none" novalidate>
                                    <div class="chat__attach-row">
                                        <button type="button" class="chat__attach js-chat-attach" aria-label="Прикрепить файл">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m16 6-8.414 8.586a2 2 0 0 0 2.829 2.829l8.414-8.586a4 4 0 1 0-5.657-5.657l-8.379 8.551a6 6 0 1 0 8.485 8.485l8.379-8.551"/></svg>
                                        </button>
                                        <span class="chat__attach-label js-chat-attach" aria-hidden="true">Прикрепить файл</span>
                                        <input type="file" class="d-none js-chat-file" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
                                    </div>
                                    <div class="chat__files js-chat-files"></div>
                                    <div class="chat__compose-row">
                                        <label for="chat-text" class="sr-only">Сообщение</label>
                                        <textarea id="chat-text" class="form-control chat__input js-chat-text" rows="2" maxlength="4000" placeholder="Напишите сообщение…"></textarea>
                                        <button type="submit" class="f-button c-primary chat__send js-chat-send" aria-label="Отправить" title="Отправить">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.714 3.048a.498.498 0 0 0-.683.627l2.843 7.627a2 2 0 0 1 0 1.396l-2.842 7.627a.498.498 0 0 0 .682.627l18-8.5a.5.5 0 0 0 0-.904z"/><path d="M6 12h16"/></svg>
                                        </button>
                                    </div>
                                    <small class="chat__error text-danger js-chat-error" role="alert"></small>
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
