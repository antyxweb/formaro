<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Iblock\Component\Tools;
use Bitrix\Main\Loader;
use Formaro\Cabinet\Service\CatalogFilterService;

/**
 * Публичный каталог товаров партнёров (инфоблок cabinet_catalog), ЧПУ от
 * SEF_FOLDER (/catalog/):
 *  - sections — корень: категории, выгодные предложения, новинки;
 *  - section  — #SECTION_CODE_PATH#/: фильтр + товары категории
 *    (formaro:catalog.section); выбранный фильтр — в пути
 *    #SECTION_CODE_PATH#/filter/<сегменты>/ (формат — CatalogFilterUrl);
 *  - element  — #SECTION_CODE_PATH#/#ELEMENT_CODE#/: карточка товара
 *    (formaro:catalog.element).
 * Пути вида «раздел/подраздел» и «раздел/товар» различает
 * CIBlockFindTools::resolveComponentEngine (как стандартный bitrix:catalog).
 *
 * Показываются только активные категории, допущенные площадкой
 * (UF_APPROVED, вместе с родителями), и активные товары; иначе — 404.
 * Товар, открытый не по своему пути категории (например, его перенесли),
 * редиректится 301 на актуальный адрес.
 */
class FormaroCatalogComponent extends CBitrixComponent
{
    private const DEFAULT_TEMPLATES = [
        'sections' => '',
        'section' => '#SECTION_CODE_PATH#/',
        'element' => '#SECTION_CODE_PATH#/#ELEMENT_CODE#/',
    ];

    public function onPrepareComponentParams($params)
    {
        $params['SEF_FOLDER'] = (string)($params['SEF_FOLDER'] ?? '/catalog/');
        $params['PAGE_SIZE'] = max(1, (int)($params['PAGE_SIZE'] ?? 24));
        $params['CACHE_TIME'] = isset($params['CACHE_TIME']) ? (int)$params['CACHE_TIME'] : 3600;

        return $params;
    }

    public function executeComponent()
    {
        if (!Loader::includeModule('iblock') || !Loader::includeModule('formaro.cabinet')) {
            ShowError('formaro.cabinet module not found');
            return;
        }
        // resolveComponentEngine берёт инфоблок из параметров компонента.
        $this->arParams['IBLOCK_ID'] = CatalogFilterService::getCatalogIblockId();

        $templates = CComponentEngine::makeComponentUrlTemplates(self::DEFAULT_TEMPLATES, $this->arParams['SEF_URL_TEMPLATES'] ?? []);
        $engine = new CComponentEngine($this);
        $engine->addGreedyPart('#SECTION_CODE_PATH#');
        $engine->setResolveCallback(['CIBlockFindTools', 'resolveComponentEngine']);
        // Фильтр категории в ЧПУ: …/<категория>/filter/<сегменты>/ — хвост
        // после /filter/ отделяем и отдаём formaro:catalog.section, остальное
        // разбирает движок как обычную категорию.
        $path = $this->request->getRequestedPage();
        $filterPath = '';
        if (preg_match('#^(.+?/)filter/(.+?)/(?:index\.php)?$#', $path, $m)) {
            $path = $m[1] . 'index.php';
            $filterPath = $m[2];
        }

        $variables = [];
        $page = $engine->guessComponentPath($this->arParams['SEF_FOLDER'], $templates, $variables, $path);
        if ($page === false) {
            // Пустой шаблон корня сам по себе не совпадает — корень узнаём по адресу.
            $page = in_array($path, [$this->arParams['SEF_FOLDER'], $this->arParams['SEF_FOLDER'] . 'index.php'], true) ? 'sections' : false;
        }
        if ($filterPath !== '' && $page !== 'section') {
            $this->show404();
            return;
        }

        $this->arResult = [
            'SEF_FOLDER' => $this->arParams['SEF_FOLDER'],
            'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
            'VARIABLES' => $variables,
            'FILTER_PATH' => $filterPath,
            'SECTION' => null,
            'ELEMENT_ID' => 0,
        ];

        if ($page === 'section' || $page === 'element') {
            $section = $this->resolveSection((string)($variables['SECTION_CODE_PATH'] ?? ''));
            if (!$section) {
                // Товар, открытый по старому пути категории, движок принимает
                // за несуществующий раздел — проверяем последний сегмент как
                // код товара.
                $codePath = trim((string)($variables['SECTION_CODE_PATH'] ?? ''), '/');
                $element = $page === 'section' ? $this->resolveElement((string)substr(strrchr('/' . $codePath, '/'), 1)) : null;
                if ($element) {
                    LocalRedirect($element['DETAIL_PAGE_URL'], false, '301 Moved Permanently');
                    return;
                }
                $this->show404();
                return;
            }
            $this->arResult['SECTION'] = $section;
        }

        if ($page === 'element') {
            $element = $this->resolveElement((string)($variables['ELEMENT_CODE'] ?? ''));
            if (!$element) {
                $this->show404();
                return;
            }
            if ((int)$element['IBLOCK_SECTION_ID'] !== $this->arResult['SECTION']['ID']) {
                LocalRedirect($element['DETAIL_PAGE_URL'], false, '301 Moved Permanently');
                return;
            }
            $this->arResult['ELEMENT_ID'] = (int)$element['ID'];
        } elseif ($page !== 'section' && $page !== 'sections') {
            $this->show404();
            return;
        }

        $this->setChain($page);
        $this->includeComponentTemplate($page);
    }

