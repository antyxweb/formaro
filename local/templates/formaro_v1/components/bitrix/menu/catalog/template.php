<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?php
$half = (count($arResult)/2)-1;
$divider = true;
?>

<?if (!empty($arResult)):?>
    <div class="col-12 col-md-6 col-xl-12">
        <?foreach($arResult as $arKey=>$arItem):?>
            <?if($arItem['DEPTH_LEVEL'] == 1):?>
                <a href="<?=$arItem["LINK"]?>" class="h5 d-block text-white text-decoration-none mb-3"><?=$arItem["TEXT"]?></a>

                <ul class="submenu">
            <?else:?>
                <?if($arKey > $half && $divider):?>
                    <?$divider = false;?>
                        </ul>
                    </div>
                    <div class="col-12 col-md-6 col-xl-12">
                        <h5 class="mb-3 d-none d-md-block d-xl-none" style="opacity: 0;">empty</h5>
                        <ul class="submenu">
                <?endif;?>

                <li class="submenu-item" <?if($arItem['SELECTED']):?>style="opacity: 0.5"<?endif;?>>
                    <svg width="12" height="12">
                        <use xlink:href="#icon-arrow-control"></use>
                    </svg>
                    <a class="submenu-item-link" href="<?=$arItem['LINK']?>">
                        <span class="name"><?=$arItem['TEXT']?></span>
                    </a>
                </li>
            <?endif;?>
        <?endforeach?>
        </ul>
    </div>
<?endif?>