<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261001200001 extends Version
{
    protected $description = 'Контент сайта: совместный режим просмотра разделов и элементов; разделы «Контентных страниц»';

    /** Код раздела => [название, сортировка, коды страниц]. */
    private const SECTIONS = [
        'for-buyers' => ['Покупателям', 100, ['make-order', 'payment', 'delivery', 'returns']],
        'for-partners' => ['Партнерам', 200, ['become-a-partner', 'sell-products']],
        'about' => ['О нас', 300, ['about', 'details', 'contacts', 'vacancies']],
        'support' => ['Поддержка', 400, ['faq', 'reviews', 'policy', 'cookie']],
    ];

    /**
     * Во всех инфоблоках контента — «Режим просмотра разделов и элементов:
     * совместный» (LIST_MODE = C). В «Контентных страницах» — разделы как
     * разделы сайта (Покупателям, Партнерам, О нас, Поддержка); страницы
     * раскладываются по ним, только те, что ещё без раздела (переложенные
     * вручную не трогаем). Вывод страниц от разделов не зависит
     * (formaro:content.page ищет по коду элемента).
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        foreach (['content_pages', 'content_faq', 'content_reviews', 'content_vacancies'] as $code) {
            $id = $helper->Iblock()->getIblockIdIfExists($code, 'content');
            $helper->Iblock()->updateIblock($id, ['LIST_MODE' => 'C']);
        }

        $pagesId = $helper->Iblock()->getIblockIdIfExists('content_pages', 'content');
        $moved = 0;
        foreach (self::SECTIONS as $sectionCode => [$name, $sort, $pages]) {
            $sectionId = $helper->Iblock()->saveSectionByCode($pagesId, [
                'CODE' => $sectionCode,
                'NAME' => $name,
                'SORT' => $sort,
                'ACTIVE' => 'Y',
            ]);
            $res = \CIBlockElement::GetList([], [
                'IBLOCK_ID' => $pagesId,
                '=CODE' => $pages,
                'SECTION_ID' => false,
                'CHECK_PERMISSIONS' => 'N',
            ], false, false, ['ID']);
            while ($row = $res->Fetch()) {
                \CIBlockElement::SetElementSection((int)$row['ID'], [$sectionId]);
                \Bitrix\Iblock\PropertyIndex\Manager::updateElementIndex($pagesId, (int)$row['ID']);
                $moved++;
            }
        }
        \CIBlock::clearIblockTagCache($pagesId);

        $this->outSuccess('Режим просмотра — совместный; разделы страниц готовы, разложено страниц: %d', $moved);
    }

    /**
     * Разделы не удаляем: в них могли добавить страницы. Режим просмотра
     * возвращаем раздельный.
     *
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();
        foreach (['content_pages', 'content_faq', 'content_reviews', 'content_vacancies'] as $code) {
            $id = $helper->Iblock()->getIblockIdIfExists($code, 'content');
            $helper->Iblock()->updateIblock($id, ['LIST_MODE' => 'S']);
        }
        $this->outSuccess('Режим просмотра — раздельный');
    }
}
