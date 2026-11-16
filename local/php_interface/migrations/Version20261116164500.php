<?php

namespace Sprint\Migration;

use Bitrix\Main\Loader;

/**
 * Страница «Контакты» (/about/contacts/): вместо таблицы реквизитов —
 * список контактов в стиле подвала (иконка + подпись): Адрес, Телефон,
 * MAX, Почта, График работы. Строку «WhatsApp / Telegram» заменили на
 * «MAX» (ссылка на профиль max.ru из элемента icon-max инфоблока 6).
 * Иконки (#icon-map-pin, #icon-tel, #icon-max, #icon-mail, #icon-clock)
 * берутся из SVG-спрайта в footer.php и красятся в --primary через
 * currentColor; стили блока — в css/pages.css (.text-page .contacts-list).
 */
class Version20261116164500 extends Version
{
    protected $description = 'Контакты (/about/contacts/): вывод в стиле подвала (иконка + подпись): адрес, телефон, MAX, почта, график; WhatsApp/Telegram → MAX';

    /** Новый HTML страницы «Контакты». */
    private const NEW_TEXT = <<<'HTML'
<div class="contacts-list">
    <div class="contacts-list__item">
        <svg class="contacts-list__icon" width="26" height="26" aria-hidden="true"><use xlink:href="#icon-map-pin"></use></svg>
        <div class="contacts-list__body">
            <div class="contacts-list__title">Адрес</div>
            <div class="contacts-list__text">Московская область, г. Химки, ул. Молодёжная 15А
                <a href="https://yandex.ru/maps/?text=%D0%A5%D0%B8%D0%BC%D0%BA%D0%B8%2C%20%D1%83%D0%BB.%20%D0%9C%D0%BE%D0%BB%D0%BE%D0%B4%D1%91%D0%B6%D0%BD%D0%B0%D1%8F%2015%D0%90" target="_blank" rel="noopener">Открыть на карте</a>
            </div>
        </div>
    </div>
    <div class="contacts-list__item">
        <svg class="contacts-list__icon" width="26" height="26" aria-hidden="true"><use xlink:href="#icon-tel"></use></svg>
        <div class="contacts-list__body">
            <div class="contacts-list__title">Телефон</div>
            <div class="contacts-list__text"><a href="tel:+74957927080">+7 495 792 70 80</a></div>
        </div>
    </div>
    <div class="contacts-list__item">
        <svg class="contacts-list__icon" width="26" height="26" aria-hidden="true"><use xlink:href="#icon-max"></use></svg>
        <div class="contacts-list__body">
            <div class="contacts-list__title">MAX</div>
            <div class="contacts-list__text"><a href="https://max.ru/u/f9LHodD0cOLbeVqhBDicFxb54Fs1Gfwv5CrzosBzXHJju7KjKKuOy8PnNMI" target="_blank" rel="noopener">Написать в MAX</a></div>
        </div>
    </div>
    <div class="contacts-list__item">
        <svg class="contacts-list__icon" width="26" height="26" aria-hidden="true"><use xlink:href="#icon-mail"></use></svg>
        <div class="contacts-list__body">
            <div class="contacts-list__title">Почта</div>
            <div class="contacts-list__text"><a href="mailto:order@reppaopt.ru">order@reppaopt.ru</a></div>
        </div>
    </div>
    <div class="contacts-list__item">
        <svg class="contacts-list__icon" width="26" height="26" aria-hidden="true"><use xlink:href="#icon-clock"></use></svg>
        <div class="contacts-list__body">
            <div class="contacts-list__title">График работы</div>
            <div class="contacts-list__text">Офис: Пн.&nbsp;–&nbsp;Пт., 09:00&nbsp;–&nbsp;18:00; Сб, Вс&nbsp;— выходные.<br>Магазин: Пн.&nbsp;–&nbsp;Сб., 10:00&nbsp;–&nbsp;17:00; Вс&nbsp;— выходной.</div>
        </div>
    </div>
</div>
HTML;

    /** Прежний HTML страницы — для отката (down). */
    private const OLD_TEXT = <<<'HTML'
<table class="text-page__props"><tbody>
    <tr><th scope="row">Адрес</th><td>Московская область, г. Химки, ул. Молодёжная 15А<br><a href="https://yandex.ru/maps/?text=%D0%A5%D0%B8%D0%BC%D0%BA%D0%B8%2C%20%D1%83%D0%BB.%20%D0%9C%D0%BE%D0%BB%D0%BE%D0%B4%D1%91%D0%B6%D0%BD%D0%B0%D1%8F%2015%D0%90" target="_blank" rel="noopener">Открыть на карте</a></td></tr>
    <tr><th scope="row">Телефон</th><td><a href="tel:+74957927080">+7 495 792 70 80</a></td></tr>
    <tr><th scope="row">WhatsApp / Telegram</th><td><a href="https://wa.me/79651282114">+7 965 128 21 14</a></td></tr>
    <tr><th scope="row">E-mail</th><td><a href="mailto:order@reppaopt.ru">order@reppaopt.ru</a></td></tr>
    <tr><th scope="row">Режим работы</th><td>Офис Пн. – Пт.: с 09:00 - 18:00. Сб, Вс - выходные дни<br>Магазин Пн. – Сб.: 10.00-17.00. Вс- выходной</td></tr>
</tbody></table>
HTML;

    public function up()
    {
        Loader::includeModule('iblock');
        $this->setText(self::NEW_TEXT, 'Контакты → список в стиле подвала (адрес, телефон, MAX, почта, график)');
    }

    public function down()
    {
        Loader::includeModule('iblock');
        $this->setText(self::OLD_TEXT, 'Контакты → откат к прежней таблице');
    }

    /** Полная замена DETAIL_TEXT страницы с кодом «contacts». */
    private function setText(string $html, string $logMessage): void
    {
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists('content_pages', 'content');

        $el = \CIBlockElement::GetList([], ['IBLOCK_ID' => $iblockId, '=CODE' => 'contacts', 'CHECK_PERMISSIONS' => 'N'], false, ['nTopCount' => 1], ['ID', 'DETAIL_TEXT'])->Fetch();
        if (!$el) {
            $this->outWarning('Страница «contacts» не найдена — правка пропущена');
            return;
        }

        if (trim((string)$el['DETAIL_TEXT']) === trim($html)) {
            $this->outInfo('Страница «contacts» уже в нужном состоянии');
            return;
        }

        (new \CIBlockElement())->Update((int)$el['ID'], ['DETAIL_TEXT' => $html, 'DETAIL_TEXT_TYPE' => 'html']);
        \CIBlock::clearIblockTagCache($iblockId);
        $this->outSuccess('%s (элемент ID %d)', $logMessage, (int)$el['ID']);
    }
}
