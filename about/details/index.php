<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Реквизиты");
$APPLICATION->SetTitle("Реквизиты");
include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/text_page_top.php';
?>
<table class="text-page__props"><tbody>
    <tr><th scope="row">Индивидуальный предприниматель</th><td>Дасни Арина Юрьевна</td></tr>
    <tr><th scope="row">Сокращенное наименование организации</th><td>ИП Дасни А.Ю.</td></tr>
    <tr><th scope="row">ОГРНИП</th><td>321774600374611</td></tr>
    <tr><th scope="row">ИНН</th><td>773320714684</td></tr>
    <tr><th scope="row">Банк</th><td>ПАО СБЕРБАНК</td></tr>
    <tr><th scope="row">Расчетный счет</th><td>40802810540000184312</td></tr>
    <tr><th scope="row">Корреспондентский счет</th><td>30101810400000000225</td></tr>
    <tr><th scope="row">БИК</th><td>044525225</td></tr>
</tbody></table>
<?include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/text_page_bottom.php';?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
