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

if($arResult["VARIABLES"]['SECTION_CODE']) {
    $arFilter = array('IBLOCK_ID'=>$arParams['IBLOCK_ID'], 'CODE'=>$arResult["VARIABLES"]['SECTION_CODE']);
    $rsSections = CIBlockSection::GetList(array('LEFT_MARGIN' => 'ASC'), $arFilter, true, array(), Array("nPageSize"=>1));
    while ($arSection = $rsSections->GetNext())
    {
        $title = $arSection['DESCRIPTION']?:$arSection['NAME'];
        $APPLICATION->SetPageProperty("title", $title);
        $APPLICATION->SetTitle($title);
    }
}
?>

<?if($arParams["USE_RSS"]=="Y"):?>
	<?
	$rss_url = CComponentEngine::makePathFromTemplate($arResult["FOLDER"].$arResult["URL_TEMPLATES"]["rss_section"], array_map("urlencode", $arResult["VARIABLES"]));
	if(method_exists($APPLICATION, 'addheadstring'))
		$APPLICATION->AddHeadString('<link rel="alternate" type="application/rss+xml" title="'.$rss_url.'" href="'.$rss_url.'" />');
	?>
<?endif?>

<?if($arParams["USE_SEARCH"]=="Y"):?>
<?=GetMessage("SEARCH_LABEL")?><?$APPLICATION->IncludeComponent(
	"bitrix:search.form",
	"flat",
	Array(
		"PAGE" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["search"]
	),
	$component
);?>
<?endif?>

<?if($arParams["USE_FILTER"]=="Y"):?>
<?$APPLICATION->IncludeComponent(
	"bitrix:catalog.filter",
	"",
	Array(
		"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
		"IBLOCK_ID" => $arParams["IBLOCK_ID"],
		"FILTER_NAME" => $arParams["FILTER_NAME"],
		"FIELD_CODE" => $arParams["FILTER_FIELD_CODE"],
		"PROPERTY_CODE" => $arParams["FILTER_PROPERTY_CODE"],
		"CACHE_TYPE" => $arParams["CACHE_TYPE"],
		"CACHE_TIME" => $arParams["CACHE_TIME"],
		"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
		"PAGER_PARAMS_NAME" => $arParams["PAGER_PARAMS_NAME"],
	),
	$component
);
?>
<?endif?>

