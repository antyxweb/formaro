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

<style>
    .section-first {
        display: none;
    }
</style>
<script>
    document.getElementById('header').classList.add('partner-page');
</script>

<section id="partner-hero" class="section">
    <div class="container-fluid">
        <div class="partners-card">
            <div class="partners-card__img d-md-none">
                <div class="embed-responsive embed-responsive-16by9" style="background-image: url('<?=$arResult["PREVIEW_PICTURE"]["SRC"]?>')"></div>
            </div>
            <div class="row mx-0">
                <div class="col-12  col-lg-8 col-xl-10 partners-card__info order-2 order-lg-1">
                    <h1 class="h2 mb-4"><?echo $arResult["NAME"];?></h1>
                    <div class="text-secondary">
                        <?if($arResult["DETAIL_TEXT"] <> ''):?>
                            <?echo $arResult["DETAIL_TEXT"];?>
                        <?else:?>
                            <p><?echo $arResult["PREVIEW_TEXT"];?></p>
                        <?endif?>
                    </div>
                    <div class="pt-4 d-flex">
                        <a href="/html/catalog.html" class="f-button c-success">Каталог товаров</a>
                        <a href="/html/partner.html" class="f-button">Чат с продавцом</a>
                    </div>
                </div><?if($arResult["PROPERTIES"]["LOGO"]["VALUE"]):?>
                    <div class="col-12 col-lg-4 col-xl-2 py-md-3 pr-md-0 order-1 order-lg-2">
                        <div class="embed-responsive embed-responsive-4by3" style="background-image: url('<?=CFile::GetPath($arResult["PROPERTIES"]["LOGO"]["VALUE"])?>')"></div>
                    </div>
                <?endif;?>
            </div>
        </div>
    </div>
</section>