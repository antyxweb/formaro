<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261013200001 extends Version
{
    protected $description = 'Гайд по сайту: подписки и рассылки — письма подписчикам, отписка и управление рассылками';

    private const UPDATED = ['buyer-cabinet', 'buyer-notify-chat', 'partners-public', 'cabinet-marketing', 'cabinet-communication', 'content', 'owner-tasks'];

    /** Тексты глав UPDATED — из data/guide (как Version20261008110001). @throws HelperException */
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
