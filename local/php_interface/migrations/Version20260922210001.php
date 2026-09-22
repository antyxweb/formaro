<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20260922210001 extends Version
{
    protected $description = 'formaro.cabinet: свойство PENDING_CHANGES для отложенных правок партнёра';

    private const IBLOCK_CODE = 'cabinet_partners';
    private const IBLOCK_TYPE = 'marketplace';

    /**
     * Черновик правок вкладки "Основное", отправленных партнёром, пока
     * статус верификации уже был "Проверен" — JSON, тот же компромисс, что
     * и CUSTOM_PROPS_JSON у товаров (см. Version20260911193002). Переносится
     * в боевые поля/свойства при следующем чтении партнёра после того, как
     * статус снова станет "Проверен" (см. PartnerRepository::get()).
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists(self::IBLOCK_CODE, self::IBLOCK_TYPE);

        $helper->Iblock()->saveProperty($iblockId, [
            'NAME' => 'Отложенные правки (JSON)',
            'CODE' => 'PENDING_CHANGES',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 6,
            'SORT' => 900,
        ]);
    }

    public function down()
    {
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists(self::IBLOCK_CODE, self::IBLOCK_TYPE);

        $helper->Iblock()->deletePropertyIfExists($iblockId, 'PENDING_CHANGES');
    }
}
