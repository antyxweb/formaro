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
                $pagerTitle = explode(' ', $arResult['IBLOCK']['NAME']);
                $pagerTitle[0] = '<span class="text-primary">'.$pagerTitle[0].'</span>';
                $arResult['IBLOCK']['NAME'] = implode(' ', $pagerTitle);
                ?>
                <h3 class="h2"><?=$arResult['IBLOCK']['NAME']?></h3>
                <a href="/partners/join/" class="f-button c-gray ml-auto">
                    <svg width="16" height="16" class="d-md-none">
                        <use xlink:href="#icon-arrow-control"></use>
                    </svg>
                    <span class="pl-2 text-uppercase d-none d-md-inline">Стать партнером</span>
                </a>
            </div>
            <h4 class="text-secondary"><?=$arResult['IBLOCK']['DESCRIPTION'];?></h4>
        </div>
    </div>

    <div class="section-body">
        <div class="container-fluid">
            <div class="section-search mb-5">
                <input class="section-search-input" type="text" placeholder="Поиск по категориям">
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

                                        <div class="cat-content-wrap scrollbar-inner mb-2">
                                            <ul>
                                                <?foreach ($arSection['SUB'] as $arSub):?>
                                                    <li class="sect">
                                                        <a href="<?=$arSub['SECTION_PAGE_URL']?>" class="dark_link"><?=$arSub['NAME']?>&nbsp;<span><?=$arSub['ELEMENT_CNT']?></span></a>
                                                    </li>
                                                <?endforeach;?>
                                            </ul>
                                        </div>
                                        <ul>
                                            <li class="sect"><a href="<?=$arSection['SECTION_PAGE_URL']?>" class="text-primary"><u>Все товары</u>&nbsp;<span><?=$arSub['ELEMENT_CNT']?></span></a></li>
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