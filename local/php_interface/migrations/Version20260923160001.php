<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20260923160001 extends Version
{
    protected $description = 'formaro.cabinet: перенос новостей из IBLOCK_ID=1 ("Новости") в cabinet_news (IBLOCK_ID=10)';

    private const SOURCE_IBLOCK_ID = 1;

    /**
     * Мигрируемые новости — не партнёрские (в отличие от cabinet_news, где
     * "чьё это" разруливает свойство PARTNER_ID, см. Version20260911193003) —
     * общий контент площадки без владельца-партнёра, поэтому PARTNER_ID у
     * перенесённых элементов остаётся пустым. Раздел "partners" в
     * cabinet_news — общий контейнер для всех новостей кабинета (не
     * тематическая рубрикация), поэтому кладём туда и эти, отдельную
     * структуру разделов источника (Маркетплейс/Каталог/Партнеры) не
     * повторяем — она не поддерживается ни репозиторием, ни UI кабинета.
     * DETAIL_PICTURE источника не переносится — у cabinet_news нет
     * аналогичного поля (только PREVIEW_PICTURE, см. NewsRepository).
     *
     * Идемпотентность — как в Version20260923140001 (перенос партнёров):
     * XML_ID = 'legacy_news_{sourceId}', пропускаем при повторном запуске.
     *
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

        $sectionId = (int)(\CIBlockSection::GetList(
            [],
            ['IBLOCK_ID' => $targetIblockId, 'CODE' => 'partners', 'CHECK_PERMISSIONS' => 'N'],
            false,
            ['ID']
        )->Fetch()['ID'] ?? 0);
        if (!$sectionId) {
            throw new HelperException('Раздел "partners" не найден в инфоблоке cabinet_news');
        }

        $count = 0;
        $res = \CIBlockElement::GetList(
            ['ID' => 'ASC'],
            ['IBLOCK_ID' => self::SOURCE_IBLOCK_ID, 'CHECK_PERMISSIONS' => 'N'],
            false,
            false,
            ['ID', 'NAME', 'CODE', 'ACTIVE', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'PREVIEW_PICTURE', 'DATE_CREATE']
        );
        while ($el = $res->Fetch()) {
            $xmlId = 'legacy_news_' . $el['ID'];

            $exists = \CIBlockElement::GetList(
                [],
                ['IBLOCK_ID' => $targetIblockId, 'XML_ID' => $xmlId, 'CHECK_PERMISSIONS' => 'N'],
                false,
                false,
                ['ID']
            )->Fetch();
            if ($exists) {
                continue;
            }

            $fields = [
                'IBLOCK_ID' => $targetIblockId,
                'IBLOCK_SECTION_ID' => $sectionId,
                'NAME' => $el['NAME'],
                'CODE' => $el['CODE'],
                'ACTIVE' => $el['ACTIVE'],
                'PREVIEW_TEXT' => (string)$el['PREVIEW_TEXT'],
                'DETAIL_TEXT' => (string)$el['DETAIL_TEXT'],
                'DATE_CREATE' => $el['DATE_CREATE'],
                'XML_ID' => $xmlId,
            ];

            // CIBlockElement::Update()/Add() не принимают голый ID файла для
            // нативных picture-полей — нужен $_FILES-подобный массив (см.
            // CFile::MakeFileArray(), тот же паттерн, что и в переносе
            // партнёров/категорий).
            if ($el['PREVIEW_PICTURE']) {
                $fields['PREVIEW_PICTURE'] = \CFile::MakeFileArray((int)$el['PREVIEW_PICTURE']);
            }

            $element = new \CIBlockElement();
            $newId = $element->Add($fields);
            if (!$newId) {
                $this->outError('Не удалось перенести новость ID=%d: %s', $el['ID'], $element->LAST_ERROR);
                continue;
            }

            $count++;
        }

        $this->outSuccess('Перенесено новостей из IBLOCK_ID=%d в cabinet_news: %d', self::SOURCE_IBLOCK_ID, $count);
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

        $count = 0;
        $res = \CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $targetIblockId, '%XML_ID' => 'legacy_news_', 'CHECK_PERMISSIONS' => 'N'],
            false,
            false,
            ['ID']
        );
        while ($el = $res->Fetch()) {
            if (\CIBlockElement::Delete($el['ID'])) {
                $count++;
            }
        }

        $this->outSuccess('Удалено перенесённых новостей: %d', $count);
    }
}
