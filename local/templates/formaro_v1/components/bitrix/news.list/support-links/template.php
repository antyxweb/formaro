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

<section class="section light-gray-stripe">
    <div class="section-header mb-5">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <?
                $pagerTitle = explode(' ', $arResult['SECTION_NAME']);
                $pagerTitle[0] = '<span class="text-primary">'.$pagerTitle[0].'</span>';
                $arResult['SECTION_NAME'] = implode(' ', $pagerTitle);
                ?>
                <h3 class="h2"><?=$arResult['SECTION_NAME']?></h3>
                <?=$arResult['SECTION_DESCRIPTION']?>
            </div>
        </div>
    </div>

    <div class="section-body">
        <div class="container-fluid">
            <div class="support-list row">
                <?foreach($arResult["ITEMS"] as $arKey=>$arItem):?>
                    <?
                    $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
                    $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
                    ?>
                    <div class="col-12 col-lg-6 col-xl-3 mb-4 mb-xl-0">
                        <a href="<?=$arItem['PROPERTIES']['LINK']['VALUE']?>" class="support__link" target="_blank">
                                            <span class="support__icon-wrapper bg-primary">
                                                <svg class="support__link-icon" width="24" height="24">
                                                    <use xlink:href="#<?=$arItem['CODE']?>"></use>
                                                </svg>
                                            </span>
                            <?=$arItem['NAME']?>
                        </a>
                    </div>
                <?endforeach;?>
            </div>
        </div>
    </div>
</section>
