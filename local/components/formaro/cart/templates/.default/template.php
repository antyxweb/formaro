<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arParams */
/** @var array $arResult */

// Вёрстка — /html/cart.html. Группы поставщиков и итог строит script.js.
// Способы доставки и оплаты — плитки из вёрстки, выбор — радиокнопки
// (коды — CartCheckoutService::DELIVERIES/PAYMENTS). Данные покупателя —
// поля блока «Данные покупателя» карточки заказа в кабинете партнёра.
$deliveries = [
    ['code' => 'cdek', 'name' => 'СДЭК', 'icon' => 'icon-delivery'],
    ['code' => 'dellin', 'name' => 'Деловые линии', 'icon' => 'icon-delivery'],
    ['code' => 'pek', 'name' => 'ПЭК', 'icon' => 'icon-delivery'],
    ['code' => 'other', 'name' => 'Другая ТК', 'icon' => 'icon-exchange'],
];
$payments = [
    ['code' => 'invoice', 'name' => 'По счету', 'icon' => 'icon-list-done'],
];
$buyer = array_map('htmlspecialcharsbx', $arResult['BUYER']);
$choices = static function (string $name, array $options): void {
    foreach ($options as $i => $option): ?>
        <div class="col-12 col-lg-6 col-xl-3 mb-4 mb-xl-0">
            <label class="cart-choice d-block h-100 mb-0">
                <input type="radio" class="sr-only" name="<?= $name ?>" value="<?= $option['code'] ?>"<?= $i === 0 ? ' checked' : '' ?>>
                <span class="support__link">
                    <span class="support__icon-wrapper">
                        <svg class="support__link-icon" width="24" height="24"><use xlink:href="#<?= $option['icon'] ?>"></use></svg>
                    </span>
                    <?= $option['name'] ?>
                </span>
            </label>
        </div>
    <?php endforeach;
};
?>
<section class="section pt-4" id="cart-page">
    <div class="section-body">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 col-xl-8" id="cart-content">
                    <div class="cart-success bg-white py-4 px-3 px-lg-4 mb-5 d-none" id="cart-success" role="status" tabindex="-1">
                        <h4 class="cart-success-title mb-3">Заказ оформлен</h4>
                        <p class="mb-2">Спасибо! Продавцы получили заказ и свяжутся с вами для подтверждения.</p>
                        <ul class="mb-4" id="cart-success-list"></ul>
                        <a href="<?= htmlspecialcharsbx($arParams['CATALOG_URL']) ?>" class="f-button c-primary">Продолжить покупки</a>
                    </div>

                    <div id="cart-groups">
                        <?php // Скелетоны, пока грузится корзина. ?>
                        <div class="cart-grid row product-list d-flex flex-wrap">
                            <?php for ($i = 0; $i < 4; $i++): ?>
                            <div class="product-item col-sm-6 col-md-4 col-xl-3 product-skeleton"><?php include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/product_card_skeleton.php'; ?></div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <form class="d-none" id="cart-checkout-form" autocomplete="on" novalidate>
                        <h3 class="my-5">Данные покупателя</h3>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="cart-buyer-name">Имя / название компании <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="cart-buyer-name" name="buyer[name]" value="<?= $buyer['name'] ?>" autocomplete="name" maxlength="255" required>
                                <div class="invalid-feedback">Укажите имя или название компании</div>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="cart-buyer-phone">Телефон <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="cart-buyer-phone" name="buyer[phone]" value="<?= $buyer['phone'] ?>" autocomplete="tel" maxlength="50" placeholder="+7 999 123-45-67" required>
                                <div class="invalid-feedback">Укажите телефон</div>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="cart-buyer-email">E-mail</label>
                                <input type="email" class="form-control" id="cart-buyer-email" name="buyer[email]" value="<?= $buyer['email'] ?>" autocomplete="email" maxlength="255">
                                <div class="invalid-feedback">Проверьте e-mail</div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="cart-address">Адрес доставки <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="cart-address" name="buyer[address]" value="<?= $buyer['address'] ?>" autocomplete="street-address" maxlength="255" aria-describedby="cart-address-help" required>
                            <small id="cart-address-help" class="form-text text-muted">Город и адрес или пункт выдачи транспортной компании</small>
                            <div class="invalid-feedback">Укажите адрес доставки</div>
                        </div>
                        <div class="form-group mb-0">
                            <label for="cart-buyer-comment">Комментарий к заказу</label>
                            <textarea class="form-control" id="cart-buyer-comment" name="buyer[comment]" rows="2" maxlength="2000"></textarea>
                        </div>

                        <h3 class="my-5">Способ доставки</h3>
                        <div class="support-list row"><?php $choices('delivery', $deliveries); ?></div>

                        <h3 class="my-5">Способ оплаты</h3>
                        <div class="support-list row"><?php $choices('payment', $payments); ?></div>
                    </form>

                    <div class="py-5 d-none" id="cart-empty">
                        <p class="mb-4">В корзине пока ничего нет</p>
                        <a href="<?= htmlspecialcharsbx($arParams['CATALOG_URL']) ?>" class="f-button c-primary">Перейти в каталог</a>
                    </div>
                </div>

                <div class="product-option-wrap col-12 col-xl-4 d-none" id="cart-summary-wrap">
                    <div id="product-option" class="cart-summary">
                        <div class="bg-white py-4 px-3 px-lg-4">
                            <form class="promocode mb-4" id="cart-coupon-form" autocomplete="off">
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" placeholder="Промокод" id="cart-coupon-input" maxlength="50">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="submit">Применить</button>
                                    </div>
                                </div>
                                <div id="cart-coupon-state"></div>
                            </form>

                            <h6 class="mb-1"><span id="cart-summary-count">0 позиций</span> на сумму:</h6>
                            <div class="lead text-secondary ml-auto mb-4">
                                <b class="text-dark" id="cart-summary-sum">0</b> <small class="mr-2">руб</small>
                            </div>

                            <div id="cart-summary-discount-wrap" class="d-none">
                                <h6 class="mb-1">Скидка:</h6>
                                <div class="lead text-secondary ml-auto mb-4">
                                    <b class="text-dark" id="cart-summary-discount">0</b> <small class="mr-2">руб</small>
                                </div>
                            </div>

                            <div id="cart-summary-coupon-wrap" class="d-none">
                                <h6 class="mb-1">Скидка по промокоду:</h6>
                                <div class="lead text-secondary ml-auto mb-4">
                                    <b class="text-dark" id="cart-summary-coupon">0</b> <small class="mr-2">руб</small>
                                </div>
                            </div>

                            <div id="cart-summary-delivery-wrap">
                                <h6 class="mb-1" id="cart-summary-delivery-title">Доставка:</h6>
                                <div class="text-secondary mb-4" id="cart-summary-delivery">Стоимость сообщит продавец после оформления</div>
                            </div>

                            <h6>Итого</h6>
                            <div class="price text-secondary">
                                <b class="text-danger" id="cart-summary-total">0</b> <small class="mr-2">руб</small>
                            </div>
                        </div>

                        <div class="product-card__actions">
                            <button type="button" class="f-button c-gray text-secondary" id="cart-checkout" disabled>Выберите товары</button>
                        </div>
                        <div class="small px-3 px-lg-4 py-2 d-none" id="cart-checkout-note" role="alert"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
