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

if($arParams['PARENT_SECTION_CODE']) {
    $arFilter = array('IBLOCK_ID'=>$arParams['IBLOCK_ID'], 'CODE'=>$arParams['PARENT_SECTION_CODE']);
    $rsSections = CIBlockSection::GetList(array('LEFT_MARGIN' => 'ASC'), $arFilter, true, array(), Array("nPageSize"=>1));
    while ($arSection = $rsSections->GetNext())
    {
        $arResult['SECTION_NAME'] = $arSection['NAME'];
        $arResult['SECTION_DESCRIPTION'] = $arSection['DESCRIPTION'];
    }
}