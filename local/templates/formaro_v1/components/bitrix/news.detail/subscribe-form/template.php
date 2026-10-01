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
    <div class="container-fluid">
        <div class="section-form-block">
            <div class="section-header mb-4">
                <div class="d-flex align-items-center">
                    <?
                    $pagerTitle = explode(' ', $arResult["NAME"]);
                    $pagerTitle[0] = '<span class="text-primary">'.$pagerTitle[0].'</span>';
                    $arResult["NAME"] = implode(' ', $pagerTitle);
                    ?>
                    <h3 class="h3"><?=$arResult["NAME"]?></h3>
                </div>
                <?if($arResult['PARTNER_ID']):?>
                    <h5 class="text-secondary">Первыми узнавайте о новых предложениях, акциях и скидках <?=htmlspecialcharsbx($arResult['PARTNER_NAME'])?></h5>
                <?else:?>
                    <h5 class="text-secondary"><?echo $arResult["PREVIEW_TEXT"];?></h5>
                <?endif;?>
            </div>

            <div class="section-body">
                <?/* Отправка — js/subscribe.js → /local/ajax/subscribe.php (HL-блок «Подписки на рассылку»). */?>
                <form class="js-subscribe" data-form="news" novalidate>
                    <div class="form-group">
                        <label for="subscribe-news-email">Ваша почта</label>
                        <input type="email" name="email" class="form-control form-control-lg" id="subscribe-news-email" aria-describedby="subscribe-news-help" autocomplete="email" required>
                        <small id="subscribe-news-help" class="form-text text-muted">Мы никогда не передадим ваш адрес электронной почты третьим лицам</small>
                    </div>
                    <?if($arResult['PARTNER_ID']):?>
                        <input type="hidden" name="partner" value="<?=(int)$arResult['PARTNER_ID']?>">
                    <?else:?>
                    <div class="form-group">
                        <label for="subscribe-news-topic">Какая тема вас интересует</label>
                        <select class="custom-select form-control-lg mr-sm-2" name="topic" id="subscribe-news-topic">
                            <option value="0">Все темы</option>
                            <?foreach ($arResult['SECTIONS'] as $arSection):?>
                                <option value="<?=(int)$arSection['ID']?>"><?=$arSection['NAME']?></option>
                            <?endforeach;?>
                        </select>
                    </div>
                    <?endif;?>
                    <div class="subscribe-trap" aria-hidden="true">
                        <label for="subscribe-news-website">Сайт</label>
                        <input type="text" name="website" id="subscribe-news-website" tabindex="-1" autocomplete="off">
                    </div>
                    <div class="form-group form-check mb-4">
                        <input type="checkbox" name="consent" value="1" class="form-check-input" id="subscribe-news-consent" required>
                        <label class="form-check-label" for="subscribe-news-consent">Я согласен с <a href="/support/policy/" target="_blank">политикой обработки персональных данных</a></label>
                    </div>
                    <button type="submit" class="f-button c-primary js-subscribe-submit">Подписаться</button>
                    <div class="subscribe-status mt-3 js-subscribe-status" role="status"></div>
                </form>
            </div>
        </div>
    </div>
</section>

