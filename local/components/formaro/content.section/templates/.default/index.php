<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arResult */
/** @global CMain $APPLICATION */

// Главная раздела — плитки пунктов раздела (меню left папки, его наполняет
// .left.menu_ext.php из инфоблока); над ними — описание раздела инфоблока
// (HTML, если заполнено — как вступление «Гайда по сайту»).
if ($arResult['DESCRIPTION'] !== ''): ?>
<section class="section pt-4 pb-0">
    <div class="section-body">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 col-xl-9">
                    <div class="text-page"><?= $arResult['DESCRIPTION'] ?></div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif;

$APPLICATION->IncludeComponent(
    'bitrix:menu',
    'section-tiles',
    [
        'ROOT_MENU_TYPE' => 'left',
        'MAX_LEVEL' => '1',
        'USE_EXT' => 'Y',
        'ALLOW_MULTI_SELECT' => 'N',
        'MENU_CACHE_TYPE' => 'N',
    ],
    $component,
    ['HIDE_ICONS' => 'Y']
);
