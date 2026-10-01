<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
// Раздел из инфоблока «Контентные страницы» (раздел «about»): главная — плитки
// (или страница с кодом раздела), страницы — /about/<код>/ (urlrewrite.php).
?><?$APPLICATION->IncludeComponent(
	"formaro:content.section",
	"",
	[
		"SECTION_CODE" => "about",
		"SEF_MODE" => "Y",
		"SEF_FOLDER" => "/about/",
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
