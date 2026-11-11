<?php
/**
 * Сообщения / Избранное / Корзина / профиль — ссылки шапки (header.php).
 * «Сообщения» вместо «Заказов» (правки клиента 19.10: заказы есть в
 * кабинете за «Войти») — чат с площадкой, счётчик — непрочитанные ответы.
 * Подключается дважды: в шапке и в копии для раздела партнёра
 * (.partner-actions — шапка там уезжает вверх). Счётчики считаются один
 * раз; дальше их обновляют cart.js / favorites.js / live-counters.js и
 * formaro:cart по data-*-count — во всех копиях сразу.
 *
 * @global CUser $USER
 */
if (!isset($headerActionsCounts)) {
    // Авторизованному — сразу числа из аккаунта (без мигания 0): непрочитанные
    // сообщения, избранное, позиции корзины.
    $headerActionsCounts = ['messages' => 0, 'favorites' => 0, 'cart' => 0];
    if ($USER->IsAuthorized() && \Bitrix\Main\Loader::includeModule('formaro.cabinet')) {
        $headerActionsUserId = (int)$USER->GetID();
        $headerActionsCounts = [
            'messages' => \Formaro\Cabinet\Service\BuyerChatService::countUnread($headerActionsUserId),
            'favorites' => (new \Formaro\Cabinet\Repository\FavoriteRepository())->count($headerActionsUserId),
            'cart' => count((new \Formaro\Cabinet\Repository\CartRepository())->listItems($headerActionsUserId)),
        ];
    }
}
?>
    <a class="header__action-link d-none d-sm-flex" href="/personal/messages/" aria-label="Сообщения" title="Сообщения и чат с площадкой">
        <svg class="header__action-icon" width="22" height="22">
            <use xlink:href="#icon-chat"></use>
        </svg>
        <small class="header__action-count" data-messages-count><?= (int)$headerActionsCounts['messages'] ?></small>
        <span class="d-none d-xl-inline">Сообщения</span>
    </a>
    <a class="header__action-link d-none d-sm-flex" href="/personal/favorites/" aria-label="Избранное" title="Избранное">
        <svg class="header__action-icon" width="20" height="20">
            <use xlink:href="#icon-favorites"></use>
        </svg>
        <small class="header__action-count" data-favorites-count><?= (int)$headerActionsCounts['favorites'] ?></small>
        <span class="d-none d-xl-inline">Избранное</span>
    </a>
    <a class="header__action-link" href="/personal/cart/" aria-label="Корзина" title="Корзина">
        <svg class="header__action-icon" width="20" height="20">
            <use xlink:href="#icon-shopping"></use>
        </svg>
        <small class="header__action-count" data-cart-count><?= (int)$headerActionsCounts['cart'] ?></small>
        <span class="d-none d-xl-inline">Корзина</span>
    </a>

    <?if($USER->IsAuthorized()):?>
        <a class="header__action-link" href="/personal/profile/" aria-label="Профиль" title="Профиль">
            <svg class="header__action-icon" width="20" height="20">
                <use xlink:href="#icon-profile"></use>
            </svg>
            <span class="d-none d-sm-inline"><?=$USER->GetFirstName()?></span>
        </a>
    <?else:?>
        <a class="header__action-link" href="/personal/profile/" aria-label="Войти" title="Войти">
            <svg class="header__action-icon" width="20" height="20">
                <use xlink:href="#icon-profile"></use>
            </svg>
            <span class="d-none d-sm-inline">Войти</span>
        </a>
    <?endif;?>
