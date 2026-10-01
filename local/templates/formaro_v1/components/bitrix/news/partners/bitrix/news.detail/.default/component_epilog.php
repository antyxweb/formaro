<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arResult */
/** @global CMain $APPLICATION */

// Превью ссылки на страницу партнёра (Open Graph) — см. result_modifier.php.
if (!empty($arResult['OG']) && \Bitrix\Main\Loader::includeModule('formaro.cabinet')) {
    $APPLICATION->SetPageProperty('og:title', $arResult['OG']['TITLE']);
    \Formaro\Cabinet\Seo\OpenGraph::set($arResult['OG']['IMAGE'], 'website', $arResult['OG']['TEXT']);
}
