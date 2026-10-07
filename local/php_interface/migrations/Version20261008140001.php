<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261008140001 extends Version
{
    protected $description = 'Гайд по сайту: письма — уведомления дублируются письмом, настройки почты и шаблон писем';

    private const UPDATED = ['auth', 'buyer-notify-chat', 'cabinet-communication', 'admin-operations', 'owner-tasks'];

    /** Тексты глав UPDATED — из data/guide (как Version20261004120001). @throws HelperException */
    public function up()
    {
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists('content_pages', 'content');
        $dir = __DIR__ . '/data/guide/';
        $updated = 0;
        foreach (json_decode((string)file_get_contents($dir . 'chapters.json'), true) as $chapter) {
            if (!in_array($chapter['code'], self::UPDATED, true)) {
                continue;
            }
            $id = (int)$helper->Iblock()->getElementId($iblockId, $chapter['code']);
            if ($id) {
                (new \CIBlockElement())->Update($id, ['DETAIL_TEXT' => (string)file_get_contents($dir . $chapter['file']), 'DETAIL_TEXT_TYPE' => 'html']);
                $updated++;
            }
        }
        \CIBlock::clearIblockTagCache($iblockId);
        $this->outSuccess('Гайд: обновлено глав %d', $updated);
    }

    public function down()
    {
        $this->outInfo('Откат текстов глав не выполняется');
    }
}
