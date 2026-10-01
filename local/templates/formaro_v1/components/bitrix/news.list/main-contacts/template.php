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
    <p class="mb-4">Подписаться на рассылку</p>

    <?/* Отправка — js/subscribe.js → /local/ajax/subscribe.php (HL-блок «Подписки на рассылку»).
         Шаблон выводится дважды (подвал и меню) — id поля свой у каждого. */?>
    <?$subscribeId = 'subscribe-footer-email-' . $this->randString();?>
    <form class="subscribe-form js-subscribe d-flex flex-wrap mb-4" data-form="footer" novalidate>
        <label for="<?=$subscribeId?>" class="sr-only">Ваш e-mail</label>
        <input type="email" name="email" id="<?=$subscribeId?>" placeholder="Ваш e-mail" class="subscribe-form-input" autocomplete="email" required>
        <span class="subscribe-trap" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off" aria-label="Сайт"></span>
        <button type="submit" class="f-button f-button-inner c-white js-subscribe-submit">Отправить</button>
        <small class="subscribe-status w-100 mt-2 js-subscribe-status" role="status"></small>
        <small class="subscribe-consent w-100 mt-2">Нажимая «Отправить», вы соглашаетесь с <a href="/support/policy/">политикой обработки персональных данных</a></small>
    </form>
</div>
