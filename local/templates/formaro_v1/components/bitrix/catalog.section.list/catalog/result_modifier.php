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

$arResult['SECTIONS_LIST'] = [];

foreach ($arResult['SECTIONS'] as $arSection) {
    if($arSection['DEPTH_LEVEL'] == 1) {
        $arResult['SECTIONS_LIST'][$arSection['ID']] = $arSection;
    } else if($arSection['DEPTH_LEVEL'] == 2){
        $arResult['SECTIONS_LIST'][$arSection['IBLOCK_SECTION_ID']]['SUB'][$arSection['ID']] = $arSection;
    }
}

$res = CIBlock::GetByID($arParams['IBLOCK_ID']);
if($ar_res = $res->GetNext()) {
    $arResult['IBLOCK'] = $ar_res;
}