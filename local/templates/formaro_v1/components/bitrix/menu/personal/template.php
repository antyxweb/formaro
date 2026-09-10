<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?if (!empty($arResult)):?>
    <div class="col-12 col-md-6 col-xl-12">
        <?foreach($arResult as $arKey=>$arItem):?>
            <?if($arItem['DEPTH_LEVEL'] == 1):?>
                <a href="<?=$arItem["LINK"]?>" class="h5 d-block text-white text-decoration-none mb-3"><?=$arItem["TEXT"]?></a>

                <div class="bg-menu__actions mb-4">
            <?else:?>
                <a class="header__action-link" href="<?=$arItem['LINK']?>" aria-label="Профиль">
                    <svg class="header__action-icon" width="20" height="20">
                        <use xlink:href="#<?=$arItem['PARAMS']['ICON']?>"></use>
                    </svg>
                    <span><?=$arItem['TEXT']?></span>
                </a>
            <?endif;?>
        <?endforeach?>

                    <?if($USER->IsAuthorized()):?>
                        <a class="header__action-link" href="/?logout=yes&<?=bitrix_sessid_get()?>" data-popup-opener="profile" aria-label="Профиль">
                            <svg class="header__action-icon" width="20" height="20">
                                <use xlink:href="#icon-exit"></use>
                            </svg>
                            <span>Выйти</span>
                        </a>
                    <?else:?>
                        <a class="header__action-link" href="/login/" data-popup-opener="profile" aria-label="Профиль">
                            <svg class="header__action-icon" width="20" height="20">
                                <use xlink:href="#icon-exit"></use>
                            </svg>
                            <span>Войти</span>
                        </a>
                    <?endif;?>
        </div>
    </div>
<?endif?>