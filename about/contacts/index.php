<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Контакты");
$APPLICATION->SetTitle("Контакты");
include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/text_page_top.php';
?>
<table class="text-page__props"><tbody>
    <tr><th scope="row">Адрес</th><td>Московская область, г. Химки, ул. Молодёжная 15А<br><a href="https://yandex.ru/maps/?text=%D0%A5%D0%B8%D0%BC%D0%BA%D0%B8%2C%20%D1%83%D0%BB.%20%D0%9C%D0%BE%D0%BB%D0%BE%D0%B4%D1%91%D0%B6%D0%BD%D0%B0%D1%8F%2015%D0%90" target="_blank" rel="noopener">Открыть на карте</a></td></tr>
    <tr><th scope="row">Телефон</th><td><a href="tel:+74957927080">+7 495 792 70 80</a></td></tr>
    <tr><th scope="row">WhatsApp / Telegram</th><td><a href="https://wa.me/79651282114">+7 965 128 21 14</a></td></tr>
    <tr><th scope="row">E-mail</th><td><a href="mailto:info@formaro.ru">info@formaro.ru</a></td></tr>
    <tr><th scope="row">Режим работы</th><td>Офис Пн. – Пт.: с 09:00 - 18:00. Сб, Вс - выходные дни<br>Магазин Пн. – Сб.: 10.00-17.00. Вс- выходной</td></tr>
</tbody></table>
<?include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/text_page_bottom.php';?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
