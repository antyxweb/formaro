<?
// «Управление рассылками» — сюда ведёт ссылка «Отписаться или настроить
// рассылки» из писем (с e-mail и подписью, вход не нужен); без ссылки —
// настройки вошедшего пользователя. См. Formaro\Cabinet\Service\MailingService.
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Управление рассылками");
$APPLICATION->SetPageProperty("robots", "noindex, nofollow");
$APPLICATION->SetTitle("Управление рассылками");
?><?$APPLICATION->IncludeComponent(
	"formaro:mailing.settings",
	"",
	[],
	false
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
