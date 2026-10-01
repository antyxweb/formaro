<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arResult */
/** @var CBitrixComponentTemplate $this */

// Превью ссылки на страницу партнёра (Open Graph, component_epilog.php):
// краткое название, обложка (иначе логотип), анонс.
$ogShortName = trim((string)($arResult['PROPERTIES']['NAME_SHORT']['VALUE'] ?? ''));
$arResult['OG'] = [
    'TITLE' => $ogShortName !== '' ? $ogShortName : (string)$arResult['~NAME'],
    'IMAGE' => (string)($arResult['DETAIL_PICTURE']['SRC'] ?? '') ?: (string)($arResult['PREVIEW_PICTURE']['SRC'] ?? ''),
    'TEXT' => (string)($arResult['~PREVIEW_TEXT'] ?? '') ?: (string)($arResult['~DETAIL_TEXT'] ?? ''),
];

$this->__component->SetResultCacheKeys(['OG']);
