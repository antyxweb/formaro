<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Formaro\Cabinet\Content\SectionPages;

/**
 * Раздел сайта из инфоблока «Контентные страницы» (комплексный, ЧПУ):
 * «Покупателям», «Партнерам», «О нас», «Поддержка» — папка раздела с одним
 * index.php и правилом в urlrewrite.php.
 *
 *   SEF_FOLDER           — главная раздела: страница с кодом, равным коду
 *                          раздела (как «Общая информация» в /about/), если
 *                          она есть, иначе плитки пунктов раздела (над ними —
 *                          описание раздела инфоблока, если заполнено);
 *   SEF_FOLDER#CODE#/    — страница (formaro:content.page по коду элемента;
 *                          элемент-ссылка — редирект по ссылке).
 *
 * Пункты раздела (плитки, меню справа, подвал) — элементы раздела
 * инфоблока по сортировке (SectionPages, .left.menu_ext.php папки):
 * добавили страницу в админке — она сама появилась везде.
 *
 * Параметры: SECTION_CODE (код раздела инфоблока = папка), SEF_FOLDER,
 * SEF_URL_TEMPLATES (index, page), CACHE_TYPE/CACHE_TIME.
 */
class FormaroContentSectionComponent extends CBitrixComponent
{
    private const DEFAULT_TEMPLATES = [
        'index' => '',
        'page' => '#ELEMENT_CODE#/',
    ];

    public function onPrepareComponentParams($params)
    {
        $params['SECTION_CODE'] = trim((string)($params['SECTION_CODE'] ?? ''));
        $params['SEF_FOLDER'] = (string)($params['SEF_FOLDER'] ?? '/' . $params['SECTION_CODE'] . '/');
        $params['CACHE_TYPE'] = (string)($params['CACHE_TYPE'] ?? 'A');
        $params['CACHE_TIME'] = (int)($params['CACHE_TIME'] ?? 36000000);

        return $params;
    }

    public function executeComponent()
    {
        if (!Loader::includeModule('iblock') || !Loader::includeModule('formaro.cabinet')) {
            ShowError('Не установлены модули iblock / formaro.cabinet');
            return;
        }

        $templates = CComponentEngine::makeComponentUrlTemplates(self::DEFAULT_TEMPLATES, $this->arParams['SEF_URL_TEMPLATES'] ?? []);
        $variables = [];
        $engine = new CComponentEngine($this);
        $page = $engine->guessComponentPath($this->arParams['SEF_FOLDER'], $templates, $variables);
        if (!$page) {
            // Пустой шаблон index движок не распознаёт: сама папка раздела
            // (/about/ или /about/index.php) — главная, остальное — 404.
            global $APPLICATION;
            $current = preg_replace('#index\.php$#', '', $APPLICATION->GetCurPage(true));
            $page = $current === $this->arParams['SEF_FOLDER'] ? 'index' : '';
        }

        $items = SectionPages::items($this->arParams['SECTION_CODE']);
        $byCode = array_column($items, null, 'code');

        if ($page === 'page') {
            $code = (string)($variables['ELEMENT_CODE'] ?? '');
            // Главная раздела по своему коду (/about/about/) — на /about/.
            if ($code === $this->arParams['SECTION_CODE']) {
                LocalRedirect($this->arParams['SEF_FOLDER'], false, '301 Moved Permanently');
            }
            if (isset($byCode[$code]) && $byCode[$code]['link'] !== '') {
                LocalRedirect($byCode[$code]['link']);
            }
            $this->arResult = ['CODE' => $code, 'ADD_CHAIN' => 'Y'];
        } elseif ($page === 'index' && isset($byCode[$this->arParams['SECTION_CODE']]) && $byCode[$this->arParams['SECTION_CODE']]['link'] === '') {
            $page = 'page';
            $this->arResult = ['CODE' => $this->arParams['SECTION_CODE'], 'ADD_CHAIN' => 'N'];
        } elseif ($page === 'index') {
            $this->arResult = ['ITEMS' => $items, 'DESCRIPTION' => SectionPages::description($this->arParams['SECTION_CODE'])];
            $name = SectionPages::name($this->arParams['SECTION_CODE']);
            if ($name !== '') {
                global $APPLICATION;
                $APPLICATION->SetTitle($name);
                $APPLICATION->SetPageProperty('title', $name);
            }
        } else {
            \Bitrix\Iblock\Component\Tools::process404('Страница не найдена', true, true, true);
            return;
        }

        $this->includeComponentTemplate($page);
    }
}
