<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arParams */
/** @var array $arResult */

// Секция целиком не выводится, если подборка пуста (например, нет
// активных скидок) — пустая карусель с заголовком выглядела бы сломанной.
if (empty($arResult['ITEMS'])) {
    return;
}

$fmt = static fn($n) => number_format((float)$n, 0, '', ' ');
?>
<section class="section <?= htmlspecialcharsbx($arParams['SECTION_CLASS']) ?>">
    <div class="section-header mb-5">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <h3 class="h2"><span class="text-primary"><?= htmlspecialcharsbx($arParams['TITLE_ACCENT']) ?></span> <?= htmlspecialcharsbx($arParams['TITLE']) ?></h3>
                <a href="<?= htmlspecialcharsbx($arParams['CATALOG_URL']) ?>" class="f-button c-gray ml-auto">
                    <svg width="16" height="16" class="d-md-none">
                        <use xlink:href="#icon-arrow-control"></use>
                    </svg>
                    <span class="pl-2 text-uppercase d-none d-md-inline">Перейти в каталог</span>
                </a>
            </div>
        </div>
    </div>
    <div class="section-body">
        <div class="container-fluid">
            <div class="main-carousel product-carousel">
                <?php foreach ($arResult['ITEMS'] as $item):
                    $url = htmlspecialcharsbx($item['URL']);
                    $name = htmlspecialcharsbx($item['NAME']);
                ?>
                <div class="carousel-cell">
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
                        <div class="product-card__actions">
                            <div class="cart-cnt">
                                <div class="buttons">
                                    <button class="cart-cnt-plus">
                                        <svg width="20" height="20"><use xlink:href="#icon-arrow-up"></use></svg>
                                    </button>
                                    <button class="cart-cnt-minus">
                                        <svg width="20" height="20"><use xlink:href="#icon-arrow-down"></use></svg>
                                    </button>
                                </div>
                                <input type="text" data-min="1" data-max="<?= (int)$item['STOCK'] ?>" value="1">
                            </div>
                            <button class="cart-add f-button c-primary">В корзину</button>
                            <button class="cart-remove f-button c-gray text-secondary d-none">
                                <svg width="16" height="16"><use xlink:href="#icon-delete"></use></svg>
                                <span class="pl-2">Удалить</span>
                            </button>
                        </div>
                        <a href="#" class="stretched-link"></a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
