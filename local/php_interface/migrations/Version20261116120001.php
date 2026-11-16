<?php

namespace Sprint\Migration;

use Bitrix\Main\Loader;
use Formaro\Cabinet\Settings\MarketplaceRequisites;

/**
 * Поправки сайта от 14.11.2026 (письмо клиента).
 *
 *  1. «Способ оплаты» (payment) — переформулировано вступление про оплату по счёту.
 *  2. «Оптовикам» (wholesale) — текст заменён целиком (партнёрская программа «Реппа Партнёр»).
 *  3. «Общая информация» (about) — бренд «Форма» → «Реппа».
 *  4. «Политика …» (policy), п. 1 — оператор «ИП Дасни Арина Юрьевна» → «ООО «Форма Ро»».
 *  5. Блок «Мы всегда поможем» (инфоблок 6, раздел support-links) — телефон → +7 495 792 70 80.
 *  6. Реквизиты площадки — ООО «Форма Ро» (ИНН/КПП/банк/счета/БИК/ЭДО СБИС), старые ИП-данные очищены.
 *
 * QR-код МАХ из п. 1 не вносится: нет файла картинки и поля под изображение в блоке.
 */
class Version20261116120001 extends Version
{
    protected $description = 'Поправки 14.11.2026: тексты (оплата/оптовикам/о нас/политика), телефон в «Мы всегда поможем», реквизиты — ООО «Форма Ро»';

    /** Новый текст страницы «Оптовикам» (полная замена). */
    private const WHOLESALE = <<<'HTML'
<p class="lead">Добро пожаловать в программу партнерского маркетинга «Реппа Партнер»!</p>
<p>Мы заинтересованы в сотрудничестве с оптовыми и постоянными покупателями. На сайте указаны оптовые цены. Для крупного опта у нас имеются специальные условия и гибкая система скидок, которые обсуждаются индивидуально.</p>
<p>Для того, чтобы получить дополнительную скидку, вы можете связаться с нами по:</p>
<ul>
    <li>Эл. почте: <a href="mailto:order@reppaopt.ru">order@reppaopt.ru</a></li>
    <li>Телефон: <a href="tel:+79671265771">+7 967 126 57 71</a>, <a href="tel:+74957927080">+7 495 792 70 80</a></li>
</ul>
HTML;

    /** Реквизиты ООО «Форма Ро»: заданные поля + очистка устаревших ИП-данных. */
    private const REQUISITES = [
        'name_full' => 'Общество с ограниченной ответственностью «Форма Ро»',
        'name_short' => 'ООО «Форма Ро»',
        'inn' => '7711021339',
        'kpp' => '774301001',
        'ogrn' => '',
        'legal_address' => '',
        'actual_address' => '',
        'bank_name' => 'Банк ВТБ 24(ПАО)',
        'bik' => '044525745',
        'account' => '40702810223000012419',
        'corr_account' => '30101810345250000745',
        'edo_sbis' => '2BE7b1b3a024b7511e3b041005056917125',
        'ceo_position' => '',
        'ceo_name' => '',
        'email' => 'order@reppaopt.ru',
    ];

    public function up()
    {
        Loader::includeModule('iblock');

        $this->patchPages();
        $this->patchSupportPhone();
        $this->patchRequisites();
    }

    /** Точечные правки текстов контентных страниц. */
    private function patchPages(): void
    {
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists('content_pages', 'content');

        $replaced = 0;
        $replaced += $this->replacePageText($iblockId, 'wholesale', null, self::WHOLESALE);
        $replaced += $this->replacePageText($iblockId, 'payment',
            'для физических лиц, юридических лиц и ИП. Счёт выставляет маркетплейс Reppa, оплата поступает на его расчётный счёт.',
            'для юридических лиц и ИП. Счёт выставляет маркетплейс Reppa, оплата поступает на его расчётный счёт и отражается на балансе поставщика.');
        $replaced += $this->replacePageText($iblockId, 'about', 'магазин Форма ', 'магазин Реппа ');
        $replaced += $this->replacePageText($iblockId, 'policy', 'ИП Дасни Арина Юрьевна', 'ООО «Форма Ро»');

        \CIBlock::clearIblockTagCache($iblockId);
        $this->outSuccess('Контентные страницы: обновлено %d', $replaced);
    }

