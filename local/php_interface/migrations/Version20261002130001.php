<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261002130001 extends Version
{
    protected $description = 'Блок «Связь с поддержкой» переименован в «Мы всегда поможем»';

    /**
     * Заголовок блока над подвалом — название раздела support-links
     * инфоблока list (шаблон news.list/support-links, первое слово —
     * акцентом).
     *
     * @throws HelperException
     */
    public function up()
    {
        $this->rename('Мы всегда поможем');
    }

    /** @throws HelperException */
    public function down()
    {
        $this->rename('Связь с поддержкой');
    }

    /** @throws HelperException */
    private function rename(string $name): void
    {
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists('list', 'content');
        $sectionId = $helper->Iblock()->getSectionIdIfExists($iblockId, 'support-links');
        $helper->Iblock()->updateSection($sectionId, ['NAME' => $name]);
        \CIBlock::clearIblockTagCache($iblockId);
        $this->outSuccess('Раздел support-links: «%s»', $name);
    }
}