<section id="hero-search" class="section pt-0">
    <div class="section-body">
        <div class="container-fluid">

            <div class="hero-search-block mb-5">
                <div class="hero-search-group">
                    <div class="hero-search-input">
                        <input type="text" class="search-input" placeholder="Начните поиск здесь...">
                        <div class="search-result-block">
                            <div class="search-result-block-wrap p-3">
                                <div class="search-result-block-wrap-ajax d-none">
                                    <ul class="mb-0">
                                        <li>
                                            <svg width="16" height="16">
                                                <use xlink:href="#icon-search"></use>
                                            </svg>
                                            <a href="#"><span></span> красный</a>
                                        </li>
                                        <li>
                                            <svg width="16" height="16">
                                                <use xlink:href="#icon-search"></use>
                                            </svg>
                                            <a href="#"><span></span> большой</a>
                                        </li>
                                        <li>
                                            <svg width="16" height="16">
                                                <use xlink:href="#icon-search"></use>
                                            </svg>
                                            <a href="#"><span></span> с подкладом</a>
                                        </li>
                                        <li>
                                            <svg width="16" height="16">
                                                <use xlink:href="#icon-search"></use>
                                            </svg>
                                            <a href="#"><span></span> и много с чем еще</a>
                                        </li>
                                    </ul>
                                </div>

                                <div class="search-result-block-wrap-offer">
                                    <h6 class="h6 text-secondary">История поиска</h6>
                                    <ul>
                                        <li>
                                            <svg width="16" height="16">
                                                <use xlink:href="#icon-search"></use>
                                            </svg>
                                            <a href="#">Жилет утепленный Фаворит</a>
                                        </li>
                                        <li>
                                            <svg width="16" height="16">
                                                <use xlink:href="#icon-search"></use>
                                            </svg>
                                            <a href="#">Панорамная маска</a>
                                        </li>
                                        <li>
                                            <svg width="16" height="16">
                                                <use xlink:href="#icon-search"></use>
                                            </svg>
                                            <a href="#">Каскетка-бейсболка</a>
                                        </li>
                                        <li>
                                            <svg width="16" height="16">
                                                <use xlink:href="#icon-search"></use>
                                            </svg>
                                            <a href="#">Сапоги войлочные</a>
                                        </li>
                                    </ul>

                                    <h6 class="h6 text-secondary">Часто ищут</h6>
                                    <ul class="mb-0">
                                        <li>
                                            <svg width="16" height="16">
                                                <use xlink:href="#icon-search"></use>
                                            </svg>
                                            <a href="#">Жилет утепленный Фаворит</a>
                                        </li>
                                        <li>
                                            <svg width="16" height="16">
                                                <use xlink:href="#icon-search"></use>
                                            </svg>
                                            <a href="#">Панорамная маска</a>
                                        </li>
                                        <li>
                                            <svg width="16" height="16">
                                                <use xlink:href="#icon-search"></use>
                                            </svg>
                                            <a href="#">Каскетка-бейсболка</a>
                                        </li>
                                        <li>
                                            <svg width="16" height="16">
                                                <use xlink:href="#icon-search"></use>
                                            </svg>
                                            <a href="#">Сапоги войлочные</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="search-result-bg"></div>
                        <svg class="hero-search-input-icon" width="24" height="24">
                            <use xlink:href="#icon-search"></use>
                        </svg>
                    </div>
                    <div class="hero-search-buttons">
                        <button class="f-button c-success f-clear mr-0 px-4" data-fancybox data-src="#filter-popup">
                            <svg width="16" height="16">
                                <use xlink:href="#icon-filter-white"></use>
                            </svg>
                            <span class="pl-2 text-uppercase d-sm-none d-md-inline">Фильтры</span>
                        </button>
                        <button class="f-button c-primary">
                            <svg width="16" height="16" class="d-none d-sm-inline d-md-none">
                                <use xlink:href="#icon-arrow-control"></use>
                            </svg>
                            <span class="pl-2 text-uppercase d-sm-none d-md-inline">Искать</span>
                        </button>
                    </div>
                </div>
            </div>

            <div id="filter-popup" style="display: none">
                <form id="filter">
                    <div class="filter bg-white">
                        <div class="catalog-options mb-1 d-flex align-items-center">
                            <button class="f-button f-dropdown bg-white text-decoration-none">
                                <svg width="16" height="16">
                                    <use xlink:href="#icon-filter-black"></use>
                                </svg>
                                <span class="pl-2 d-none d-md-inline-block">Фильтры</span>
                            </button>
                        </div>

                        <div class="filter-row">
                            <div class="filter-row-title d-flex align-items-center py-2 py-lg-3 px-3 px-lg-4">
                                <h6 class="mr-auto mb-0">Категория</h6>
                                <svg width="20" height="20">
                                    <use xlink:href="#icon-arrow-up"></use>
                                </svg>
                            </div>
                            <div class="filter-row-list py-2">
                                <label class="px-3 px-lg-4 py-2 mb-0">
                                    <input type="checkbox" checked>
                                    <svg width="20" height="20">
                                        <use xlink:href="#icon-checkbox-tick"></use>
                                    </svg>
                                    <span>Партнеры</span>
                                </label>
                                <label class="px-3 px-lg-4 py-2 mb-0">
                                    <input type="checkbox">
                                    <svg width="20" height="20">
                                        <use xlink:href="#icon-checkbox-tick"></use>
                                    </svg>
                                    <span>Маркеплейс</span>
                                </label>
                                <label class="px-3 px-lg-4 py-2 mb-0">
                                    <input type="checkbox">
                                    <svg width="20" height="20">
                                        <use xlink:href="#icon-checkbox-tick"></use>
                                    </svg>
                                    <span>Каталог</span>
                                </label>
                            </div>
                        </div>

                        <div class="filter-row">
                            <div class="filter-row-title d-flex align-items-center py-2 py-lg-3 px-3 px-lg-4">
                                <h6 class="mr-auto mb-0">Диапазон дат</h6>
                                <svg width="20" height="20">
                                    <use xlink:href="#icon-arrow-up"></use>
                                </svg>
                            </div>
                            <div class="filter-row-list filter-row-price">
                                <div class="filter-row-price-inputs d-flex align-items-center justify-content-between py-2 py-lg-3 px-3 px-lg-4">
                                    <input type="date" class="filterPriceMin" value="23.01.2018" placeholder="23.01.2018">
                                    <span class="text-secondary">&mdash;</span>
                                    <input type="date" class="filterPriceMax" value="10.03.2026" placeholder="10.03.2026">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex">
                        <button class="f-button c-white text-secondary f-clear mr-0 px-2 px-lg-4 w-50">
                            <svg width="16" height="16">
                                <use xlink:href="#icon-close"></use>
                            </svg>
                            <span class="pl-2">Сбросить</span>
                        </button>
                        <button class="f-button c-success px-2 px-lg-4 w-50" style="margin-right: 15px">
                            <svg width="16" height="16" class="d-none d-sm-inline d-md-none">
                                <use xlink:href="#icon-arrow-control"></use>
                            </svg>
                            <span class="pl-2">Применить</span>
                        </button>
                    </div>
                </form>
            </div>

