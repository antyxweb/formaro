<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?if (!empty($arResult)):?>
    <div class="col-12 col-md-6 col-xl-12">
        <?
        $previousLevel = 0;
        $divider = true;
        $level = 1;
        foreach($arResult as $arItem):?>

            <?if ($previousLevel && $arItem["DEPTH_LEVEL"] < $previousLevel):?>
                <?=str_repeat("</ul>", ($previousLevel - $arItem["DEPTH_LEVEL"]));?>

                <?if($level == 2):?>
                    </div>
                    <div class="col-12 col-md-6 col-xl-12">
                <?endif;?>
                <?$level++;?>
            <?endif?>

            <?if ($arItem["DEPTH_LEVEL"] == 1):?>
                <a href="<?=$arItem["LINK"]?>" class="h5 d-block text-white text-decoration-none mb-3"><?=$arItem["TEXT"]?></a>
                <ul class="submenu mb-4">
            <?else:?>
                <li class="submenu-item" <?if($arItem['SELECTED']):?>style="opacity: 0.5"<?endif;?>>
                    <svg width="12" height="12">
                        <use xlink:href="#icon-arrow-control"></use>
                    </svg>
                    <a class="submenu-item-link" href="<?=$arItem["LINK"]?>">
                        <span class="name"><?=$arItem["TEXT"]?></span>
                    </a>
                </li>
            <?endif?>

            <?$previousLevel = $arItem["DEPTH_LEVEL"];?>
        <?endforeach?>

        <?if ($previousLevel > 1)://close last item tags?>
            <?=str_repeat("</ul>", ($previousLevel-1) );?>
        <?endif?>

    </div>
<?endif?>