    /** Раздел по пути кодов; активный и одобренный вместе со всеми родителями. */
    private function resolveSection(string $codePath): ?array
    {
        $sectionId = (int)CIBlockFindTools::GetSectionIDByCodePath($this->arParams['IBLOCK_ID'], $codePath);
        if (!$sectionId) {
            return null;
        }

        $chain = [];
        $res = CIBlockSection::GetNavChain($this->arParams['IBLOCK_ID'], $sectionId, ['ID', 'NAME', 'CODE', 'SECTION_PAGE_URL', 'IBLOCK_SECTION_ID', 'DEPTH_LEVEL'], true);
        $ids = array_column($res, 'ID');
        $meta = [];
        $metaRes = CIBlockSection::GetList([], ['IBLOCK_ID' => $this->arParams['IBLOCK_ID'], 'ID' => $ids, 'CHECK_PERMISSIONS' => 'N'], false, ['ID', 'ACTIVE', 'GLOBAL_ACTIVE', 'UF_APPROVED', 'SECTION_PAGE_URL']);
        while ($row = $metaRes->GetNext()) {
            $meta[(int)$row['ID']] = $row;
        }
        foreach ($res as $row) {
            $m = $meta[(int)$row['ID']] ?? null;
            if (!$m || $m['GLOBAL_ACTIVE'] !== 'Y' || empty($m['UF_APPROVED'])) {
                return null;
            }
            $chain[] = [
                'ID' => (int)$row['ID'],
                'NAME' => $row['NAME'],
                'URL' => $m['SECTION_PAGE_URL'],
                'PARENT_ID' => (int)$row['IBLOCK_SECTION_ID'],
                'DEPTH_LEVEL' => (int)$row['DEPTH_LEVEL'],
            ];
        }

        $current = end($chain);

        return $current + ['CHAIN' => $chain];
    }

    private function resolveElement(string $code): ?array
    {
        if ($code === '') {
            return null;
        }
        $element = CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $this->arParams['IBLOCK_ID'], '=CODE' => $code, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
            false,
            ['nTopCount' => 1],
            ['ID', 'NAME', 'IBLOCK_SECTION_ID', 'DETAIL_PAGE_URL']
        )->GetNext();

        return $element ?: null;
    }

    /** Заголовок страницы и хлебные крошки (корень «Каталог» даёт
     *  .section.php папки). Для товара заголовок ставит
     *  formaro:catalog.element. */
    private function setChain(string $page): void
    {
        global $APPLICATION;

        if ($page === 'sections') {
            $APPLICATION->SetTitle('Каталог товаров');
            return;
        }

        $chain = $this->arResult['SECTION']['CHAIN'];
        foreach ($chain as $i => $item) {
            $isLast = $page === 'section' && $i === count($chain) - 1;
            $APPLICATION->AddChainItem($item['NAME'], $isLast ? '' : $item['URL']);
        }
        if ($page === 'section') {
            $APPLICATION->SetTitle($this->arResult['SECTION']['NAME']);
            $APPLICATION->SetPageProperty('title', $this->arResult['SECTION']['NAME']);
        }
    }

    private function show404(): void
    {
        Tools::process404('Страница не найдена', true, true, true);
    }
}
