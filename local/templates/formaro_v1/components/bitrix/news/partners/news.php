<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}
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
                        <button class="f-button c-primary">
                            <svg width="16" height="16" class="d-none d-sm-inline d-md-none">
                                <use xlink:href="#icon-arrow-control"></use>
                            </svg>
                            <span class="pl-2 text-uppercase d-sm-none d-md-inline">Искать</span>
                        </button>
                    </div>
                </div>
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
