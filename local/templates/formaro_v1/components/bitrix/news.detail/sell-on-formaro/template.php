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

<section class="section bg-image bg-cover bg-overlay" style="background-image: url('<?=$arResult["DETAIL_PICTURE"]["SRC"]?:$arResult["PREVIEW_PICTURE"]["SRC"]?>')">
    <div class="section-header mb-5">
        <div class="container-fluid">
            <div class="d-flex align-items-center mb-4">
                <?
                $pagerTitle = explode(' ', $arResult["NAME"]);
                $pagerTitle[0] = '<span class="bg-primary text-white py-2 pl-4 pr-2 mr-3">'.$pagerTitle[0].'</span>';
                $arResult["NAME"] = implode(' ', $pagerTitle);
                ?>
                <h3 class="h2"><?=$arResult["NAME"]?></h3>
            </div>
            <h4 class="text-white"><?echo $arResult["PREVIEW_TEXT"];?></h4>
        </div>
    </div>

    <div class="section-body">
        <div class="container-fluid">
            <?echo $arResult["DETAIL_TEXT"];?>
        </div>
    </div>
</section>
