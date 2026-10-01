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

// Темы подписки — разделы каталога верхнего уровня (SubscriptionService::topics()).
$arResult['SECTIONS'] = [];
if (\Bitrix\Main\Loader::includeModule('formaro.cabinet')) {
    foreach (\Formaro\Cabinet\Service\SubscriptionService::topics() as $id => $name) {
        $arResult['SECTIONS'][] = ['ID' => $id, 'NAME' => htmlspecialcharsbx($name)];
    }
}
