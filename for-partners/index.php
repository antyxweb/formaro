<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
// Раздел из инфоблока «Контентные страницы» (раздел «for-partners»): главная — плитки
// (или страница с кодом раздела), страницы — /for-partners/<код>/ (urlrewrite.php).
?><?$APPLICATION->IncludeComponent(
	"formaro:content.section",
	"",
	[
		"SECTION_CODE" => "for-partners",
		"SEF_MODE" => "Y",
		"SEF_FOLDER" => "/for-partners/",
		"SEF_URL_TEMPLATES" => [
			"index" => "",
			"page" => "#ELEMENT_CODE#/",
		],
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "36000000",
	],
	false
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
