<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Партнерам");
$APPLICATION->SetTitle("Партнерам");
?><?$APPLICATION->IncludeComponent(
	"bitrix:menu",
	"section-tiles",
	[
		"ROOT_MENU_TYPE" => "left",
		"MAX_LEVEL" => "1",
		"USE_EXT" => "N",
		"ALLOW_MULTI_SELECT" => "N",
		"MENU_CACHE_TYPE" => "N",
	],
	false,
	["HIDE_ICONS" => "Y"]
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
