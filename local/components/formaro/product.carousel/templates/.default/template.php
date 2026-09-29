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
            <div class="main-carousel product-carousel js-product-carousel"
                 data-mode="<?= htmlspecialcharsbx($arParams['MODE']) ?>"
                 data-partner-id="<?= (int)$arParams['PARTNER_ID'] ?>"
                 data-page-size="<?= (int)$arParams['COUNT'] ?>"
                 data-offset="<?= count($arResult['ITEMS']) ?>">
                <?php foreach ($arResult['ITEMS'] as $item): ?>
                <div class="carousel-cell">
                    <?php include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/product_card.php'; ?>
                </div>
                <?php endforeach; ?>
                <?php if ($arResult['HAS_MORE']): ?>
                <div class="carousel-cell carousel-cell-last">
                    <div class="product-card">
                        <button class="load-more-slider" type="button" aria-label="Показать еще">
                            <svg width="36" height="36"><use xlink:href="#icon-plus"></use></svg>
                            <svg width="24" height="24" class="icon-progress d-none"><use xlink:href="#icon-progress"></use></svg>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
