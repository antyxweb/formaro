<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */

// Вёрстка — /html/orders.html: слева заказы (товары — каруселью
// .product-carousel, её запускает scripts.js), справа меню личного кабинета.
// В колонке «Оплата», пока заказ не оплачен, — ссылки «Скачать счёт» (PDF)
// и «Сообщить об оплате» (script.js → /local/ajax/order_payment_notice.php;
// после — дата сообщения). Под данными заказа — «Чат с продавцом» (диалог
// с продавцом в «Чатах и сообщениях», с вопросом по этому заказу) и
// «Отменить заказ» (только новый; script.js → /local/ajax/order_cancel.php),
// у выполненного и отменённого — «Повторить заказ» (товары — в корзину).
// Товары заказа (карусель) по умолчанию скрыты — «Показать товары».
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
                        <?php $APPLICATION->IncludeComponent('formaro:auth.form', '', ['TITLE' => 'Войдите, чтобы увидеть свои заказы.'], false, ['HIDE_ICONS' => 'Y']); ?>
                        <?php elseif (!$arResult['ORDERS']): ?>
                        <div class="personal-block bg-white py-4 px-3 px-lg-4 mb-4">
                            <p class="mb-4">У вас пока нет заказов.</p>
                            <a href="<?= $e($arParams['CATALOG_URL']) ?>" class="f-button c-primary">Перейти в каталог</a>
                        </div>
                        <?php endif; ?>

                        <?php foreach ($arResult['ORDERS'] as $order): ?>
                        <div class="order-item mb-5" id="order-<?= (int)$order['ID'] ?>">
                            <div class="order-item__head bg-white py-4 px-3 px-lg-4">
                                <div class="d-flex flex-wrap align-items-center mb-3">
                                    <h4 class="mb-0 mr-3">Заказ <?= $e($order['NUMBER']) ?></h4>
                                    <span class="order-status <?= $e($order['STATUS_CLASS']) ?> mr-3 js-order-status"><?= $e($order['STATUS']) ?></span>
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
                                        <?php if ($order['INVOICE_URL'] !== ''): ?>
                                        <div class="order-item__payment-links js-order-payment">
                                            <?php if ($order['PAYMENT_NOTICE_DATE'] !== ''): ?>
                                            <?php // Уже сообщил об оплате — счёт больше не нужен. ?>
                                            <small class="text-muted js-order-payment-notice">Вы сообщили об оплате <?= $e($order['PAYMENT_NOTICE_DATE']) ?></small>
                                            <?php else: ?>
                                            <a href="<?= $e($order['INVOICE_URL']) ?>" class="js-order-invoice" download>Скачать счёт</a>
                                            <a href="#" class="js-order-payment-notice" data-order-id="<?= (int)$order['ID'] ?>" data-order-number="<?= $e($order['NUMBER']) ?>">Сообщить об оплате</a>
                                            <?php endif; ?>
                                            <small class="text-danger js-order-payment-error" role="alert"></small>
                                        </div>
                                        <?php endif; ?>
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

                                <?php if ($order['PARTNER']['url'] !== '' || $order['CAN_CANCEL'] || $order['CAN_REPEAT']): ?>
                                <div class="order-item__actions d-flex flex-wrap align-items-center mt-4 js-order-actions">
                                    <?php if ($order['PARTNER']['url'] !== ''): ?>
                                    <a href="/personal/messages/?partner=<?= (int)$order['PARTNER']['id'] ?>&amp;order=<?= urlencode($order['NUMBER']) ?>" class="f-button c-success">
                                        <svg width="16" height="16"><use xlink:href="#icon-mail"></use></svg>
                                        <span class="pl-2">Чат с продавцом</span>
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($order['CAN_CANCEL']): ?>
                                    <?php // На телефоне — только иконка (подпись — для экранного диктора и в подсказке). ?>
                                    <button type="button" class="f-button c-gray text-secondary ml-auto js-order-cancel" data-order-id="<?= (int)$order['ID'] ?>" data-order-number="<?= $e($order['NUMBER']) ?>" aria-label="Отменить заказ" title="Отменить заказ">
                                        <svg width="16" height="16" aria-hidden="true"><use xlink:href="#icon-close"></use></svg>
                                        <span class="pl-2 d-none d-sm-inline">Отменить заказ</span>
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($order['CAN_REPEAT']): ?>
                                    <?php // Товары заказа — снова в корзину (script.js → /local/ajax/order_repeat.php). ?>
                                    <?php // На телефоне — только иконка, как у «Отменить заказ» (подпись — для экранного диктора и в подсказке). ?>
                                    <button type="button" class="f-button c-primary ml-auto js-order-repeat" data-order-id="<?= (int)$order['ID'] ?>" data-order-number="<?= $e($order['NUMBER']) ?>" aria-label="Повторить заказ" title="Повторить заказ">
                                        <svg width="16" height="16" viewBox="0 0 20 22" aria-hidden="true"><use xlink:href="#icon-icon-case"></use></svg>
                                        <span class="pl-2 d-none d-sm-inline">Повторить заказ</span>
                                    </button>
                                    <?php endif; ?>
                                    <small class="text-danger js-order-cancel-error" role="alert"></small>
                                </div>
                                <?php endif; ?>
                            </div>

                            <?php // Под карточкой заказа; товары по умолчанию скрыты, история — во всплывающем окне (script.js). ?>
                            <div class="order-item__links d-flex flex-wrap mt-3">
                                <a href="#order-items-<?= (int)$order['ID'] ?>" class="order-item__toggle js-order-items-toggle" role="button" aria-expanded="false" aria-controls="order-items-<?= (int)$order['ID'] ?>">
                                    <span class="js-order-items-toggle-text">Показать товары</span> (<?= count($order['ITEMS']) ?>)
                                    <svg width="16" height="16" aria-hidden="true"><use xlink:href="#icon-arrow-down"></use></svg>
                                </a>
                                <a href="#" class="js-order-history" role="button" data-order-number="<?= $e($order['NUMBER']) ?>">История заказа</a>
                                <template class="js-order-history-list">
                                    <ul class="order-history">
                                        <?php foreach ($order['HISTORY'] as $entry): ?>
                                        <li class="order-history__item">
                                            <div class="order-history__text"><?= $e($entry['TEXT']) ?></div>
                                            <small class="text-muted"><?= $e($entry['AUTHOR']) ?> · <?= $e($entry['DATE']) ?></small>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </template>
                            </div>

                            <div class="order-item__items" id="order-items-<?= (int)$order['ID'] ?>" hidden>
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
