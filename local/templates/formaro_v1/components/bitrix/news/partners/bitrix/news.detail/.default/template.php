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
            <?php // Обложка партнёра (DETAIL_PICTURE) — как в карточках партнёров на главной; на широком экране — баннером (style.css). ?>
            <?if($arResult["DETAIL_PICTURE"]["SRC"]):?>
                <div class="partners-card__img partner-hero__cover">
                    <div class="embed-responsive embed-responsive-16by9" style="background-image: url('<?=$arResult["DETAIL_PICTURE"]["SRC"]?>')"></div>
                </div>
            <?endif;?>
            <div class="row mx-0">
                <div class="col-12  col-lg-8 col-xl-10 partners-card__info order-2 order-lg-1">
                    <?php // Краткое название партнёра (NAME_SHORT), если заполнено, иначе полное. ?>
                    <h1 class="h2 mb-4"><?= trim((string)($arResult["PROPERTIES"]["NAME_SHORT"]["VALUE"] ?? '')) !== '' ? htmlspecialcharsbx(trim((string)$arResult["PROPERTIES"]["NAME_SHORT"]["VALUE"])) : $arResult["NAME"] ?></h1>
                    <div class="text-secondary">
                        <?if($arResult["DETAIL_TEXT"] <> ''):?>
                            <?echo $arResult["DETAIL_TEXT"];?>
                        <?else:?>
                            <p><?echo $arResult["PREVIEW_TEXT"];?></p>
                        <?endif?>
                    </div>
                    <div class="pt-4 d-flex">
                        <?php // Общий вопрос продавцу — новый или существующий диалог в «Чатах и сообщениях». ?>
                        <a href="/personal/messages/?partner=<?= (int)$arResult["ID"] ?>" class="f-button c-success">
                            <svg width="16" height="16" aria-hidden="true"><use xlink:href="#icon-mail"></use></svg>
                            <span class="pl-2">Связаться с продавцом</span>
                        </a>
                    </div>
                </div><?if($arResult["PREVIEW_PICTURE"]["SRC"]):?>
                    <div class="col-12 col-lg-4 col-xl-2 py-md-3 pr-md-4 order-1 order-lg-2">
                        <div class="embed-responsive embed-responsive-4by3" style="background-image: url('<?=$arResult["PREVIEW_PICTURE"]["SRC"]?>')"></div>
                    </div>
                <?endif;?>
            </div>
        </div>
    </div>
</section>