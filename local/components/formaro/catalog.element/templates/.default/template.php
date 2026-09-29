<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arParams */
/** @var array $arResult */

// Вёрстка — /html/product-detail.html. Блок #product-detail целиком
// заменяет script.js при переходе на вариант (цвет/размер) без
// перезагрузки страницы.

$fmt = static fn($n) => number_format((float)$n, 0, '', ' ');
$name = htmlspecialcharsbx($arResult['NAME']);

// Описание партнёра — HTML из визуального редактора кабинета: чистим от
// скриптов и прочего опасного; без него — краткое описание как текст.
$description = '';
if (trim(strip_tags((string)$arResult['DESCRIPTION'])) !== '') {
    $sanitizer = new CBXSanitizer();
    $sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_MIDDLE);
    $description = $sanitizer->SanitizeHtml($arResult['DESCRIPTION']);
} elseif (trim((string)$arResult['SHORT_DESCRIPTION']) !== '') {
    $description = '<p>' . nl2br(htmlspecialcharsbx(trim($arResult['SHORT_DESCRIPTION']))) . '</p>';
}

$propsTable = static function (array $props): string {
    $html = '';
    foreach ($props as $prop) {
        $html .= '<tr><td><h6>' . htmlspecialcharsbx($prop['NAME']) . '</h6></td><td>' . htmlspecialcharsbx($prop['VALUE']) . '</td></tr>';
    }

    return $html;
};
?>
<div id="product-detail" data-product-id="<?= (int)$arResult['ID'] ?>">
<section class="section pt-4">
    <div class="section-body">
        <div class="container-fluid">
            <div class="row">
                <div class="product-gallery-wrap col-12 col-xl-8">
                    <div id="product-gallery">
                        <div class="favorite" data-product-id="<?= (int)$arResult['ID'] ?>">
                            <button class="button-icon">
                                <svg width="24" height="24"><use xlink:href="#icon-favorite-stroke"></use></svg>
                            </button>
                            <button class="button-icon d-none">
                                <svg width="24" height="24"><use xlink:href="#icon-favorite"></use></svg>
                            </button>
                        </div>

                        <div class="main-carousel product-gallery-carousel">
                            <?php foreach ($arResult['GALLERY'] as $image): ?>
                            <div class="carousel-cell">
                                <a href="<?= htmlspecialcharsbx($image['SRC']) ?>" style="height: <?= (int)$image['HEIGHT'] ?>px;width: <?= (int)$image['WIDTH'] ?>px;" data-fancybox="gallery" data-caption="<?= $name ?>">
                                    <img src="<?= htmlspecialcharsbx($image['SRC']) ?>" alt="<?= $name ?>">
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="product-option-wrap col-12 col-xl-4">
                    <div id="product-option">

                        <div class="bg-white py-4 px-3 px-lg-4">
                            <div class="d-flex mb-3">
                                <div class="badges">
                                    <?php foreach ($arResult['BADGES'] as $badge): ?>
                                    <small class="<?= htmlspecialcharsbx($badge['class']) ?>"><?= htmlspecialcharsbx($badge['text']) ?></small>
                                    <?php endforeach; ?>
                                </div>

                                <?php if ($arResult['SKU'] !== ''): ?>
                                <small class="ml-auto text-muted text-right pt-2">Артикул: <?= htmlspecialcharsbx($arResult['SKU']) ?></small>
                                <?php endif; ?>
                            </div>

                            <div class="price text-secondary mb-2">
                                <b class="text-danger" data-price="<?= (float)$arResult['PRICE'] ?>"><?= $fmt($arResult['PRICE']) ?></b> <small class="mr-2">руб/шт.</small>
                                <?php if ($arResult['OLD_PRICE']): ?>
                                <s><?= $fmt($arResult['OLD_PRICE']) ?> <small>руб/шт.</small></s>
                                <?php endif; ?>
                            </div>

                            <div class="text-secondary small mb-3">
                                <?php if ($arResult['STOCK'] > 0): ?>
                                В наличии: <?= (int)$arResult['STOCK'] ?> шт.
                                <?php elseif ($arResult['IS_PREORDER']): ?>
                                Под заказ
                                <?php else: ?>
                                Нет в наличии
                                <?php endif; ?>
                            </div>

                            <div class="row text-secondary pb-2">
                                <div class="col-6">
                                    <a href="javascript:void(0);" data-click-tab="reviews" class="d-block">
                                        <svg width="16" height="16" class="star">
                                            <use xlink:href="#icon-star-outline"></use>
                                        </svg>
                                        4,8 · 23 оценки
                                    </a>
                                </div>
                                <div class="col-6 text-right">
                                    <a href="javascript:void(0);" data-click-tab="faq" class="d-block">5 вопросов</a>
                                </div>
                            </div>

                            <?php if ($arResult['COLORS']): ?>
                            <div class="mt-4">
                                <h6 class="mb-2">Цвет: <span><?= htmlspecialcharsbx($arResult['COLOR']) ?></span></h6>
                                <div class="color-option">
                                    <?php foreach ($arResult['COLORS'] as $variant): ?>
                                    <a href="<?= htmlspecialcharsbx($variant['URL']) ?>" class="color-item js-product-variant" title="<?= htmlspecialcharsbx($variant['VALUE']) ?>" data-product-id="<?= (int)$variant['ID'] ?>">
                                        <div class="embed-responsive embed-responsive-3by4<?= $variant['ACTIVE'] ? ' border-primary' : '' ?>" style="background-image: url('<?= htmlspecialcharsbx($variant['IMAGE']) ?>')"></div>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($arResult['SIZES']): ?>
                            <div class="mt-4">
                                <h6 class="mb-2">Размер: <span><?= htmlspecialcharsbx($arResult['SIZE']) ?></span></h6>
                                <div class="size-option mb-1">
                                    <?php foreach ($arResult['SIZES'] as $variant): ?>
                                    <a href="<?= htmlspecialcharsbx($variant['URL']) ?>" class="size-item js-product-variant" data-product-id="<?= (int)$variant['ID'] ?>"><small<?= $variant['ACTIVE'] ? ' class="border-primary"' : '' ?>><?= htmlspecialcharsbx($variant['VALUE']) ?></small></a>
                                    <?php endforeach; ?>
                                </div>

                                <a href="#" class="text-muted small">Таблица размеров</a>
                            </div>
                            <?php endif; ?>

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
                                <input type="text" data-min="1" data-max="<?= (int)$arResult['STOCK'] ?>" value="1">
                            </div>
                            <button class="cart-add f-button c-primary">В корзину</button>
                            <button class="cart-remove f-button c-gray text-secondary d-none">
                                <svg width="16" height="16"><use xlink:href="#icon-delete"></use></svg>
                                <span class="pl-2">Удалить</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="product-tabs-wrap col-12 col-xl-8">

                    <div class="tab-wrap">
                        <div class="tab-head d-flex flex-wrap pt-5 pb-4">
                            <button class="f-button c-secondary mb-3" data-tab="description">Описание</button>
                            <?php if ($arResult['PROPS']): ?>
                            <button class="f-button c-white mb-3" data-tab="features">Характеристики</button>
                            <?php endif; ?>
                            <button class="f-button c-white mb-3" data-tab="reviews">Отзывы (12)</button>
                            <button class="f-button c-white mb-3" data-tab="faq">Вопрос/ответ (5)</button>
                        </div>

                        <div class="tab-body mr-xl-5">
                            <div class="tab-content tab-description">
                                <?php if ($description !== ''): ?>
                                <?= $description ?>
                                <?php else: ?>
                                <p class="text-secondary">Описание пока не добавлено.</p>
                                <?php endif; ?>
                            </div>
                            <?php if ($arResult['PROPS']): ?>
                            <div class="tab-content tab-features d-none">
                                <table class="mb-4 w-100">
                                    <?= $propsTable($arResult['PROPS']) ?>
                                </table>
                            </div>
                            <?php endif; ?>
                            <?php include __DIR__ . '/reviews_faq_stub.php'; ?>
                        </div>
                    </div>
                </div>

                <div class="product-sidebar-wrap col-12 col-xl-4 pt-5">
                    <div id="product-sidebar">
                        <?php if ($arResult['PARTNER']): $partner = $arResult['PARTNER']; ?>
                        <h5 class="mb-0" style="height: 60px">О продавце</h5>
                        <div class="partners-card">
                            <div class="row mx-0">
                                <div class="col-12 col-md-8 partners-card__info order-2 order-md-1">
                                    <h3 title="<?= htmlspecialcharsbx($partner['NAME']) ?>"><?= htmlspecialcharsbx($partner['NAME']) ?></h3>
                                    <p class="text-secondary"><?= $partner['TEXT'] ?></p>
                                </div>
                                <?php if ($partner['LOGO']): ?>
                                <div class="col-12 col-md-4 py-md-3 pr-md-4 order-1 order-md-2">
                                    <div class="embed-responsive embed-responsive-4by3" style="background-image: url('<?= htmlspecialcharsbx($partner['LOGO']) ?>')"></div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="partners-card__actions">
                                <a href="<?= htmlspecialcharsbx($partner['URL']) ?>" class="f-button c-success">Каталог товаров</a>
                                <a href="<?= htmlspecialcharsbx($partner['URL']) ?>" class="f-button">Чат с продавцом</a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($arResult['SHORT_PROPS']): ?>
                        <h5 class="mb-0<?= $arResult['PARTNER'] ? ' mt-5' : '' ?>" style="height: 60px">Краткие характеристики</h5>
                        <table class="mb-4">
                            <?= $propsTable($arResult['SHORT_PROPS']) ?>
                        </table>
                        <?php if (count($arResult['PROPS']) > count($arResult['SHORT_PROPS'])): ?>
                        <a href="javascript:void(0);" data-click-tab="features">Все характеристики</a>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
</div>
