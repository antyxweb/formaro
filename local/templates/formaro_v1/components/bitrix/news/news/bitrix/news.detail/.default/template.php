<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);
?>

<div class="news-gallery-wrap col-12 <?if($arResult['PROPERTIES']['CATALOG_SECTION_ID']['VALUE'] || $arResult['PROPERTIES']['PARTNER_ID']['VALUE']):?>col-xl-8<?endif;?>">
    <?if($arParams["DISPLAY_PICTURE"]!="N" && is_array($arResult["DETAIL_PICTURE"])):?>
        <div class="news-card__img">
            <div class="embed-responsive embed-responsive-16by9" style="background-image: url('<?=$arResult["DETAIL_PICTURE"]["SRC"]?>')"></div>
        </div>
    <?endif?>

    <div class="news-content pb-5">
        <div class="d-flex align-items-center py-4">
            <div class="d-flex align-items-center text-primary">
                <div class="badges">
                    <small class="<?=$arResult['SECTIONS'][$arResult['IBLOCK_SECTION_ID']]['CLASS']?>"><?=$arResult['SECTIONS'][$arResult['IBLOCK_SECTION_ID']]['NAME']?></small><br/>
                </div>
            </div>
            <div class="ml-auto text-muted"><?=$arResult["DISPLAY_ACTIVE_FROM"]?></div>
        </div>

        <?if($arResult["DETAIL_TEXT"] <> ''):?>
            <?echo $arResult["DETAIL_TEXT"];?>
        <?else:?>
            <p><?echo $arResult["PREVIEW_TEXT"];?></p>
        <?endif?>
    </div>
</div>