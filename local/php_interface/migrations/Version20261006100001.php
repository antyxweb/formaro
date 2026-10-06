<?php

namespace Sprint\Migration;

use Bitrix\Main\Loader;
use Formaro\Cabinet\Settings\MarketplaceRequisites;

class Version20261006100001 extends Version
{
    protected $description = 'Реквизиты площадки — в настройки модуля formaro.cabinet; страницы «Реквизиты» и «Способ оплаты» выводят их метками';

    /** Реквизиты, которые были вписаны в страницы «Реквизиты» и «Способ оплаты». */
    private const DEFAULTS = [
        'name_full' => 'Индивидуальный предприниматель Дасни Арина Юрьевна',
        'name_short' => 'ИП Дасни А.Ю.',
        'inn' => '773320714684',
        'ogrn' => '321774600374611',
        'actual_address' => '125466, г. Москва, Новокуркинское шоссе, д. 1',
        'bank_name' => 'ПАО Сбербанк, г. Москва',
        'bik' => '044525225',
        'account' => '40802810540000184312',
        'corr_account' => '30101810400000000225',
        'ceo_position' => 'Генеральный директор',
        'ceo_name' => 'Дасни Арина Юрьевна',
    ];

    /** Код страницы => метка, которой заменяется её таблица реквизитов. */
    private const PAGES = [
        'details' => '#REQUISITES#',
        'payment' => '#REQUISITES_BANK#',
    ];

    /**
     * Пустые поля настроек заполняются реквизитами со страниц (заполненные
     * не трожем); таблица реквизитов на странице «Реквизиты» (about/details)
     * и «Банковские реквизиты» на «Способе оплаты» (for-buyers/payment)
     * заменяются метками — дальше реквизиты правятся только в настройках.
     */
    public function up()
    {
        Loader::includeModule('formaro.cabinet');
        Loader::includeModule('iblock');

        $current = MarketplaceRequisites::get();
        $fill = [];
        foreach (self::DEFAULTS as $code => $value) {
            if ($current[$code] === '') {
                $fill[$code] = $value;
            }
        }
        MarketplaceRequisites::set($fill);

        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists('content_pages', 'content');
        foreach (self::PAGES as $code => $token) {
            $el = \CIBlockElement::GetList([], ['IBLOCK_ID' => $iblockId, '=CODE' => $code, 'CHECK_PERMISSIONS' => 'N'], false, ['nTopCount' => 1], ['ID', 'DETAIL_TEXT'])->Fetch();
            if (!$el || strpos($el['DETAIL_TEXT'], $token) !== false) {
                continue;
            }
            // Последняя таблица страницы — реквизиты (на «Способе оплаты» она под «Банковскими реквизитами»).
            $text = (string)$el['DETAIL_TEXT'];
            $pos = strrpos($text, '<table class="text-page__props">');
            $end = $pos === false ? false : strpos($text, '</table>', $pos);
            if ($end === false) {
                $this->outWarning('На странице %s таблица реквизитов не найдена — метку %s добавьте вручную', $code, $token);
                continue;
            }
            $text = substr($text, 0, $pos) . $token . substr($text, $end + strlen('</table>'));
            (new \CIBlockElement())->Update((int)$el['ID'], ['DETAIL_TEXT' => $text, 'DETAIL_TEXT_TYPE' => 'html']);
        }
        \CIBlock::clearIblockTagCache($iblockId);

        $this->outSuccess('Реквизиты площадки в настройках модуля: заполнено полей %d', count($fill));
    }

    public function down()
    {
        $this->outInfo('Откат не выполняется: реквизиты остаются в настройках, метки — на страницах');
    }
}
