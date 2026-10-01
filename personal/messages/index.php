<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Чаты и сообщения");
$APPLICATION->SetTitle("Чаты и сообщения");
?><?$APPLICATION->IncludeComponent(
	"formaro:personal.chat",
	"",
	[],
	false
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
