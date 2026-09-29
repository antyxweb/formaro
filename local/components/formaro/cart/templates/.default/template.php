<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arParams */

// Вёрстка — /html/cart.html. Группы поставщиков и итог строит script.js.
?>
<section class="section pt-4" id="cart-page">
    <div class="section-body">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 col-xl-8" id="cart-content">
                    <div id="cart-groups">
                        <?php // Скелетоны, пока грузится корзина. ?>
                        <div class="cart-grid row product-list d-flex flex-wrap">
                            <?php for ($i = 0; $i < 4; $i++): ?>
                            <div class="product-item col-sm-6 col-md-4 col-xl-3 product-skeleton"><?php include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/product_card_skeleton.php'; ?></div>
                            <?php endfor; ?>
                        </div>
                    </div>

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

                            <h6>Итого</h6>
                            <div class="price text-secondary">
                                <b class="text-danger" id="cart-summary-total">0</b> <small class="mr-2">руб</small>
                            </div>
                            <small class="text-muted d-none" id="cart-summary-note">Доставка и способ оплаты — при оформлении заказа</small>
                        </div>

                        <div class="product-card__actions">
                            <button type="button" class="f-button c-success" id="cart-checkout" disabled>Перейти к оформлению</button>
                        </div>
                        <div class="small text-muted px-3 px-lg-4 py-2 d-none" id="cart-checkout-note">Оформление заказа появится на следующем этапе.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
