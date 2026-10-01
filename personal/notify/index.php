<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Уведомления");
$APPLICATION->SetTitle("Уведомления");
?><?$APPLICATION->IncludeComponent(
	"formaro:personal.notifications",
	"",
	[],
	false
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
