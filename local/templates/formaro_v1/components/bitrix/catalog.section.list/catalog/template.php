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

// Нет категорий с товарами (например, у партнёра) — блок не выводим.
if (empty($arResult['SECTIONS_LIST'])) {
    return;
}
?>

<?
// SECTION_CLASS — класс секции (по умолчанию серая полоса, как на главной);
// HIDE_HEADER = Y — без заголовка блока (корень /catalog/: заголовок уже
// в шапке страницы).
$sectionClass = (string)($arParams['SECTION_CLASS'] ?? '') !== '' ? $arParams['SECTION_CLASS'] : 'light-gray-stripe';
?>
<section class="section <?=htmlspecialcharsbx($sectionClass)?>">
    <?if(($arParams['HIDE_HEADER'] ?? 'N') !== 'Y'):?>
    <div class="section-header mb-5">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <?
                // Необязательные параметры вызова (страница партнёра):
                // BLOCK_TITLE — свой заголовок вместо названия инфоблока,
                // SHOW_PARTNER_BUTTON / SHOW_DESCRIPTION = N — скрыть кнопку
                // «Стать партнером» и описание инфоблока.
                $blockTitle = (string)($arParams['~BLOCK_TITLE'] ?? '') !== '' ? $arParams['~BLOCK_TITLE'] : $arResult['IBLOCK']['~NAME'];
                $pagerTitle = explode(' ', htmlspecialcharsbx($blockTitle));
                $pagerTitle[0] = '<span class="text-primary">'.$pagerTitle[0].'</span>';
                ?>
                <h3 class="h2"><?=implode(' ', $pagerTitle)?></h3>
                <?if(($arParams['SHOW_PARTNER_BUTTON'] ?? 'Y') !== 'N'):?>
                <a href="/partners/join/" class="f-button c-gray ml-auto">
                    <svg width="16" height="16" class="d-md-none">
                        <use xlink:href="#icon-arrow-control"></use>
                    </svg>
                    <span class="pl-2 text-uppercase d-none d-md-inline">Стать партнером</span>
                </a>
                <?endif;?>
            </div>
            <?if(($arParams['SHOW_DESCRIPTION'] ?? 'Y') !== 'N'):?>
            <h4 class="text-secondary"><?=$arResult['IBLOCK']['DESCRIPTION'];?></h4>
            <?endif;?>
        </div>
    </div>
    <?endif;?>

    <div class="section-body">
        <div class="container-fluid">
            <div class="section-search mb-5">
                <input class="section-search-input" type="text" placeholder="Поиск по категориям">
                <button type="button" class="section-search-clear d-none" aria-label="Очистить поиск">
                    <svg width="16" height="16">
                        <use xlink:href="#icon-close"></use>
                    </svg>
                </button>
                <button type="submit">
                    <svg width="20" height="20">
                        <use xlink:href="#icon-search"></use>
                    </svg>
                </button>
            </div>

            <div class="row category-list">
                <?foreach ($arResult['SECTIONS_LIST'] as $arSection):?>
                    <div class="section-search-block col-12 col-md-12 col-lg-6 _col-xl-4 mb-5">
                        <div class="category-item">
                            <div class="row ml-0">
                                <div class="col-4 d-none d-md-block cat-img" style="background-image: url('<?=$arSection['PICTURE']['SRC']?:''?>')"></div>
                                <div class="col-12 col-md-8">
                                    <div class="cat-content">
                                        <a href="<?=$arSection['SECTION_PAGE_URL']?>"><h3 class="mb-4"><?=$arSection['NAME']?></h3></a>

                                        <div class="cat-content-wrap mb-2">
                                            <ul>
                                                <?foreach ($arSection['SUB'] as $arSub):?>
                                                    <li class="sect">
                                                        <a href="<?=$arSub['SECTION_PAGE_URL']?>" class="dark_link"><?=$arSub['NAME']?>&nbsp;<span><?=$arSub['ELEMENT_CNT']?></span></a>
                                                    </li>
                                                <?endforeach;?>
                                            </ul>
                                        </div>
                                        <ul>
                                            <li class="sect"><a href="<?=$arSection['SECTION_PAGE_URL']?>" class="text-primary"><u>Все товары</u>&nbsp;<span><?=$arSection['ELEMENT_CNT']?></span></a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-cat-img d-md-none" style="background-image: url('<?=$arSection['PICTURE']['SRC']?:''?>')"></div>
                        </div>
                    </div>
                <?endforeach;?>
            </div>
        </div>
    </div>
</section>