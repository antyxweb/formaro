<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arParams */
/** @var array $arResult */

// Вёрстка — /html/orders.html: слева заказы (товары — каруселью
// .product-carousel, её запускает scripts.js), справа меню личного кабинета.
// Под данными заказа — «Чат с продавцом» (пока — страница продавца, как
// в карточке товара: чата на стороне покупателя ещё нет), «Скачать счёт»
// (PDF, пока заказ не оплачен) и «Отменить заказ» (только новый; script.js
// → /local/ajax/order_cancel.php).
$fmt = static fn($n) => number_format((float)$n, 0, '', ' ');
$e = static fn($s) => htmlspecialcharsbx((string)$s);
?>
<section class="section pt-4">
    <div class="section-body">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 col-xl-9">
                    <div class="orders mr-xl-5" id="personal-orders" data-sessid="<?= bitrix_sessid() ?>">
                        <?php if (!$arResult['AUTHORIZED']): ?>
                        <div class="bg-white py-4 px-3 px-lg-4 mb-4">
                            <p class="mb-4">Войдите, чтобы увидеть свои заказы.</p>
                            <a href="/login/?backurl=<?= urlencode('/personal/orders/') ?>" class="f-button c-primary">Войти</a>
                        </div>
                        <?php elseif (!$arResult['ORDERS']): ?>
                        <div class="bg-white py-4 px-3 px-lg-4 mb-4">
                            <p class="mb-4">У вас пока нет заказов.</p>
                            <a href="<?= $e($arParams['CATALOG_URL']) ?>" class="f-button c-primary">Перейти в каталог</a>
                        </div>
                        <?php endif; ?>

                        <?php foreach ($arResult['ORDERS'] as $order): ?>
                        <div class="order-item mb-5" id="order-<?= (int)$order['ID'] ?>">
                            <div class="order-item__head bg-white py-4 px-3 px-lg-4">
                                <div class="d-flex flex-wrap align-items-center mb-3">
                                    <h4 class="mb-0 mr-3">Заказ <?= $e($order['NUMBER']) ?></h4>
                                    <span class="badge <?= $e($order['STATUS_CLASS']) ?> mr-3 js-order-status"><?= $e($order['STATUS']) ?></span>
                                    <small class="text-muted ml-md-auto"><?= $e($order['DATE']) ?></small>
                                </div>

                                <div class="row">
                                    <div class="col-12 col-md-6 col-lg-3 mb-3 mb-lg-0">
                                        <h6 class="mb-1">Продавец</h6>
                                        <?php if ($order['PARTNER']['url'] !== ''): ?>
                                        <a href="<?= $e($order['PARTNER']['url']) ?>"><?= $e($order['PARTNER']['name']) ?></a>
                                        <?php else: ?>
                                        <?= $e($order['PARTNER']['name']) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-12 col-md-6 col-lg-3 mb-3 mb-lg-0">
                                        <h6 class="mb-1">Доставка</h6>
                                        <div><?= $e($order['DELIVERY'] ?: '—') ?></div>
                                        <?php if ($order['ADDRESS'] !== ''): ?>
                                        <small class="text-muted"><?= $e($order['ADDRESS']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-12 col-md-6 col-lg-3 mb-3 mb-md-0">
                                        <h6 class="mb-1">Оплата</h6>
                                        <div><?= $e($order['PAYMENT'] ?: '—') ?></div>
                                        <small class="<?= $e($order['PAYMENT_STATUS_CLASS']) ?>"><?= $e($order['PAYMENT_STATUS']) ?></small>
                                    </div>
                                    <div class="col-12 col-md-6 col-lg-3">
                                        <h6 class="mb-1"><?= (int)$order['QTY'] ?> шт. на сумму</h6>
                                        <div class="price text-secondary">
                                            <b class="text-danger"><?= $fmt($order['TOTAL']) ?></b> <small>руб</small>
                                        </div>
                                        <?php if ($order['DISCOUNT'] > 0): ?>
                                        <small class="text-muted">
                                            Скидка <?= $fmt($order['DISCOUNT']) ?> руб<?= $order['COUPON'] !== '' ? ' по промокоду ' . $e($order['COUPON']) : '' ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if ($order['COMMENT'] !== ''): ?>
                                <small class="d-block text-muted mt-3">Комментарий: <?= $e($order['COMMENT']) ?></small>
                                <?php endif; ?>

                                <?php if ($order['PARTNER']['url'] !== '' || $order['INVOICE_URL'] !== '' || $order['CAN_CANCEL']): ?>
                                <div class="order-item__actions d-flex flex-wrap align-items-center mt-4 js-order-actions">
                                    <?php if ($order['PARTNER']['url'] !== ''): ?>
                                    <a href="<?= $e($order['PARTNER']['url']) ?>" class="f-button c-success">
                                        <svg width="16" height="16"><use xlink:href="#icon-mail"></use></svg>
                                        <span class="pl-2">Чат с продавцом</span>
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($order['INVOICE_URL'] !== ''): ?>
                                    <a href="<?= $e($order['INVOICE_URL']) ?>" class="f-button c-primary js-order-invoice" download>
                                        <svg width="16" height="16"><use xlink:href="#icon-bill"></use></svg>
                                        <span class="pl-2">Скачать счёт</span>
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($order['CAN_CANCEL']): ?>
                                    <button type="button" class="f-button c-gray text-secondary js-order-cancel" data-order-id="<?= (int)$order['ID'] ?>" data-order-number="<?= $e($order['NUMBER']) ?>">
                                        <svg width="16" height="16"><use xlink:href="#icon-close"></use></svg>
                                        <span class="pl-2">Отменить заказ</span>
                                    </button>
                                    <?php endif; ?>
                                    <small class="text-danger js-order-cancel-error" role="alert"></small>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="main-carousel product-carousel">
                                <?php foreach ($order['ITEMS'] as $item): ?>
                                <div class="carousel-cell">
                                    <div class="product-card">
                                        <div class="product-card__img">
                                            <?php if ($item['URL'] !== ''): ?>
                                            <a href="<?= $e($item['URL']) ?>" class="embed-responsive embed-responsive-1by1" style="background-image: url('<?= $e($item['IMAGE']) ?>')"></a>
                                            <?php else: ?>
                                            <div class="embed-responsive embed-responsive-1by1"></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="product-card__info">
                                            <?php if ($item['URL'] !== ''): ?>
                                            <a href="<?= $e($item['URL']) ?>"><h3 title="<?= $e($item['NAME']) ?>"><?= $e($item['NAME']) ?></h3></a>
                                            <?php else: ?>
                                            <h3 title="<?= $e($item['NAME']) ?>"><?= $e($item['NAME']) ?></h3>
                                            <?php endif; ?>
                                            <div class="price mb-2">
                                                <b class="text-danger"><?= $fmt($item['PRICE']) ?></b> <small>руб/шт. × <?= (int)$item['QTY'] ?></small>
                                            </div>
                                            <div class="text-secondary order-item__meta">
                                                <?php if ($item['SKU'] !== ''): ?><small>Артикул: <?= $e($item['SKU']) ?></small><?php endif; ?>
                                                <?php if ($item['COLOR'] !== '' || $item['SIZE'] !== ''): ?>
                                                <small><?= $e(implode(', ', array_filter([$item['COLOR'], $item['SIZE']]))) ?></small>
                                                <?php endif; ?>
                                                <small>Сумма: <?= $fmt($item['SUM']) ?> руб</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-12 col-xl-3">
                    <?php include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/personal_sidebar.php'; ?>
                </div>
            </div>
        </div>
    </div>
</section>
