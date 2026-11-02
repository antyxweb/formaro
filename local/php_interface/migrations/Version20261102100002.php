<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261102100002 extends Version
{
    protected $description = 'Контакты: ссылка на канал Reppa в MAX вместо заглушки https://max.ru/';

    private const MAX_URL = 'https://max.ru/u/f9LHodD0cOLbeVqhBDicFxb54Fs1Gfwv5CrzosBzXHJju7KjKKuOy8PnNMI';

    /** @throws HelperException */
    public function up()
    {
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists('List', 'content');
        $updated = 0;
        $rs = \CIBlockElement::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => ['icon-max', 'icon-max-white']], false, false, ['ID']);
        while ($item = $rs->Fetch()) {
            \CIBlockElement::SetPropertyValuesEx($item['ID'], $iblockId, ['LINK' => self::MAX_URL]);
            $updated++;
        }
        \CIBlock::clearIblockTagCache($iblockId);
        $this->outSuccess('Контакты: ссылка MAX обновлена у %d', $updated);
    }

    public function down()
    {
        $this->outInfo('Откат ссылки MAX не выполняется');
    }
}
