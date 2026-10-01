<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
// Текст, заголовок и SEO — элемент «payment» инфоблока «Контентные страницы».
?><?$APPLICATION->IncludeComponent(
	"formaro:content.page",
	"",
	[
		"CODE" => "payment",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "36000000",
	],
	false
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
