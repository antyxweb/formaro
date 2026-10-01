<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Iblock\InheritedProperty\ElementValues;
use Bitrix\Main\Loader;

/**
 * Контентная страница из инфоблока «Контентные страницы» (content_pages,
 * тип content; миграции Version20261001190001/190002): разделы
 * «Покупателям», «Партнерам», «О нас», «Поддержка».
 *
 * Страница — элемент с кодом CODE (передаёт index.php папки): NAME —
 * заголовок, DETAIL_TEXT — текст, вкладка «SEO» — title/description.
 * Свойство EMBED — блоки других инфоблоков под текстом (XML_ID значения =
 * код инфоблока): «Вопросы и ответы» (content_faq), «Отзывы»
 * (content_reviews), «Вакансии» (content_vacancies); вёрстка блока —
 * templates/.default/embeds/<код>.php.
 *
 * Кэш (только данные) — тегированный по инфоблокам: правка элемента в
 * админке сразу видна.
 * Нет элемента — 404.
 *
 * Параметры: CODE, ADD_CHAIN (Y — название в хлебные крошки; N — когда у
 * папки есть свой .section.php), CACHE_TYPE/CACHE_TIME.
 */
class FormaroContentPageComponent extends CBitrixComponent
{
    private const PAGES = 'content_pages';
    private const EMBEDS = ['content_faq', 'content_reviews', 'content_vacancies'];

    public function onPrepareComponentParams($params)
    {
        $params['CODE'] = trim((string)($params['CODE'] ?? ''));
        $params['ADD_CHAIN'] = ($params['ADD_CHAIN'] ?? 'Y') === 'N' ? 'N' : 'Y';
        $params['CACHE_TIME'] = (int)($params['CACHE_TIME'] ?? 36000000);

        return $params;
    }

    public function executeComponent()
    {
        global $APPLICATION;
        if (!Loader::includeModule('iblock')) {
            ShowError('Модуль «Информационные блоки» не установлен');
            return;
        }

        // В кэше — только данные; вёрстка — при каждом показе: в ней меню
        // раздела (text_page_bottom.php) — его стили и текущий пункт не
        // попали бы в закэшированный вывод.
        if ($this->startResultCache()) {
            $this->arResult = $this->load() ?? [];
            if (!$this->arResult) {
                $this->abortResultCache();
            } else {
                $this->endResultCache();
            }
        }

        if (!$this->arResult) {
            \Bitrix\Iblock\Component\Tools::process404('Страница не найдена', true, true, true);
            return;
        }
        $this->includeComponentTemplate();

        $seo = $this->arResult['SEO'];
        $APPLICATION->SetTitle($seo['ELEMENT_PAGE_TITLE'] ?: $this->arResult['NAME']);
        $APPLICATION->SetPageProperty('title', $seo['ELEMENT_META_TITLE'] ?: $this->arResult['NAME']);
        if ($seo['ELEMENT_META_DESCRIPTION']) {
            $APPLICATION->SetPageProperty('description', $seo['ELEMENT_META_DESCRIPTION']);
        }
        if ($seo['ELEMENT_META_KEYWORDS']) {
            $APPLICATION->SetPageProperty('keywords', $seo['ELEMENT_META_KEYWORDS']);
        }
        if ($this->arParams['ADD_CHAIN'] === 'Y') {
            $APPLICATION->AddChainItem($this->arResult['NAME']);
        }

        // «Изменить страницу» в публичной части для редакторов.
        if ($APPLICATION->GetShowIncludeAreas()) {
            $buttons = CIBlock::GetPanelButtons($this->arResult['IBLOCK_ID'], $this->arResult['ID'], 0, ['SECTION_BUTTONS' => false]);
            $this->addIncludeAreaIcons(CIBlock::GetComponentMenu($APPLICATION->GetPublicShowMode(), $buttons));
        }
    }

    private function load(): ?array
    {
        $pagesId = $this->iblockId(self::PAGES);
        if (!$pagesId || $this->arParams['CODE'] === '') {
            return null;
        }
        CIBlock::registerWithTagCache($pagesId);

        $element = CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $pagesId, '=CODE' => $this->arParams['CODE'], 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'Y'],
            false,
            ['nTopCount' => 1],
            ['ID', 'IBLOCK_ID', 'NAME', 'DETAIL_TEXT', 'DETAIL_TEXT_TYPE']
        )->Fetch();
        if (!$element) {
            return null;
        }

        $embeds = [];
        $props = CIBlockElement::GetProperty($pagesId, $element['ID'], ['SORT' => 'ASC', 'VALUE_SORT' => 'ASC'], ['CODE' => 'EMBED']);
        while ($prop = $props->Fetch()) {
            $code = (string)$prop['VALUE_XML_ID'];
            if (in_array($code, self::EMBEDS, true) && !isset($embeds[$code])) {
                $embeds[$code] = $this->embedItems($code);
            }
        }

        $text = (string)$element['DETAIL_TEXT'];

        return [
            'ID' => (int)$element['ID'],
            'IBLOCK_ID' => $pagesId,
            'NAME' => (string)$element['NAME'],
            'TEXT' => $element['DETAIL_TEXT_TYPE'] === 'html' ? $text : nl2br(htmlspecialcharsbx($text)),
            'EMBEDS' => $embeds,
            'SEO' => (new ElementValues($pagesId, $element['ID']))->getValues() + [
                'ELEMENT_PAGE_TITLE' => '', 'ELEMENT_META_TITLE' => '', 'ELEMENT_META_DESCRIPTION' => '', 'ELEMENT_META_KEYWORDS' => '',
            ],
        ];
    }

    /** Активные элементы встроенного инфоблока по сортировке (отзывы — свежие выше при равной). */
    private function embedItems(string $code): array
    {
        $iblockId = $this->iblockId($code);
        if (!$iblockId) {
            return [];
        }
        CIBlock::registerWithTagCache($iblockId);

        $select = ['ID', 'IBLOCK_ID', 'NAME', 'PREVIEW_TEXT', 'PREVIEW_TEXT_TYPE', 'ACTIVE_FROM'];
        if ($code === 'content_vacancies') {
            $select[] = 'PROPERTY_SALARY';
            $select[] = 'PROPERTY_EMAIL';
        }
        $items = [];
        $res = CIBlockElement::GetList(
            ['SORT' => 'ASC', 'ACTIVE_FROM' => 'DESC', 'ID' => 'ASC'],
            ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y', 'CHECK_PERMISSIONS' => 'Y'],
            false,
            false,
            $select
        );
        while ($row = $res->Fetch()) {
            $text = (string)$row['PREVIEW_TEXT'];
            $items[] = [
                'ID' => (int)$row['ID'],
                'NAME' => (string)$row['NAME'],
                'TEXT' => $row['PREVIEW_TEXT_TYPE'] === 'html' ? $text : nl2br(htmlspecialcharsbx($text)),
                'DATE' => $row['ACTIVE_FROM'] ? FormatDate('j F Y', MakeTimeStamp($row['ACTIVE_FROM'])) : '',
                'SALARY' => (string)($row['PROPERTY_SALARY_VALUE'] ?? ''),
                'EMAIL' => (string)($row['PROPERTY_EMAIL_VALUE'] ?? ''),
            ];
        }

        return $items;
    }

    private function iblockId(string $code): int
    {
        $row = CIBlock::GetList([], ['=CODE' => $code, 'TYPE' => 'content', 'CHECK_PERMISSIONS' => 'N'])->Fetch();

        return $row ? (int)$row['ID'] : 0;
    }
}
