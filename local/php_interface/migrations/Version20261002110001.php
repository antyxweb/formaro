<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261002110001 extends Version
{
    protected $description = 'Контентные страницы: раздел «Гайд по сайту» (/guide/) — документация сайта для владельца';

    private const SECTION_CODE = 'guide';
    private const SECTION_NAME = 'Гайд по сайту';
    private const SECTION_SORT = 900;

    /** Вступление над плитками глав на /guide/ (описание раздела). */
    private const INTRO = <<<'HTML'
<p class="lead">Описание сайта Formaro для владельца: что видят посетители, покупатели и партнёры, как работают основные сценарии и что нужно делать самому. Скриншоты кликабельны — открываются в полный размер.</p>
<p>Начните с главы «Обзор сайта». Административная панель Битрикса в гайд не входит. Гайд будет дополняться.</p>
HTML;

    /**
     * Раздел «guide» инфоблока «Контентные страницы» и главы гайда —
     * элементы раздела (данные — data/guide/chapters.json, тексты —
     * data/guide/*.html; скриншоты — папка сайта /guide/img/). Вывод —
     * /guide/ (formaro:content.section): плитки глав с вступлением,
     * страницы глав с меню справа. Доступ к папке — только администраторам
     * (/guide/.access.php).
     *
     * Уже существующие главы (по коду) не перезаписываются — их могли
     * поправить в админке; новые главы — следующими миграциями.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists('content_pages', 'content');
        $sectionId = $helper->Iblock()->saveSectionByCode($iblockId, [
            'CODE' => self::SECTION_CODE,
            'NAME' => self::SECTION_NAME,
            'SORT' => self::SECTION_SORT,
            'ACTIVE' => 'Y',
            'DESCRIPTION' => self::INTRO,
            'DESCRIPTION_TYPE' => 'html',
        ]);

        $dir = __DIR__ . '/data/guide/';
        $chapters = json_decode((string)file_get_contents($dir . 'chapters.json'), true);
        if (!is_array($chapters)) {
            throw new HelperException('Не прочитан data/guide/chapters.json');
        }

        $added = 0;
        foreach ($chapters as $chapter) {
            if ($helper->Iblock()->getElementId($iblockId, $chapter['code'])) {
                continue;
            }
            $text = file_get_contents($dir . $chapter['file']);
            if ($text === false) {
                throw new HelperException('Нет файла главы ' . $chapter['file']);
            }
            $helper->Iblock()->addElement($iblockId, [
                'NAME' => $chapter['name'],
                'CODE' => $chapter['code'],
                'SORT' => $chapter['sort'],
                'ACTIVE' => 'Y',
                'IBLOCK_SECTION_ID' => $sectionId,
                'DETAIL_TEXT' => $text,
                'DETAIL_TEXT_TYPE' => 'html',
            ], [
                'ICON' => $chapter['icon'],
            ]);
            $added++;
        }
        \CIBlock::clearIblockTagCache($iblockId);

        $this->outSuccess('Раздел «%s» готов, добавлено глав: %d', self::SECTION_NAME, $added);
    }

    /** @throws HelperException */
    public function down()
    {
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists('content_pages', 'content');
        $section = \CIBlockSection::GetList([], ['IBLOCK_ID' => $iblockId, '=CODE' => self::SECTION_CODE, 'CHECK_PERMISSIONS' => 'N'], false, ['ID'])->Fetch();
        if ($section) {
            // Удаляет и элементы раздела.
            \CIBlockSection::Delete((int)$section['ID']);
        }
        \CIBlock::clearIblockTagCache($iblockId);
        $this->outSuccess('Раздел «%s» удалён', self::SECTION_NAME);
    }
}
