<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arResult */
/** @var array $arParams */
/** @global CMain $APPLICATION */

// Меню раздела справа на текстовых страницах (Покупателям, Партнерам, О нас,
// Поддержка) — меню типа left раздела; заголовок — название раздела
// (.section.php), первое слово — синим, как у «Кабинета покупателя».
if (empty($arResult)) {
    return;
}
$title = (string)($arParams['TITLE'] ?? '');
$words = explode(' ', $title, 2);
?>
<nav class="sidebar-menu section-sidebar bg-white py-4 px-3 px-lg-4 mb-4" aria-label="<?= htmlspecialcharsbx($title) ?>">
    <?php if ($title !== ''): ?>
    <h4 class="mb-4"><span class="text-primary"><?= htmlspecialcharsbx($words[0]) ?></span><?= isset($words[1]) ? ' ' . htmlspecialcharsbx($words[1]) : '' ?></h4>
    <?php endif; ?>
    <ul class="section-sidebar__list list-unstyled mb-0">
        <?php foreach ($arResult as $item): ?>
        <li>
            <a class="section-sidebar__link<?= $item['SELECTED'] ? ' is-active' : '' ?>" href="<?= htmlspecialcharsbx($item['LINK']) ?>"<?= $item['SELECTED'] ? ' aria-current="page"' : '' ?>>
                <svg width="12" height="12" aria-hidden="true"><use xlink:href="#icon-arrow-control"></use></svg>
                <span><?= htmlspecialcharsbx($item['TEXT']) ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</nav>
