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

<div class="col-12 col-md-6 col-xl-12 d-flex flex-column">

    <h5 class="mb-3"><?=$arResult['SECTION_NAME']?></h5>

    <div class="bg-menu__actions mb-4">
        <?foreach($arResult["ITEMS"] as $arKey=>$arItem):?>
            <?
            $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
            $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
            ?>
            <a class="header__action-link" href="<?=$arItem["PROPERTIES"]['LINK']['VALUE']?>" aria-label="<?=$arItem["NAME"]?>" target="_blank">
                <svg class="header__action-icon" width="20" height="20">
                    <use xlink:href="#<?=$arItem["CODE"]?>"></use>
                </svg>
                <span><?=$arItem["NAME"]?></span>
            </a>
        <?endforeach;?>
    </div>

    <h5 class="mb-3 mt-auto">Будь всегда в форме!</h5>
    <p class="ьи-4">Подписаться на рассылку</p>

    <form action="" class="subscribe-form d-flex mb-4">
        <input type="text" placeholder="Ваш e-mail" class="subscribe-form-input">
        <button class="f-button f-button-inner c-white">Отправить</button>
    </form>
</div>
