<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Ваши заказы");
$APPLICATION->SetTitle("Ваши заказы");
?><?$APPLICATION->IncludeComponent(
	"formaro:personal.orders",
	"",
	[
		"CATALOG_URL" => "/catalog/",
	],
	false
);?>

<?$APPLICATION->IncludeComponent(
	"formaro:product.carousel",
	"",
	[
		"MODE" => "VIEWED",
		"COUNT" => "20",
		"TITLE_ACCENT" => "Просмотренные",
		"TITLE" => "товары",
		"CATALOG_URL" => "",
		"SECTION_CLASS" => "light-gray-stripe",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "600",
	],
	false
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
