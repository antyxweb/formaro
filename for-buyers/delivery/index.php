<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Доставка и стоимость");
$APPLICATION->SetTitle("Доставка и стоимость");
include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/text_page_top.php';
?>
<h2>Доставка транспортными компаниями</h2>
<p>Доставка возможна в любой регион России и страны СНГ транспортной компанией на ваш выбор. При оформлении заказа выберите:</p>
<div class="text-page__cards">
    <div class="text-page__card text-page__card--logo"><img src="/local/templates/formaro_v1/images/delivery/cdek.svg" alt="СДЭК" height="32" loading="lazy"></div>
    <div class="text-page__card text-page__card--logo"><img src="/local/templates/formaro_v1/images/delivery/dellin.svg" alt="Деловые линии" height="32" loading="lazy"></div>
    <div class="text-page__card text-page__card--logo"><img src="/local/templates/formaro_v1/images/delivery/pek.svg" alt="ПЭК" height="32" loading="lazy"></div>
    <div class="text-page__card"><h3>Другая транспортная компания</h3><p>Укажите её в комментарии к заказу.</p></div>
</div>
<p>Стоимость и сроки доставки обсуждаются с продавцом отдельно — после оформления заказа он свяжется с вами. Задать вопрос о доставке можно и самостоятельно: «Чат с продавцом» в разделе «<a href="/personal/orders/">Ваши заказы</a>».</p>
<p>Стоимость доставки транспортной компанией не включается в стоимость заказа и оплачивается клиентом при получении груза, согласно тарифам выбранной ТК.</p>

<h3>Для получения товара при себе необходимо иметь</h3>
<ul>
    <li>документ, удостоверяющий личность;</li>
    <li>печать или доверенность (для юридических лиц), в случае получения товара доверенным лицом.</li>
</ul>
<?include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/text_page_bottom.php';?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
