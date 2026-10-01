<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
// Свой каталог партнёра: /partners/<код>/catalog/… (правило в urlrewrite.php,
// PARTNER_CODE — код элемента cabinet_partners). Устроен как /catalog/,
// но только с его товарами и категориями; шапка сайта скрыта, как на
// странице партнёра; в хлебных крошках корень — «Главная» партнёра.
$catalogPartner = null;
$catalogPartnerCode = (string)($_GET['PARTNER_CODE'] ?? '');
if ($catalogPartnerCode !== '' && \Bitrix\Main\Loader::includeModule('iblock')) {
    $catalogPartner = CIBlockElement::GetList(
        [],
        ['IBLOCK_CODE' => 'cabinet_partners', '=CODE' => $catalogPartnerCode, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
        false,
        ['nTopCount' => 1],
        ['ID', 'CODE']
    )->Fetch() ?: null;
}

if (!$catalogPartner) {
    \Bitrix\Iblock\Component\Tools::process404('Страница не найдена', true, true, true);
} else {
    // Раздел партнёра: «Будьте в курсе» в подвале — подписка на этого партнёра.
    $APPLICATION->SetPageProperty('PARTNER_ID', (string)$catalogPartner['ID']);
?>
<script>
    document.getElementById('header').classList.add('partner-page');
</script>
<?$APPLICATION->IncludeComponent(
	"formaro:catalog",
	"",
	[
		"PARTNER_ID" => $catalogPartner["ID"],
		"SEF_MODE" => "Y",
		"SEF_FOLDER" => "/partners/" . $catalogPartner["CODE"] . "/catalog/",
		"SEF_URL_TEMPLATES" => [
			"sections" => "",
			"section" => "#SECTION_CODE_PATH#/",
			"element" => "#SECTION_CODE_PATH#/#ELEMENT_CODE#/",
		],
		"PAGE_SIZE" => "24",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "3600",
	],
	false
);?>
<?
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>
