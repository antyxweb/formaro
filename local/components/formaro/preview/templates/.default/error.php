<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arResult */
?>
<section class="section pt-4">
    <div class="section-body">
        <div class="container-fluid py-5">
            <p class="mb-4"><?= htmlspecialcharsbx($arResult['ERROR']) ?></p>
            <a href="/cabinet/" class="f-button c-primary">Перейти в кабинет</a>
        </div>
    </div>
</section>
