<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arResult */
/** @global CUser $USER */

// Меню личного кабинета (вёрстка /html/orders.html: блок справа).
// Счётчики — по PARAMS.COUNTER пункта (.left.menu.php раздела /personal/):
// orders — заказы, notify — непрочитанные уведомления, messages — непрочитанные ответы продавцов, cart — позиции корзины, favorites — избранное. Корзину и
// избранное дальше обновляют cart.js/favorites.js (data-атрибуты те же,
// что у счётчиков в шапке).
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
<div class="sidebar-menu personal-sidebar bg-white py-4 px-3 px-lg-4 mb-4">
    <h4 class="mb-4"><span class="text-primary">Личный</span> кабинет</h4>

    <ul class="personal-menu-list header__actions list-unstyled mb-0">
        <?php foreach ($arResult as $item): ?>
        <?php $counter = (string)($item['PARAMS']['COUNTER'] ?? ''); ?>
        <li>
            <a class="header__action-link d-flex justify-content-start" href="<?= htmlspecialcharsbx($item['LINK']) ?>"<?= $item['SELECTED'] ? ' aria-current="page"' : '' ?>>
                <svg class="header__action-icon" width="26" height="28" viewBox="0 0 26 28">
                    <use xlink:href="#<?= htmlspecialcharsbx($item['PARAMS']['ICON'] ?? 'icon-profile') ?>"></use>
                </svg>
                <?php if ($counter !== ''): ?>
                <small class="header__action-count"<?= $countAttrs[$counter] ?? '' ?>><?= (int)($counts[$counter] ?? 0) ?></small>
                <?php endif; ?>
                <span<?= $item['SELECTED'] ? ' class="font-weight-bold"' : '' ?>><?= htmlspecialcharsbx($item['TEXT']) ?></span>
            </a>
        </li>
        <?php endforeach; ?>
        <?php if ($USER->IsAuthorized()): ?>
        <li>
            <a class="header__action-link d-flex justify-content-start" href="/?logout=yes&amp;<?= bitrix_sessid_get() ?>">
                <svg class="header__action-icon" width="26" height="28" viewBox="0 0 26 28">
                    <use xlink:href="#icon-exit"></use>
                </svg>
                <span>Выйти</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</div>
