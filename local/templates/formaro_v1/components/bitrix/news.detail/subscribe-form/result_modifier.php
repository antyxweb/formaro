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
// В разделе партнёра (PARTNER_ID) тем нет — подписка на его обновления,
// в подзаголовке — его краткое название.
$arResult['SECTIONS'] = [];
$arResult['PARTNER_ID'] = 0;
$arResult['PARTNER_NAME'] = '';
$partnerId = (int)($arParams['PARTNER_ID'] ?? 0);
if ($partnerId && \Bitrix\Main\Loader::includeModule('formaro.cabinet')) {
    $arResult['PARTNER_NAME'] = \Formaro\Cabinet\Service\SubscriptionService::partnerName($partnerId);
    $arResult['PARTNER_ID'] = $arResult['PARTNER_NAME'] !== '' ? $partnerId : 0;
}
if (!$arResult['PARTNER_ID'] && \Bitrix\Main\Loader::includeModule('formaro.cabinet')) {
    foreach (\Formaro\Cabinet\Service\SubscriptionService::topics() as $id => $name) {
        $arResult['SECTIONS'][] = ['ID' => $id, 'NAME' => htmlspecialcharsbx($name)];
    }
}
