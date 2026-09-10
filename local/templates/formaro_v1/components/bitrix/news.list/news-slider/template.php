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
                $pagerTitle[0] = '<span class="text-primary">'.$pagerTitle[0].'</span>';
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
            <div class="main-carousel news-carousel news-list" _data-flickity='{ "cellAlign": "left", "wrapAround": true, "pageDots": false }'>

                <?foreach($arResult["ITEMS"] as $arKey=>$arItem):?>
                <?
                $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
                $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
                ?>
                    <div class="carousel-cell">
                        <div class="news-card" id="<?=$this->GetEditAreaId($arItem['ID']);?>">
                            <a href="<?=$arItem["DETAIL_PAGE_URL"]?>" class="d-block news-card__img">
                                <div class="embed-responsive embed-responsive-4by3" style="background-image: url('<?=$arItem["PREVIEW_PICTURE"]["SRC"]?>')"></div>
                                <div class="badges">
                                    <small class="<?=$arResult['SECTIONS'][$arItem['IBLOCK_SECTION_ID']]['CLASS']?>"><?=$arResult['SECTIONS'][$arItem['IBLOCK_SECTION_ID']]['NAME']?></small><br/>
                                </div>
                            </a>
                            <div class="news-card__info">
                                <h3 title="Дождевик мужской Squall, желтый"><?=$arItem["NAME"]?></h3>
                                <div class="text-secondary mb-2">
                                    <small><?=$arItem["DISPLAY_ACTIVE_FROM"]?></small>
                                </div>
                                <p class="mb-0"><?=$arItem["PREVIEW_TEXT"]?></p>
                            </div>
                            <div class="news-card__actions pb-4">
                                <a href="<?=$arItem["DETAIL_PAGE_URL"]?>" class="ml-0 f-button f-button-inner c-gray">Подробнее</a>
                            </div>
                        </div>
                    </div>
                <?endforeach;?>

            </div>
        </div>
    </div>
</section>
