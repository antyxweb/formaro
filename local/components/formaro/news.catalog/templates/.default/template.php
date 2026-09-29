<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arParams */
/** @var array $arResult */

// Вёрстка — /html/news-detail-3.html (сайдбар детальной новости).
?>
<?php foreach ($arResult['CATEGORIES'] as $category): ?>
<div class="category-list mb-5">
    <div class="category-item">
        <?php if ($category['PICTURE']): ?>
        <div class="bg-cat-img" style="background-image: url('<?= htmlspecialcharsbx($category['PICTURE']) ?>')"></div>
        <?php endif; ?>
        <div class="cat-content">
            <a href="<?= htmlspecialcharsbx($category['URL']) ?>"><h3 class="mb-4"><?= htmlspecialcharsbx($category['NAME']) ?></h3></a>

            <?php if ($category['CHILDREN']): ?>
            <div class="cat-content-wrap mb-2">
                <ul>
                    <?php foreach ($category['CHILDREN'] as $child): ?>
                    <li class="sect"><a href="<?= htmlspecialcharsbx($child['URL']) ?>" class="dark_link"><?= htmlspecialcharsbx($child['NAME']) ?>&nbsp;<span><?= (int)$child['COUNT'] ?></span></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            <ul>
                <li class="sect"><a href="<?= htmlspecialcharsbx($category['URL']) ?>" class="text-primary"><u>Все товары</u>&nbsp;<span><?= (int)$category['COUNT'] ?></span></a></li>
            </ul>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php if ($arResult['PRODUCTS']): ?>
<h5 class="mb-0" style="height: 60px"><?= htmlspecialcharsbx($arParams['~PRODUCTS_TITLE']) ?></h5>
<div class="row product-list d-flex flex-wrap mb-5">
    <?php foreach ($arResult['PRODUCTS'] as $item): ?>
    <div class="product-item col-sm-6 col-md-4 col-xl-6">
        <?php include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/product_card.php'; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
