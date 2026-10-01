<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Кабинет покупателя");
$APPLICATION->SetTitle("Кабинет покупателя");
?><?$APPLICATION->IncludeComponent(
	"bitrix:menu",
	"personal-tiles",
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
