<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Вопросы и ответы");
$APPLICATION->SetTitle("Вопросы и ответы");
include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/text_page_top.php';
?>
<div class="faq">
    <details>
        <summary>Как оформить заказ?</summary>
        <div><p>Добавьте выбранные товары в корзину, перейдите на страницу <a href="/personal/cart/">Корзина</a>, проверьте позиции и нажмите «Оформить заказ». Подробнее — в разделе «<a href="/for-buyers/make-order/">Как сделать заказ</a>».</p></div>
    </details>
    <details>
        <summary>Нужно ли регистрироваться, чтобы сделать заказ?</summary>
        <div><p>Да. Для того, чтобы оформить заказ, вам необходимо зарегистрироваться или авторизоваться.</p></div>
    </details>
    <details>
        <summary>Как оплатить заказ?</summary>
        <div><p>Способы оплаты для физических и юридических лиц описаны в разделе «<a href="/for-buyers/payment/">Способ оплаты</a>».</p></div>
    </details>
    <details>
        <summary>Как доставляется заказ и сколько стоит доставка?</summary>
        <div><p>Курьером, самовывозом, Почтой России или транспортной компанией. Условия и стоимость — в разделе «<a href="/for-buyers/delivery/">Доставка и стоимость</a>».</p></div>
    </details>
    <details>
        <summary>Можно ли вернуть или обменять товар?</summary>
        <div><p>Да, согласно действующему законодательству РФ вы можете вернуть товар в течение 14 дней с даты получения. Условия — в разделе «<a href="/for-buyers/returns/">Возврат и обмен</a>».</p></div>
    </details>
    <details>
        <summary>Есть ли специальные условия для оптовых покупателей и юридических лиц?</summary>
        <div><p>Для оптовых покупателей и юридических лиц у нас имеются специальные условия и гибкая система скидок, которые обсуждаются индивидуально.</p></div>
    </details>
    <details>
        <summary>Товара нет в наличии нужного размера или цвета — что делать?</summary>
        <div><p>Остатки товаров на складе постоянно меняются. Если требуемого вам товара на момент заказа не окажется в наличии, при вашем согласии он будет поставлен «под заказ».</p></div>
    </details>
    <details>
        <summary>Как связаться с продавцом?</summary>
        <div><p>Нажмите «Чат с продавцом» в карточке товара, в заказе или на странице продавца — переписка появится в разделе «<a href="/personal/messages/">Чаты и сообщения</a>» кабинета покупателя.</p></div>
    </details>
    <details>
        <summary>Как стать партнером и продавать товары на Formaro.ru?</summary>
        <div><p>Подробнее — в разделе «<a href="/for-partners/become-a-partner/">Стать партнером</a>».</p></div>
    </details>
</div>
<p class="mt-4">Не нашли ответ? Напишите нам на <a href="mailto:info@formaro.ru">info@formaro.ru</a> или позвоните: <a href="tel:+74957927080">+7 495 792 70 80</a>.</p>
<?include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/text_page_bottom.php';?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
