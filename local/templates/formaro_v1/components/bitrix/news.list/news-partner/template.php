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
<?foreach($arResult["ITEMS"] as $arKey=>$arItem):?>
    <?
    $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
    $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
    ?>
    <div class="partners-card mb-5" id="<?=$this->GetEditAreaId($arItem['ID']);?>">

        <?if($arItem["PREVIEW_PICTURE"]["SRC"]):?>
            <div class="partners-card__img">
                <div class="embed-responsive embed-responsive-16by9" style="background-image: url('<?=$arItem["PREVIEW_PICTURE"]["SRC"]?>')"></div>
            </div>
        <?endif;?>
        <div class="row mx-0">
            <div class="col-12 col-md-8 partners-card__info order-2 order-md-1">
                <h3><?=$arItem["NAME"]?></h3>
                <p class="text-secondary"><?=$arItem["PREVIEW_TEXT"]?></p>
            </div>
            <?if($arItem["PROPERTIES"]["LOGO"]["VALUE"]):?>
                <div class="col-12 col-md-4 py-md-3 pr-md-0 order-1 order-md-2">
                    <div class="embed-responsive embed-responsive-4by3" style="background-image: url('<?=CFile::GetPath($arItem["PROPERTIES"]["LOGO"]["VALUE"])?>')"></div>
                </div>
            <?endif;?>
        </div>

        <div class="partners-card__actions">
            <a href="<?=$arItem["DETAIL_PAGE_URL"]?>" class="f-button c-success">Каталог товаров</a>
            <a href="<?=$arItem["DETAIL_PAGE_URL"]?>#chat" class="f-button">Чат с продавцом</a>
        </div>
    </div>
<?php endforeach;?>

