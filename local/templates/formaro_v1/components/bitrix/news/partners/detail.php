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

<?php $ElementID = $APPLICATION->IncludeComponent(
    "bitrix:news.detail",
    "",
    [
        "DISPLAY_DATE" => $arParams["DISPLAY_DATE"],
        "DISPLAY_NAME" => $arParams["DISPLAY_NAME"],
        "DISPLAY_PICTURE" => $arParams["DISPLAY_PICTURE"],
        "DISPLAY_PREVIEW_TEXT" => $arParams["DISPLAY_PREVIEW_TEXT"],
        "IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
        "IBLOCK_ID" => $arParams["IBLOCK_ID"],
        "FIELD_CODE" => $arParams["DETAIL_FIELD_CODE"],
        "PROPERTY_CODE" => $arParams["DETAIL_PROPERTY_CODE"],
        "DETAIL_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["detail"],
        "SECTION_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["section"],
        "META_KEYWORDS" => $arParams["META_KEYWORDS"],
        "META_DESCRIPTION" => $arParams["META_DESCRIPTION"],
        "BROWSER_TITLE" => $arParams["BROWSER_TITLE"],
        "SET_CANONICAL_URL" => $arParams["DETAIL_SET_CANONICAL_URL"],
        "SET_LAST_MODIFIED" => $arParams["SET_LAST_MODIFIED"],
        "SET_TITLE" => "Y",
        "MESSAGE_404" => $arParams["MESSAGE_404"],
        "SET_STATUS_404" => $arParams["SET_STATUS_404"],
        "SHOW_404" => $arParams["SHOW_404"],
        "FILE_404" => $arParams["FILE_404"],
        "INCLUDE_IBLOCK_INTO_CHAIN" => $arParams["INCLUDE_IBLOCK_INTO_CHAIN"],
        "ADD_SECTIONS_CHAIN" => $arParams["ADD_SECTIONS_CHAIN"],
        "ACTIVE_DATE_FORMAT" => $arParams["DETAIL_ACTIVE_DATE_FORMAT"],
        "CACHE_TYPE" => $arParams["CACHE_TYPE"],
        "CACHE_TIME" => $arParams["CACHE_TIME"],
        "CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
        "USE_PERMISSIONS" => $arParams["USE_PERMISSIONS"],
        "GROUP_PERMISSIONS" => $arParams["GROUP_PERMISSIONS"],
        "DISPLAY_TOP_PAGER" => $arParams["DETAIL_DISPLAY_TOP_PAGER"],
        "DISPLAY_BOTTOM_PAGER" => $arParams["DETAIL_DISPLAY_BOTTOM_PAGER"],
        "PAGER_TITLE" => $arParams["DETAIL_PAGER_TITLE"],
        "PAGER_SHOW_ALWAYS" => "N",
        "PAGER_TEMPLATE" => $arParams["DETAIL_PAGER_TEMPLATE"],
        "PAGER_SHOW_ALL" => $arParams["DETAIL_PAGER_SHOW_ALL"],
        "CHECK_DATES" => $arParams["CHECK_DATES"],
        "ELEMENT_ID" => $arResult["VARIABLES"]["ELEMENT_ID"],
        "ELEMENT_CODE" => $arResult["VARIABLES"]["ELEMENT_CODE"],
        "SECTION_ID" => $arResult["VARIABLES"]["SECTION_ID"],
        "SECTION_CODE" => $arResult["VARIABLES"]["SECTION_CODE"],
        "IBLOCK_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["news"],
        "USE_SHARE" => $arParams["USE_SHARE"],
        "SHARE_HIDE" => $arParams["SHARE_HIDE"],
        "SHARE_TEMPLATE" => $arParams["SHARE_TEMPLATE"],
        "SHARE_HANDLERS" => $arParams["SHARE_HANDLERS"],
        "SHARE_SHORTEN_URL_LOGIN" => $arParams["SHARE_SHORTEN_URL_LOGIN"],
        "SHARE_SHORTEN_URL_KEY" => $arParams["SHARE_SHORTEN_URL_KEY"],
        "ADD_ELEMENT_CHAIN" => $arParams["ADD_ELEMENT_CHAIN"],
        'STRICT_SECTION_CHECK' => $arParams['STRICT_SECTION_CHECK'],
    ],
    $component
);?>

<?php
// Блоки как на главной, но только с товарами/новостями этого партнёра
// (вёрстка — /html/partner.html). $ElementID — элемент инфоблока
// cabinet_partners, по нему привязаны товары (PROPERTY_PARTNER_ID) и
// новости партнёра.
$partnerId = (int)$ElementID;
// Блоки с товарами — только если у партнёра есть активные товары
// (иначе пустая строка поиска с «ничего не найдено»). Новости — всегда.
$partnerHasProducts = $partnerId > 0
    && \Bitrix\Main\Loader::includeModule('formaro.cabinet')
    && (new \Formaro\Cabinet\Repository\ProductRepository())->countPublic(['PROPERTY_PARTNER_ID' => $partnerId]) > 0;
