<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?if (!empty($arResult)):?>
    <div class="header__menu d-none d-xl-flex">
        <?foreach($arResult as $arItem):?>
            <a href="<?=$arItem['LINK']?>" class="<?if($arItem['SELECTED']):?>text-primary<?endif;?>"><?=$arItem['TEXT']?></a>
        <?endforeach?>
    </div>
<?endif?>