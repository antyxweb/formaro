<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */

// Вёрстка — /html/orders.html: слева заказы (товары — таблицей, стили
// .product-sizes из css/pages.css), справа меню личного кабинета.
// В колонке «Оплата», пока заказ не оплачен, — ссылки «Скачать счёт» (PDF)
// и «Сообщить об оплате» (script.js → /local/ajax/order_payment_notice.php;
// после — дата сообщения). Под данными заказа — «Написать в чат» (диалог
// с площадкой в «Чатах и сообщениях») и
// «Отменить заказ» (только новый; script.js → /local/ajax/order_cancel.php),
// у выполненного и отменённого — «Повторить заказ» (товары — в корзину).
// Товары заказа (таблица) по умолчанию скрыты — «Показать товары».
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
                                    <div class="col-12 col-md-6 col-lg-4 mb-3 mb-lg-0">
                                        <h6 class="mb-1">Доставка</h6>
                                        <div><?= $e($order['DELIVERY'] ?: '—') ?></div>
                                        <?php if ($order['ADDRESS'] !== ''): ?>
                                        <small class="text-muted"><?= $e($order['ADDRESS']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-12 col-md-6 col-lg-4 mb-3 mb-md-0">
                                        <h6 class="mb-1">Оплата</h6>
                                        <div><?= $e($order['PAYMENT'] ?: '—') ?></div>
                                        <small class="<?= $e($order['PAYMENT_STATUS_CLASS']) ?>"><?= $e($order['PAYMENT_STATUS']) ?></small>
                                        <?php if ($order['INVOICE_URL'] !== ''): ?>
                                        <div class="order-item__payment-links js-order-payment">
                                            <?php if ($order['PAYMENT_NOTICE_DATE'] !== ''): ?>
                                            <?php // Уже сообщил об оплате — счёт больше не нужен. ?>
                                            <small class="text-muted js-order-payment-notice">Вы сообщили об оплате <?= $e($order['PAYMENT_NOTICE_DATE']) ?></small>
                                            <?php else: ?>
                                            <a href="<?= $e($order['INVOICE_URL']) ?>" class="js-order-invoice" download>Скачать счёт</a><?php if ($order['INVOICE_DUE'] !== ''): ?> <small class="text-muted">до <?= $e($order['INVOICE_DUE']) ?></small><?php endif; ?>
                                            <a href="#" class="js-order-payment-notice" data-order-id="<?= (int)$order['ID'] ?>" data-order-number="<?= $e($order['NUMBER']) ?>">Сообщить об оплате</a>
                                            <?php endif; ?>
                                            <small class="text-danger js-order-payment-error" role="alert"></small>
                                        </div>
                                        <?php elseif ($order['INVOICE_NOTE'] !== ''): ?>
                                        <small class="text-muted d-block"><?= $e($order['INVOICE_NOTE']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-12 col-md-6 col-lg-4">
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

                                <div class="order-item__actions d-flex flex-wrap align-items-center mt-4 js-order-actions">
                                    <a href="/personal/messages/" class="f-button c-success">
                                        <svg width="16" height="16"><use xlink:href="#icon-mail"></use></svg>
                                        <span class="pl-2">Написать в чат</span>
                                    </a>
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
                            <div class="product-sizes order-table">
                                <table>
                                    <thead><tr>
                                        <th>Товар</th>
                                        <th>Цена</th>
                                        <th>Количество</th>
                                        <th>Сумма</th>
                                    </tr></thead>
                                    <tbody>
                                        <?php foreach ($order['ITEMS'] as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="order-table__product">
                                                    <?php if ($item['URL'] !== ''): ?>
                                                    <a href="<?= $e($item['URL']) ?>" class="order-table__img" style="background-image: url('<?= $e($item['IMAGE']) ?>')" aria-hidden="true" tabindex="-1"></a>
                                                    <div><a href="<?= $e($item['URL']) ?>"><?= $e($item['NAME']) ?></a>
                                                    <?php else: ?>
                                                    <span class="order-table__img"></span>
                                                    <div><?= $e($item['NAME']) ?>
                                                    <?php endif; ?>
                                                        <?php if ($item['SKU'] !== ''): ?><small class="d-block text-muted">Артикул: <?= $e($item['SKU']) ?></small><?php endif; ?>
                                                        <?php if ($item['COLOR'] !== '' || $item['SIZE'] !== ''): ?>
                                                        <small class="d-block text-muted"><?= $e(implode(', ', array_filter([$item['COLOR'], $item['SIZE']]))) ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="product-sizes__price"><?= $fmt($item['PRICE']) ?> руб/шт.</td>
                                            <td class="text-nowrap"><?= (int)$item['QTY'] ?> шт.</td>
                                            <td class="text-nowrap"><b><?= $fmt($item['SUM']) ?></b> руб</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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
