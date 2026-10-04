<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261004120001 extends Version
{
    protected $description = 'Гайд по сайту: главы о кабинете администратора (/admin/), обновление глав про модерацию, оплату, вывод средств и чаты';

    /** Главы, текст которых обновляется из data/guide (остальные не трогаем — их могли поправить в админке). */
    private const UPDATED = [
        'overview', 'buyer-notify-chat', 'partners-public', 'cabinet-catalog', 'cabinet-orders',
        'cabinet-finance', 'cabinet-communication', 'owner-tasks', 'admin-start', 'admin-operations',
    ];

    /**
     * Новые главы (admin-start, admin-operations) добавляются в раздел
     * «guide» по data/guide/chapters.json, у глав из UPDATED обновляется
     * текст. Скриншоты — /guide/img/a-*.jpg.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists('content_pages', 'content');
        $section = \CIBlockSection::GetList([], ['IBLOCK_ID' => $iblockId, '=CODE' => 'guide', 'CHECK_PERMISSIONS' => 'N'], false, ['ID'])->Fetch();
        if (!$section) {
            throw new HelperException('Нет раздела «guide» — сначала Version20261002110001');
        }

        $dir = __DIR__ . '/data/guide/';
        $chapters = json_decode((string)file_get_contents($dir . 'chapters.json'), true);
        $added = $updated = 0;
        foreach ($chapters as $chapter) {
            $text = (string)file_get_contents($dir . $chapter['file']);
            $id = (int)$helper->Iblock()->getElementId($iblockId, $chapter['code']);
            if (!$id) {
                $helper->Iblock()->addElement($iblockId, [
                    'NAME' => $chapter['name'],
                    'CODE' => $chapter['code'],
                    'SORT' => $chapter['sort'],
                    'ACTIVE' => 'Y',
                    'IBLOCK_SECTION_ID' => (int)$section['ID'],
                    'DETAIL_TEXT' => $text,
                    'DETAIL_TEXT_TYPE' => 'html',
                ], ['ICON' => $chapter['icon']]);
                $added++;
            } elseif (in_array($chapter['code'], self::UPDATED, true)) {
                (new \CIBlockElement())->Update($id, ['DETAIL_TEXT' => $text, 'DETAIL_TEXT_TYPE' => 'html']);
                $updated++;
            }
        }
        \CIBlock::clearIblockTagCache($iblockId);

        $this->outSuccess('Гайд: добавлено глав %d, обновлено %d', $added, $updated);
    }

    /** @throws HelperException */
    public function down()
    {
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists('content_pages', 'content');
        foreach (['admin-start', 'admin-operations'] as $code) {
            $id = (int)$helper->Iblock()->getElementId($iblockId, $code);
            if ($id) {
                \CIBlockElement::Delete($id);
            }
        }
        \CIBlock::clearIblockTagCache($iblockId);
        $this->outSuccess('Главы о кабинете администратора удалены (тексты остальных глав не откатываются)');
    }
}