    /**
     * $old === null — полная замена текста страницы $code на $new; иначе — замена подстроки $old → $new.
     * Возвращает 1 если обновили, 0 если менять нечего.
     */
    private function replacePageText(int $iblockId, string $code, ?string $old, string $new): int
    {
        $el = \CIBlockElement::GetList([], ['IBLOCK_ID' => $iblockId, '=CODE' => $code, 'CHECK_PERMISSIONS' => 'N'], false, ['nTopCount' => 1], ['ID', 'DETAIL_TEXT'])->Fetch();
        if (!$el) {
            $this->outWarning('Страница «%s» не найдена — правка пропущена', $code);
            return 0;
        }

        $text = (string)$el['DETAIL_TEXT'];
        if ($old === null) {
            $updated = trim($text) === trim($new) ? $text : $new;
        } elseif (strpos($text, $new) !== false) {
            return 0; // уже применено
        } elseif (strpos($text, $old) === false) {
            $this->outWarning('На странице «%s» не найден фрагмент для замены — правка пропущена', $code);
            return 0;
        } else {
            $updated = str_replace($old, $new, $text);
        }

        if ($updated === $text) {
            return 0;
        }
        (new \CIBlockElement())->Update((int)$el['ID'], ['DETAIL_TEXT' => $updated, 'DETAIL_TEXT_TYPE' => 'html']);

        return 1;
    }

    /** Телефон в блоке «Мы всегда поможем» (support-links) → +7 495 792 70 80. */
    private function patchSupportPhone(): void
    {
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists('list', 'content');

        $section = \CIBlockSection::GetList([], ['IBLOCK_ID' => $iblockId, '=CODE' => 'support-links', 'CHECK_PERMISSIONS' => 'N'], false, ['ID'])->Fetch();
        if (!$section) {
            $this->outWarning('Раздел «support-links» не найден — телефон не обновлён');
            return;
        }
        $sectionId = (int)$section['ID'];

        $NEW_NAME = '+7 495 792 70 80';
        $NEW_LINK = 'tel:+74957927080';
        $found = false;
        $elements = \CIBlockElement::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $iblockId, 'SECTION_ID' => $sectionId, 'CHECK_PERMISSIONS' => 'N'], false, false, ['ID', 'NAME']);
        while ($el = $elements->Fetch()) {
            // GetList не отдаёт свойство LINK — читаем его отдельно через GetProperty.
            $link = '';
            $props = \CIBlockElement::GetProperty($iblockId, (int)$el['ID'], ['sort' => 'asc'], ['CODE' => 'LINK']);
            if ($prop = $props->Fetch()) {
                $link = (string)(is_array($prop['VALUE']) ? ($prop['VALUE'][0] ?? '') : $prop['VALUE']);
            }
            if (strpos($link, 'tel:') !== 0) {
                continue; // ищем именно телефонный элемент
            }
            $found = true;
            if ($el['NAME'] !== $NEW_NAME || $link !== $NEW_LINK) {
                (new \CIBlockElement())->Update((int)$el['ID'], ['NAME' => $NEW_NAME]);
                \CIBlockElement::SetPropertyValuesEx((int)$el['ID'], $iblockId, ['LINK' => $NEW_LINK]);
                $this->outSuccess('Блок «Мы всегда поможем»: телефон обновлён (элемент ID %d)', (int)$el['ID']);
            }
            break;
        }
        if (!$found) {
            $this->outWarning('В разделе «support-links» не найдено телефонного элемента — правка пропущена');
        }
        \CIBlock::clearIblockTagCache($iblockId);
    }

    /** Реквизиты площадки — ООО «Форма Ро». */
    private function patchRequisites(): void
    {
        Loader::includeModule('formaro.cabinet');
        MarketplaceRequisites::set(self::REQUISITES);
        $this->outSuccess('Реквизиты площадки обновлены на ООО «Форма Ро» (ИНН %s)', self::REQUISITES['inn']);
    }

    public function down()
    {
        $this->outInfo('Откат не выполняется: тексты и реквизиты правятся только вперёд (исходные значения — в настройках модуля и админке)');
    }
}
