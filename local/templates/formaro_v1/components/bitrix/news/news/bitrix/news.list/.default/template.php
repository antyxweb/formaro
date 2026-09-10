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

<div class="row" id="pagerGrid">
    <?foreach($arResult["ITEMS"] as $arItem):?>
        <?
        $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
        $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
        ?>
        <div class="col-12 col-sm-6 col-md-4 col-xl-3 mb-4">
            <div class="news-card h-100 d-flex flex-column" id="<?=$this->GetEditAreaId($arItem['ID']);?>">
                <a href="<?=$arItem["DETAIL_PAGE_URL"]?>" class="d-block news-card__img">
                    <div class="embed-responsive embed-responsive-4by3" style="background-image: url('<?=$arItem["PREVIEW_PICTURE"]["SRC"]?>')"></div>
                    <div class="badges">
                        <small class="<?=$arResult['SECTIONS'][$arItem['IBLOCK_SECTION_ID']]['CLASS']?>"><?=$arResult['SECTIONS'][$arItem['IBLOCK_SECTION_ID']]['NAME']?></small><br/>
                    </div>
                </a>
                <div class="news-card__info">
                    <h3><?=$arItem["NAME"]?></h3>
                    <div class="text-secondary mb-2">
                        <small><?=$arItem["DISPLAY_ACTIVE_FROM"]?></small>
                    </div>
                    <p class="mb-0"><?=$arItem["PREVIEW_TEXT"]?></p>
                </div>
                <div class="news-card__actions pb-4 mt-auto">
                    <a href="<?=$arItem["DETAIL_PAGE_URL"]?>" class="ml-0 f-button f-button-inner c-gray">Подробнее</a>
                </div>
            </div>
        </div>
    <?endforeach;?>

    <?if($arParams["DISPLAY_BOTTOM_PAGER"]):?>
        <div class="col-12" id="pagerNav">
            <?=$arResult["NAV_STRING"]?>
        </div>
    <?endif;?>

</div>
