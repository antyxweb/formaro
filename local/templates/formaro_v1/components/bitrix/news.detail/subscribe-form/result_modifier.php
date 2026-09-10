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

$arFilter = array('IBLOCK_ID'=>3, 'DEPTH_LEVEL'=>1);
$rsSections = CIBlockSection::GetList(array('LEFT_MARGIN' => 'ASC'), $arFilter, true, array(), false);
while ($arSection = $rsSections->GetNext())
{
    $arResult['SECTIONS'][] = $arSection;
}