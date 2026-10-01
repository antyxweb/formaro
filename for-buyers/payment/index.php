<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Способ оплаты");
$APPLICATION->SetTitle("Способ оплаты");
include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/text_page_top.php';
?>
<div class="text-page__note">
    <p><strong>Оплата по счёту (безналичный расчёт)</strong> — для физических лиц, юридических лиц и ИП. Счёт выставляет маркетплейс Formaro, оплата поступает на его расчётный счёт.</p>
</div>

<h2>Как оплатить заказ</h2>
<ol>
    <li>При оформлении заказа в разделе «Способ оплаты» выберите «По счёту (безнал)».</li>
    <li>После оформления откройте раздел «<a href="/personal/orders/">Ваши заказы</a>» и нажмите «Скачать счёт» в колонке «Оплата» — счёт на оплату в формате PDF.</li>
    <li>Оплатите счёт по указанным в нём реквизитам — через интернет-банк или в отделении банка. В назначении платежа укажите номер счёта.</li>
    <li>Нажмите «Сообщить об оплате» у заказа — так мы быстрее найдём ваш платёж.</li>
    <li>Маркетплейс проверит поступление и подтвердит оплату — в заказе появится статус «Оплачен», а в разделе «<a href="/personal/notify/">Уведомления</a>» — сообщение.</li>
</ol>
<p><strong>Юридическим лицам и ИП.</strong> Счёт-договор и УПД для бухгалтерии выдаются вместе с заказом.</p>

<h2>Банковские реквизиты</h2>
<table class="text-page__props"><tbody>
    <tr><th scope="row">Получатель</th><td>ИП Дасни А.Ю.</td></tr>
    <tr><th scope="row">ИНН</th><td>773320714684</td></tr>
    <tr><th scope="row">ОГРНИП</th><td>321774600374611</td></tr>
    <tr><th scope="row">Р/С</th><td>40802810540000184312 в ПАО Сбербанк г. Москва</td></tr>
    <tr><th scope="row">БИК</th><td>044525225</td></tr>
    <tr><th scope="row">К/С</th><td>30101810400000000225</td></tr>
    <tr><th scope="row">Генеральный директор</th><td>Дасни Арина Юрьевна</td></tr>
    <tr><th scope="row">Фактический адрес</th><td>125466, г. Москва, Новокуркинское шоссе, д. 1</td></tr>
</tbody></table>
<?include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/text_page_bottom.php';?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
