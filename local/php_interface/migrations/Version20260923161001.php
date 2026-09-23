<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20260923161001 extends Version
{
    protected $description = 'formaro.cabinet: разделы новостей из IBLOCK_ID=1 в cabinet_news + переразметка перенесённых новостей по своим разделам';

    private const SOURCE_IBLOCK_ID = 1;

    /** source IBLOCK_SECTION_ID => новый раздел cabinet_news.
     *  Раздел-источник "Партнеры" (ID=3) назван так же, как уже существующий
     *  в cabinet_news общий раздел "partners" (см. Version20260911193003), но
     *  по смыслу это разное — там общий контейнер всех новостей кабинета, тут
     *  тематическая рубрика источника. Даю другой CODE, иначе
     *  NewsRepository::resolveSectionId() (ищет по CODE='partners') получил
     *  бы неоднозначность между двумя разделами с одинаковым кодом. */
    private const SECTION_MAP = [
        1 => ['code' => 'legacy-marketplace', 'name' => 'Маркетплейс'],
        2 => ['code' => 'legacy-catalog', 'name' => 'Каталог'],
        3 => ['code' => 'legacy-partners-topic', 'name' => 'Партнеры'],
    ];

    /**
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $targetIblockId = $helper->Iblock()->getIblockIdIfExists('cabinet_news', 'content');
        if (!$targetIblockId) {
            throw new HelperException(
                'Инфоблок "cabinet_news" не найден — сначала должна отработать миграция Version20260911193003'
            );
        }

        $sectionIdMap = [];
        foreach (self::SECTION_MAP as $sourceSectionId => $info) {
            $sectionIdMap[$sourceSectionId] = $helper->Iblock()->saveSectionByCode($targetIblockId, [
                'CODE' => $info['code'],
                'NAME' => $info['name'],
                'SORT' => 500,
            ]);
        }

        $count = 0;
        $res = \CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => self::SOURCE_IBLOCK_ID, 'CHECK_PERMISSIONS' => 'N'],
            false,
            false,
            ['ID', 'IBLOCK_SECTION_ID']
        );
        while ($el = $res->Fetch()) {
            $targetSectionId = $sectionIdMap[(int)$el['IBLOCK_SECTION_ID']] ?? null;
            if (!$targetSectionId) {
                continue;
            }

            // Перенесённый элемент ищем по XML_ID, проставленному в
            // Version20260923160001 — этой миграции не важно, каким способом
            // он там оказался, только бы существовал.
            $xmlId = 'legacy_news_' . $el['ID'];
            $target = \CIBlockElement::GetList(
                [],
                ['IBLOCK_ID' => $targetIblockId, 'XML_ID' => $xmlId, 'CHECK_PERMISSIONS' => 'N'],
                false,
                false,
                ['ID', 'IBLOCK_SECTION_ID']
            )->Fetch();
            if (!$target || (int)$target['IBLOCK_SECTION_ID'] === $targetSectionId) {
                continue;
            }

            (new \CIBlockElement())->Update($target['ID'], ['IBLOCK_SECTION_ID' => $targetSectionId]);
            $count++;
        }

        $this->outSuccess(
            'Разделы новостей созданы: %d, переразмечено новостей: %d',
            count($sectionIdMap),
            $count
        );
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();
        $targetIblockId = $helper->Iblock()->getIblockIdIfExists('cabinet_news', 'content');
        if (!$targetIblockId) {
            $this->outError('Инфоблок "cabinet_news" не найден');
            return;
        }

        $partnersSectionId = (int)(\CIBlockSection::GetList(
            [],
            ['IBLOCK_ID' => $targetIblockId, 'CODE' => 'partners', 'CHECK_PERMISSIONS' => 'N'],
            false,
            ['ID']
        )->Fetch()['ID'] ?? 0);

        $count = 0;
        foreach (self::SECTION_MAP as $info) {
            if ($partnersSectionId) {
                $res = \CIBlockElement::GetList(
                    [],
                    ['IBLOCK_ID' => $targetIblockId, 'SECTION_CODE' => $info['code'], 'CHECK_PERMISSIONS' => 'N'],
                    false,
                    false,
                    ['ID']
                );
                while ($el = $res->Fetch()) {
                    (new \CIBlockElement())->Update($el['ID'], ['IBLOCK_SECTION_ID' => $partnersSectionId]);
                    $count++;
                }
            }

            $helper->Iblock()->deleteSectionIfExists($targetIblockId, $info['code']);
        }

        $this->outSuccess('Разделы удалены, новости возвращены в "partners": %d', $count);
    }
}
