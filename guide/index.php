<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
// Раздел из инфоблока «Контентные страницы» (раздел «guide»): главная — вступление и плитки глав
// (доступ — .access.php, только администраторы), главы — /guide/<код>/ (urlrewrite.php).
?><?$APPLICATION->IncludeComponent(
	"formaro:content.section",
	"",
	[
		"SECTION_CODE" => "guide",
		"SEF_MODE" => "Y",
		"SEF_FOLDER" => "/guide/",
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
