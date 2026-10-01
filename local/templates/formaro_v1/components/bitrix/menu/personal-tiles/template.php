<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arResult */
/** @global CUser $USER */

// Главная личного кабинета (/personal/): разделы плитками, как блоки «Связь
// с поддержкой» (.support-list, шаблон news.list/support-links). Пункты и
// иконки — меню типа left раздела /personal/ (то же, что в меню справа,
// personal-sidebar); у заказов, корзины и избранного — счётчик на иконке.
if (empty($arResult)) {
    return;
}

$counts = ['orders' => 0, 'notify' => 0, 'messages' => 0, 'cart' => 0, 'favorites' => 0];
if ($USER->IsAuthorized() && \Bitrix\Main\Loader::includeModule('formaro.cabinet')) {
    $userId = (int)$USER->GetID();
    $counts['orders'] = (new \Formaro\Cabinet\Repository\OrderRepository())->countByUser($userId);
    $counts['notify'] = \Formaro\Cabinet\Repository\NotificationRepository::forBuyer()->countUnread($userId);
    $counts['messages'] = \Formaro\Cabinet\Service\BuyerChatService::countUnread($userId);
    $counts['cart'] = count((new \Formaro\Cabinet\Repository\CartRepository())->listItems($userId));
    $counts['favorites'] = (new \Formaro\Cabinet\Repository\FavoriteRepository())->count($userId);
}
$countAttrs = ['orders' => ' data-orders-count', 'notify' => ' data-notify-count', 'messages' => ' data-messages-count', 'cart' => ' data-cart-count', 'favorites' => ' data-favorites-count'];
?>
<section class="section pt-4">
    <div class="section-body">
        <div class="container-fluid">
            <div class="support-list personal-tiles row">
                <?php foreach ($arResult as $item): ?>
                <?php $counter = (string)($item['PARAMS']['COUNTER'] ?? ''); ?>
                <div class="col-12 col-md-6 col-xl-4 mb-4">
                    <a href="<?= htmlspecialcharsbx($item['LINK']) ?>" class="support__link">
                        <span class="support__icon-wrapper bg-primary">
                            <svg class="support__link-icon" width="26" height="28" viewBox="0 0 26 28" aria-hidden="true">
                                <use xlink:href="#<?= htmlspecialcharsbx($item['PARAMS']['ICON'] ?? 'icon-profile') ?>"></use>
                            </svg>
                            <?php if ($counter !== ''): ?>
                            <?php // Ноль скрыт; js/live-counters.js и скрипты корзины/избранного обновляют число, при нуле — прячут (data-hide-zero). ?>
                            <small class="personal-tiles__count<?= empty($counts[$counter]) ? ' d-none' : '' ?>"<?= $countAttrs[$counter] ?? '' ?> data-hide-zero><?= (int)($counts[$counter] ?? 0) ?></small>
                            <?php endif; ?>
                        </span>
                        <?= htmlspecialcharsbx($item['TEXT']) ?>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
