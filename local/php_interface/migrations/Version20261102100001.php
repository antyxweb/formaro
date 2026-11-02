<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261102100001 extends Version
{
    protected $description = 'Контакты: WhatsApp и Telegram заменены на мессенджер MAX (правки клиента 19.10)';

    private const MAX_URL = 'https://max.ru/';

    /** Иконка — CODE элемента (id SVG-символа из footer.php). Белая иконка — для плиток «Поддержки». */
    private const REPLACE = [
        'icon-whatsapp' => 'icon-max',
        'icon-tg-white' => 'icon-max-white',
    ];

    /** @throws HelperException */
    public function up()
    {
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists('List', 'content');
        $el = new \CIBlockElement();
        $updated = 0;
        $rs = \CIBlockElement::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => array_keys(self::REPLACE)], false, false, ['ID', 'NAME', 'CODE']);
        while ($item = $rs->Fetch()) {
            // «Напишите нам в WhatsApp» → «Напишите нам в MAX», «Whatsapp»/«Telegram» → «MAX»
            $name = preg_replace('/whats\s?app|telegram/iu', 'MAX', $item['NAME']);
            $el->Update($item['ID'], ['NAME' => $name, 'CODE' => self::REPLACE[$item['CODE']]]);
            \CIBlockElement::SetPropertyValuesEx($item['ID'], $iblockId, ['LINK' => self::MAX_URL]);
            $updated++;
        }
        \CIBlock::clearIblockTagCache($iblockId);
        $this->outSuccess('Контакты: заменено на MAX %d', $updated);
    }

    public function down()
    {
        $this->outInfo('Откат замены мессенджеров не выполняется');
    }
}
