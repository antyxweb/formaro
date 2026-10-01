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

$arFilter = array('IBLOCK_ID'=>$arParams['IBLOCK_ID']);
$rsSections = CIBlockSection::GetList(array('LEFT_MARGIN' => 'ASC'), $arFilter, true, array(), false);
while ($arSection = $rsSections->GetNext())
{
    switch ($arSection['CODE']) {
        case 'marketplace':
            $arSection['CLASS'] = 'bg-primary';
            break;
        case 'catalog':
            $arSection['CLASS'] = 'bg-warning';
            break;
        case 'partners':
            $arSection['CLASS'] = 'bg-success';
            break;
    }
    $arResult['SECTIONS'][$arSection['ID']] = $arSection;
}

// Превью ссылки (Open Graph, component_epilog.php): картинка и анонс.
$arResult['OG'] = [
    'IMAGE' => (string)($arResult['DETAIL_PICTURE']['SRC'] ?? '') ?: (string)($arResult['PREVIEW_PICTURE']['SRC'] ?? ''),
    'TEXT' => (string)($arResult['~PREVIEW_TEXT'] ?? '') ?: (string)($arResult['~DETAIL_TEXT'] ?? ''),
];

$this->__component->SetResultCacheKeys(['PROPERTIES', 'OG']);