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

<section class="section <?=$arParams['SECTION_CLASS']?>">
    <div class="section-header mb-5">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <?
                $pagerTitle = explode(' ', $arParams['PAGER_TITLE']);
                $pagerTitle[0] = '<span class="text-success">'.$pagerTitle[0].'</span>';
                $arParams['PAGER_TITLE'] = implode(' ', $pagerTitle);
                ?>
                <h3 class="h2"><?=$arParams['PAGER_TITLE']?></h3>
                <a href="<?=str_replace('#SITE_DIR#', '', $arResult['LIST_PAGE_URL'])?>" class="f-button c-gray ml-auto">
                    <svg width="16" height="16" class="d-md-none">
                        <use xlink:href="#icon-arrow-control"></use>
                    </svg>
                    <span class="pl-2 text-uppercase d-none d-md-inline">Все <?=$arResult['NAME']?></span>
                </a>
            </div>
        </div>
    </div>
    <div class="section-body">
        <div class="container-fluid">
            <div class="main-carousel partners-carousel partners-list" _data-flickity='{ "cellAlign": "left", "wrapAround": true, "pageDots": false }'>

                <?foreach($arResult["ITEMS"] as $arKey=>$arItem):?>
                <?
                $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
                $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
                ?>
                    <div class="carousel-cell">
                        <div class="partners-card" id="<?=$this->GetEditAreaId($arItem['ID']);?>">
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
                            </div>
                        </div>
                    </div>
                <?endforeach;?>

            </div>
        </div>
    </div>
</section>