if ($partnerHasProducts):
?>
    <?$APPLICATION->IncludeComponent(
        "formaro:catalog.search",
        "",
        [
            "PARTNER_ID" => $partnerId,
            "SECTION_CLASS" => "pt-0",
            "PAGE_SIZE" => "24",
            "CACHE_TYPE" => "A",
            "CACHE_TIME" => "3600",
        ],
        false,
        ["HIDE_ICONS" => "Y"]
    );?>
    <style>
        .hide-on-main {
            display: none !important;
        }
    </style>

    <?$APPLICATION->IncludeComponent(
        "formaro:product.carousel",
        "",
        [
            "MODE" => "DISCOUNT",
            "PARTNER_ID" => $partnerId,
            "COUNT" => "12",
            "TITLE_ACCENT" => "Выгодные",
            "TITLE" => "предложения",
            "CATALOG_URL" => "#hero-search",
            "SECTION_CLASS" => "light-gray-stripe",
            "CACHE_TYPE" => "A",
            "CACHE_TIME" => "600",
        ],
        false,
        ["HIDE_ICONS" => "Y"]
    );?>

    <?$APPLICATION->IncludeComponent(
        "formaro:product.carousel",
        "",
        [
            "MODE" => "NEW",
            "PARTNER_ID" => $partnerId,
            "COUNT" => "12",
            "TITLE_ACCENT" => "Новые",
            "TITLE" => "поступления",
            "CATALOG_URL" => "#hero-search",
            "SECTION_CLASS" => "",
            "CACHE_TYPE" => "A",
            "CACHE_TIME" => "600",
        ],
        false,
        ["HIDE_ICONS" => "Y"]
    );?>

    <?php
    // Категории, где есть товары партнёра; число товаров — только его.
    $GLOBALS['partnerSectionsFilter'] = ['UF_APPROVED' => 1];
    $GLOBALS['partnerCountFilter'] = ['PROPERTY_PARTNER_ID' => $partnerId];
    ?>
    <?$APPLICATION->IncludeComponent(
        "bitrix:catalog.section.list",
        "catalog",
        [
            "IBLOCK_TYPE" => "catalog",
            "IBLOCK_ID" => "9",
            "SECTION_ID" => "",
            "SECTION_CODE" => "",
            "COUNT_ELEMENTS" => "Y",
            "COUNT_ELEMENTS_FILTER" => "CNT_ACTIVE",
            "ADDITIONAL_COUNT_ELEMENTS_FILTER" => "partnerCountFilter",
            "HIDE_SECTIONS_WITH_ZERO_COUNT_ELEMENTS" => "Y",
            "TOP_DEPTH" => "2",
            "SECTION_FIELDS" => ["NAME", "PICTURE"],
            "SECTION_USER_FIELDS" => [],
            "FILTER_NAME" => "partnerSectionsFilter",
            "VIEW_MODE" => "LIST",
            "SHOW_PARENT_NAME" => "Y",
            "SECTION_URL" => "",
            "CACHE_TYPE" => "A",
            "CACHE_TIME" => "36000000",
            "CACHE_GROUPS" => "Y",
            "CACHE_FILTER" => "N",
            "ADD_SECTIONS_CHAIN" => "N",
            "BLOCK_TITLE" => "Каталог товаров партнера",
            "SHOW_PARTNER_BUTTON" => "N",
            "SHOW_DESCRIPTION" => "N",
            "PARTNER_ID" => $partnerId,
        ],
        false,
        ["HIDE_ICONS" => "Y"]
    );?>
<?php endif; ?>

<?php if ($partnerId > 0): ?>
    <?php $GLOBALS['partnerNewsFilter'] = ['PROPERTY_PARTNER_ID' => $partnerId]; ?>
    <?$APPLICATION->IncludeComponent(
        "bitrix:news.list",
        "news-slider",
        [
            "ACTIVE_DATE_FORMAT" => "d F Y",
            "ADD_SECTIONS_CHAIN" => "N",
            "AJAX_MODE" => "N",
            "CACHE_FILTER" => "Y",
            "CACHE_GROUPS" => "Y",
            "CACHE_TIME" => "36000000",
            "CACHE_TYPE" => "A",
            "CHECK_DATES" => "Y",
            "DETAIL_URL" => "",
            "DISPLAY_BOTTOM_PAGER" => "N",
            "DISPLAY_DATE" => "Y",
            "DISPLAY_NAME" => "Y",
            "DISPLAY_PICTURE" => "Y",
            "DISPLAY_PREVIEW_TEXT" => "Y",
            "DISPLAY_TOP_PAGER" => "N",
            "FIELD_CODE" => [],
            "FILTER_NAME" => "partnerNewsFilter",
            "HIDE_LINK_WHEN_NO_DETAIL" => "N",
            "IBLOCK_ID" => "10",
            "IBLOCK_TYPE" => "content",
            "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
            "INCLUDE_SUBSECTIONS" => "Y",
            "NEWS_COUNT" => "20",
            "PAGER_TITLE" => "Новости партнера",
            "PARENT_SECTION" => "",
            "PARENT_SECTION_CODE" => "",
            "PREVIEW_TRUNCATE_LEN" => "",
            "PROPERTY_CODE" => [],
            "SET_BROWSER_TITLE" => "N",
            "SET_LAST_MODIFIED" => "N",
            "SET_META_DESCRIPTION" => "N",
            "SET_META_KEYWORDS" => "N",
            "SET_STATUS_404" => "N",
            "SET_TITLE" => "N",
            "SHOW_404" => "N",
            "SORT_BY1" => "ACTIVE_FROM",
            "SORT_BY2" => "SORT",
            "SORT_ORDER1" => "DESC",
            "SORT_ORDER2" => "ASC",
            "SECTION_CLASS" => "",
        ],
        false,
        ["HIDE_ICONS" => "Y"]
    );?>
<?php endif; ?>