<?$APPLICATION->IncludeComponent(
	"bitrix:news.list",
	"",
	Array(
		"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
		"IBLOCK_ID" => $arParams["IBLOCK_ID"],
		"NEWS_COUNT" => $arParams["NEWS_COUNT"],
		"SORT_BY1" => $arParams["SORT_BY1"],
		"SORT_ORDER1" => $arParams["SORT_ORDER1"],
		"SORT_BY2" => $arParams["SORT_BY2"],
		"SORT_ORDER2" => $arParams["SORT_ORDER2"],
		"FIELD_CODE" => $arParams["LIST_FIELD_CODE"],
		"PROPERTY_CODE" => $arParams["LIST_PROPERTY_CODE"],
		"SET_TITLE" => $arParams["SET_TITLE"],
		"SET_LAST_MODIFIED" => $arParams["SET_LAST_MODIFIED"],
		"MESSAGE_404" => $arParams["MESSAGE_404"],
		"SET_STATUS_404" => $arParams["SET_STATUS_404"],
		"SHOW_404" => $arParams["SHOW_404"],
		"FILE_404" => $arParams["FILE_404"],
		"INCLUDE_IBLOCK_INTO_CHAIN" => $arParams["INCLUDE_IBLOCK_INTO_CHAIN"],
		"ADD_SECTIONS_CHAIN" => $arParams["ADD_SECTIONS_CHAIN"],
		"CACHE_TYPE" => $arParams["CACHE_TYPE"],
		"CACHE_TIME" => $arParams["CACHE_TIME"],
		"CACHE_FILTER" => $arParams["CACHE_FILTER"],
		"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
		"DISPLAY_TOP_PAGER" => $arParams["DISPLAY_TOP_PAGER"],
		"DISPLAY_BOTTOM_PAGER" => $arParams["DISPLAY_BOTTOM_PAGER"],
		"PAGER_TITLE" => $arParams["PAGER_TITLE"],
		"PAGER_TEMPLATE" => $arParams["PAGER_TEMPLATE"],
		"PAGER_SHOW_ALWAYS" => $arParams["PAGER_SHOW_ALWAYS"],
		"PAGER_DESC_NUMBERING" => $arParams["PAGER_DESC_NUMBERING"],
		"PAGER_DESC_NUMBERING_CACHE_TIME" => $arParams["PAGER_DESC_NUMBERING_CACHE_TIME"],
		"PAGER_SHOW_ALL" => $arParams["PAGER_SHOW_ALL"],
		"PAGER_BASE_LINK_ENABLE" => $arParams["PAGER_BASE_LINK_ENABLE"],
		"PAGER_BASE_LINK" => $arParams["PAGER_BASE_LINK"],
		"PAGER_PARAMS_NAME" => $arParams["PAGER_PARAMS_NAME"],
		"DISPLAY_DATE" => $arParams["DISPLAY_DATE"],
		"DISPLAY_NAME" => "Y",
		"DISPLAY_PICTURE" => $arParams["DISPLAY_PICTURE"],
		"DISPLAY_PREVIEW_TEXT" => $arParams["DISPLAY_PREVIEW_TEXT"],
		"PREVIEW_TRUNCATE_LEN" => $arParams["PREVIEW_TRUNCATE_LEN"],
		"ACTIVE_DATE_FORMAT" => $arParams["LIST_ACTIVE_DATE_FORMAT"],
		"USE_PERMISSIONS" => $arParams["USE_PERMISSIONS"],
		"GROUP_PERMISSIONS" => $arParams["GROUP_PERMISSIONS"],
		"FILTER_NAME" => $arParams["FILTER_NAME"],
		"HIDE_LINK_WHEN_NO_DETAIL" => $arParams["HIDE_LINK_WHEN_NO_DETAIL"],
		"CHECK_DATES" => $arParams["CHECK_DATES"],
		"STRICT_SECTION_CHECK" => $arParams["STRICT_SECTION_CHECK"],

		"PARENT_SECTION" => $arResult["VARIABLES"]["SECTION_ID"],
		"PARENT_SECTION_CODE" => $arResult["VARIABLES"]["SECTION_CODE"],
		"DETAIL_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["detail"],
		"SECTION_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["section"],
		"IBLOCK_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["news"],
	),
	$component
);?>

        </div>
    </div>
</section>
