<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arResult */

// Главная страница раздела (Покупателям, Партнерам, Поддержка): подразделы
// плитками, как блоки «Связь с поддержкой» (.support-list, шаблон
// news.list/support-links). Пункты и иконки — меню типа left раздела
// (.left.menu.php, параметр ICON — иконка спрайта, рисуется через
// currentColor белым на синем круге).
if (empty($arResult)) {
    return;
}
?>
<section class="section pt-4">
    <div class="section-body">
        <div class="container-fluid">
            <div class="support-list section-tiles row">
                <?php foreach ($arResult as $item): ?>
                <div class="col-12 col-md-6 col-xl-4 mb-4">
                    <a href="<?= htmlspecialcharsbx($item['LINK']) ?>" class="support__link">
                        <span class="support__icon-wrapper bg-primary">
                            <svg class="support__link-icon" width="30" height="30" aria-hidden="true">
                                <use xlink:href="#<?= htmlspecialcharsbx($item['PARAMS']['ICON'] ?? 'icon-info') ?>"></use>
                            </svg>
                        </span>
                        <?= htmlspecialcharsbx($item['TEXT']) ?>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
