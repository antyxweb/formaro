<?php
/**
 * Карточка товара витрины (.product-card) — общая разметка для
 * серверных списков: карусели formaro:product.carousel, товары новости
 * formaro:news.catalog. Клиентская копия — js/catalog-common.js
 * (FormaroProductCard.html), держать их одинаковыми.
 *
 * Ожидает $item — строку ProductCardService::toItem(): ID, NAME, URL,
 * IMAGE, SKU, STOCK, IS_PREORDER, PRICE, OLD_PRICE, BADGES.
 * Обёртку (.carousel-cell / .product-item) добавляет вызывающий шаблон.
 */
/** @var array $item */
$fmt = static fn($n) => number_format((float)$n, 0, '', ' ');
$url = htmlspecialcharsbx($item['URL']);
$name = htmlspecialcharsbx($item['NAME']);
?>
<div class="product-card">
    <div class="product-card__img">
        <a href="<?= $url ?>" class="embed-responsive embed-responsive-1by1" style="background-image: url('<?= htmlspecialcharsbx($item['IMAGE']) ?>')"></a>
        <?php if ($item['BADGES']): ?>
        <div class="badges">
            <?php foreach ($item['BADGES'] as $badge): ?>
            <small class="<?= htmlspecialcharsbx($badge['class']) ?>"><?= htmlspecialcharsbx($badge['text']) ?></small><br/>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="favorite" data-product-id="<?= (int)$item['ID'] ?>">
            <button class="button-icon">
                <svg width="20" height="20"><use xlink:href="#icon-favorite-stroke"></use></svg>
            </button>
            <button class="button-icon d-none">
                <svg width="20" height="20"><use xlink:href="#icon-favorite"></use></svg>
            </button>
        </div>
    </div>
    <div class="product-card__info">
        <a href="<?= $url ?>"><h3 title="<?= $name ?>"><?= $name ?></h3></a>
        <div class="price mb-2">
            <?php if ($item['OLD_PRICE']): ?>
            <s><?= $fmt($item['OLD_PRICE']) ?></s> <b class="text-danger" data-price="<?= (float)$item['PRICE'] ?>"><?= $fmt($item['PRICE']) ?></b> <small>руб/шт.</small>
            <?php else: ?>
            <b class="text-danger" data-price="<?= (float)$item['PRICE'] ?>"><?= $fmt($item['PRICE']) ?></b> <small>руб/шт.</small>
            <?php endif; ?>
        </div>
        <div class="text-secondary">
            <small>Артикул: <?= htmlspecialcharsbx($item['SKU']) ?></small>
            <?php if ($item['STOCK'] > 0): ?>
            <small>В наличии: <?= (int)$item['STOCK'] ?> шт.</small>
            <?php elseif ($item['IS_PREORDER']): ?>
            <small>Под заказ</small>
            <?php else: ?>
            <small>Нет в наличии</small>
            <?php endif; ?>
        </div>
    </div>
    <?php
    // Корзина (js/cart.js): под заказ — без ограничения количества (max 0),
    // нет в наличии и не под заказ — купить нельзя.
    $canBuy = $item['STOCK'] > 0 || $item['IS_PREORDER'];
    ?>
    <div class="product-card__actions" data-product-id="<?= (int)$item['ID'] ?>">
        <div class="cart-cnt">
            <div class="buttons">
                <button class="cart-cnt-plus">
                    <svg width="20" height="20"><use xlink:href="#icon-arrow-up"></use></svg>
                </button>
                <button class="cart-cnt-minus">
                    <svg width="20" height="20"><use xlink:href="#icon-arrow-down"></use></svg>
                </button>
            </div>
            <input type="text" data-min="1" data-max="<?= $item['IS_PREORDER'] ? 0 : (int)$item['STOCK'] ?>" value="1">
        </div>
        <button class="cart-add f-button c-primary"<?= $canBuy ? '' : ' disabled' ?>>В корзину</button>
        <button class="cart-remove f-button c-gray text-secondary d-none">
            <svg width="16" height="16"><use xlink:href="#icon-delete"></use></svg>
            <span class="pl-2">Удалить</span>
        </button>
    </div>
    <a href="#" class="stretched-link"></a>
</div>
