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
                <h5 class="text-secondary"><?echo $arResult["PREVIEW_TEXT"];?></h5>
            </div>

            <div class="section-body">
                <form>
                    <div class="form-group">
                        <label for="exampleInputEmail1">Ваша почта</label>
                        <input type="email" name="EMAIL" class="form-control form-control-lg" id="exampleInputEmail1" aria-describedby="emailHelp">
                        <small id="emailHelp" class="form-text text-muted">Мы никогда не передадим ваш адрес электронной почты третьим лицам</small>
                    </div>
                    <div class="form-group">
                        <label>Какая темя вас интересует</label>
                        <select class="custom-select form-control-lg mr-sm-2" name="SECTION">
                            <option>Выбирете...</option>
                            <?foreach ($arResult['SECTIONS'] as $arSection):?>
                                <option value="<?=$arSection['ID']?>"><?=$arSection['NAME']?></option>
                            <?endforeach;?>
                        </select>
                    </div>
                    <div class="form-group form-check mb-4">
                        <input type="checkbox" name="AGREE" class="form-check-input" id="exampleCheck1">
                        <label class="form-check-label" for="exampleCheck1">Я согласен с политикой обработки персональных данных</label>
                    </div>
                    <button type="submit" class="f-button c-primary">Подписаться</button>
                </form>
            </div>
        </div>
    </div>
</section>

