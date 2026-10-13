<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */

// «Управление рассылками»: категории уведомлений переключателями (сохраняются
// сразу), подписки на новости — списком с «Отписаться», внизу — «Отписаться
// от всех рассылок». Сохранение — script.js → /local/ajax/mailing.php.
$e = static fn($s) => htmlspecialcharsbx((string)$s);
$embed = $arParams['EMBED'] === 'Y';
$state = $arResult['STATE'];

if (!$state) {
    if ($embed) {
        return;
    }
    ?>
    <section class="section pt-4">
        <div class="section-body">
            <div class="container-fluid">
                <div class="mailing-settings mailing-settings--page">
                    <?php if ($arResult['INVALID_LINK']): ?>
                    <div class="alert alert-warning mb-4">Ссылка устарела или повреждена. Откройте её из последнего письма или войдите на сайт.</div>
                    <?php endif; ?>
                    <?php $APPLICATION->IncludeComponent('formaro:auth.form', '', ['TITLE' => 'Войдите, чтобы настроить рассылки.'], false, ['HIDE_ICONS' => 'Y']); ?>
                </div>
            </div>
        </div>
    </section>
    <?php
    return;
}

$attrs = 'data-sessid="' . bitrix_sessid() . '"';
if ($arResult['LINK']) {
    $attrs .= ' data-u="' . $e($arResult['LINK']['u']) . '" data-t="' . $e($arResult['LINK']['t']) . '"';
}
$hasAnything = $state['groups'] || $state['newsletters'];

ob_start();
?>
<div class="mailing-settings<?= $embed ? ' profile-block bg-white py-4 px-3 px-lg-4 mb-4' : '' ?>" id="mailing-settings" <?= $attrs ?>>
    <?php // На странице заголовок — у самой страницы (h1). ?>
    <?php if ($embed): ?><h4 class="mb-2">Рассылки</h4><?php endif; ?>
    <?php if ($arResult['INVALID_LINK']): ?>
    <div class="alert alert-warning mb-4">Ссылка из письма устарела или повреждена — ниже настройки вашей учётной записи.</div>
    <?php endif; ?>
    <p class="text-muted mb-4">
        Письма приходят на <b><?= $e($state['email']) ?></b>.<?= $state['groups'] ? ' Выберите, о каких событиях сообщать.' : '' ?>
        Служебные письма (регистрация, восстановление пароля, оформление заказа и счета) приходят всегда.
    </p>

    <?php foreach ($state['groups'] as $group): ?>
    <div class="mailing-group mb-4 js-mailing-group" data-audience="<?= $e($group['audience']) ?>">
        <?php if (!$embed): ?><h5 class="mb-3"><?= $e($group['title']) ?></h5><?php endif; ?>
        <?php foreach ($group['items'] as $item): ?>
        <?php $id = 'mailing-' . str_replace('.', '-', $item['channel']); ?>
        <div class="custom-control custom-switch mailing-item<?= $arResult['HIGHLIGHT'] === $item['channel'] ? ' is-highlighted' : '' ?>">
            <input type="checkbox" class="custom-control-input js-mailing-channel" id="<?= $id ?>" value="<?= $e($item['channel']) ?>"<?= $item['enabled'] ? ' checked' : '' ?>>
            <label class="custom-control-label" for="<?= $id ?>">
                <?= $e($item['label']) ?>
                <?php if ($item['hint'] !== ''): ?><small class="d-block text-muted"><?= $e($item['hint']) ?></small><?php endif; ?>
            </label>
        </div>
        <?php endforeach; ?>
        <small class="mailing-status js-mailing-status" role="status"></small>
    </div>
    <?php endforeach; ?>

    <?php if ($embed && $arResult['PARTNER']): ?>
    <p class="text-muted mb-4">Письма кабинета партнёра (заказы, выплаты, техподдержка) настраиваются в <a href="/cabinet/notifications/?tab=mailing">кабинете партнёра</a>.</p>
    <?php endif; ?>

    <div class="mailing-group mb-4">
        <h5 class="mb-3">Новости и акции</h5>
        <ul class="list-unstyled mb-0 mailing-newsletters js-mailing-newsletters">
            <?php foreach ($state['newsletters'] as $item): ?>
            <li class="d-flex flex-wrap align-items-center justify-content-between" data-id="<?= (int)$item['id'] ?>">
                <span class="mr-3">
                    <?= $e($item['title']) ?>
                    <?php if ($item['created'] !== ''): ?><small class="d-block text-muted">Подписка с <?= $e($item['created']) ?></small><?php endif; ?>
                </span>
                <button type="button" class="f-button c-gray f-button-sm js-mailing-unsubscribe">Отписаться</button>
            </li>
            <?php endforeach; ?>
        </ul>
        <p class="text-muted mb-0 js-mailing-newsletters-empty<?= $state['newsletters'] ? ' d-none' : '' ?>">
            Вы не подписаны на новости. Подписаться можно в блоке «Будьте в курсе» на главной и на страницах поставщиков.
        </p>
    </div>

    <?php if ($hasAnything): ?>
    <div class="d-flex flex-wrap align-items-center">
        <button type="button" class="f-button c-gray js-mailing-unsubscribe-all">Отписаться от всех рассылок</button>
        <small class="mailing-status js-mailing-all-status" role="status"></small>
    </div>
    <?php endif; ?>
</div>
<?php
$block = ob_get_clean();

if ($embed) {
    echo $block;

    return;
}
?>
<section class="section pt-4">
    <div class="section-body">
        <div class="container-fluid">
            <div class="mailing-settings--page"><?= $block ?></div>
        </div>
    </div>
</section>
