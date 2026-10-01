<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arResult */
/** @global CMain $APPLICATION */

// Вёрстка — как «Ваши заказы»: слева список, справа меню личного кабинета.
// Над списком — выбор флажками и действия с выбранными (прочитать, удалить —
// видны, когда что-то выбрано) и «Прочитать все». Непрочитанные — с синей
// иконкой и точкой. Клик по уведомлению — прочитано и переход к заказу.
$e = static fn($s) => htmlspecialcharsbx((string)$s);
?>
<section class="section pt-4">
    <div class="section-body">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 col-xl-9">
                    <div class="personal-notify mr-xl-5" id="personal-notify" data-sessid="<?= bitrix_sessid() ?>">
                        <?php if (!$arResult['AUTHORIZED']): ?>
                        <?php $APPLICATION->IncludeComponent('formaro:auth.form', '', ['TITLE' => 'Войдите, чтобы увидеть свои уведомления.'], false, ['HIDE_ICONS' => 'Y']); ?>
                        <?php else: ?>
                        <div class="notify-block notify-empty bg-white py-4 px-3 px-lg-4 mb-4<?= $arResult['ITEMS'] ? ' d-none' : '' ?>">
                            <p class="mb-0">Уведомлений пока нет. Здесь появятся новости о ваших заказах: смена статуса, подтверждение оплаты.</p>
                        </div>

                        <?php if ($arResult['ITEMS']): ?>
                        <div class="notify-toolbar catalog-options d-flex flex-wrap align-items-center mb-3">
                            <div class="custom-control custom-checkbox mr-3">
                                <input type="checkbox" class="custom-control-input js-notify-select-all" id="notify-select-all">
                                <label class="custom-control-label" for="notify-select-all">Выбрать все</label>
                            </div>
                            <div class="notify-toolbar__selected d-none align-items-center js-notify-selected">
                                <button type="button" class="button-icon bg-light js-notify-read-selected" title="Отметить прочитанными" aria-label="Отметить прочитанными">
                                    <svg width="16" height="16" aria-hidden="true"><use xlink:href="#icon-checkbox-tick"></use></svg>
                                </button>
                                <button type="button" class="button-icon js-notify-delete-selected" title="Удалить выбранные" aria-label="Удалить выбранные">
                                    <svg width="16" height="16" aria-hidden="true"><use xlink:href="#icon-delete"></use></svg>
                                </button>
                            </div>
                            <a href="#" class="ml-auto js-notify-read-all<?= $arResult['UNREAD'] ? '' : ' d-none' ?>" role="button">Прочитать все</a>
                        </div>
                        <small class="d-block text-danger mb-3 js-notify-error" role="alert"></small>

                        <ul class="notify-list list-unstyled mb-0">
                            <?php foreach ($arResult['ITEMS'] as $item): ?>
                            <li class="notify-item bg-white mb-3<?= $item['IS_READ'] ? '' : ' is-unread' ?><?= $item['LINK'] !== '' ? ' has-link' : '' ?> js-notify-item" data-id="<?= (int)$item['ID'] ?>" data-link="<?= $e($item['LINK']) ?>">
                                <div class="custom-control custom-checkbox notify-item__check">
                                    <input type="checkbox" class="custom-control-input js-notify-choose" id="notify-<?= (int)$item['ID'] ?>" value="<?= (int)$item['ID'] ?>">
                                    <label class="custom-control-label" for="notify-<?= (int)$item['ID'] ?>"><span class="sr-only">Выбрать</span></label>
                                </div>
                                <span class="notify-item__icon" aria-hidden="true">
                                    <svg width="26" height="28" viewBox="0 0 26 28"><use xlink:href="#<?= $e($item['ICON']) ?>"></use></svg>
                                </span>
                                <div class="notify-item__body">
                                    <div class="notify-item__head">
                                        <?php if ($item['LINK'] !== ''): ?>
                                        <a href="<?= $e($item['LINK']) ?>" class="notify-item__title js-notify-open"><?= $e($item['TITLE']) ?></a>
                                        <?php else: ?>
                                        <span class="notify-item__title"><?= $e($item['TITLE']) ?></span>
                                        <?php endif; ?>
                                        <small class="notify-item__date text-muted"><?= $e($item['DATE']) ?></small>
                                    </div>
                                    <?php if ($item['MESSAGE'] !== ''): ?>
                                    <div class="notify-item__message text-secondary"><?= $e($item['MESSAGE']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="button-icon notify-item__delete js-notify-delete" title="Удалить" aria-label="Удалить уведомление">
                                    <svg width="16" height="16" aria-hidden="true"><use xlink:href="#icon-delete"></use></svg>
                                </button>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
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
