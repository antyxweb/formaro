<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Ваш профиль");
$APPLICATION->SetTitle("Ваш профиль");
?><?$APPLICATION->IncludeComponent(
	"formaro:personal.profile",
	"",
	[],
	false
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
