<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20260922190001 extends Version
{
    protected $description = 'formaro.cabinet: добавить статус "Не проверен" в свойство VERIFICATION_STATUS партнёра';

    private const IBLOCK_CODE = 'cabinet_partners';
    private const IBLOCK_TYPE = 'marketplace';

    /**
     * saveProperty() с VALUES полностью синхронизирует список значений
     * list-свойства (сопоставляя по XML_ID), поэтому здесь перечислены ВСЕ
     * значения, включая уже существующие из Version20260911193001 — иначе
     * их бы удалило при обновлении.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists(self::IBLOCK_CODE, self::IBLOCK_TYPE);

        $helper->Iblock()->saveProperty($iblockId, [
            'NAME' => 'Статус верификации',
            'CODE' => 'VERIFICATION_STATUS',
            'PROPERTY_TYPE' => 'L',
            'SORT' => 110,
            'VALUES' => [
                ['VALUE' => 'Не проверен', 'XML_ID' => 'not_verified', 'DEF' => 'Y'],
                ['VALUE' => 'На проверке', 'XML_ID' => 'pending'],
                ['VALUE' => 'Проверен', 'XML_ID' => 'verified'],
                ['VALUE' => 'Отклонён', 'XML_ID' => 'rejected'],
            ],
        ]);
    }

    public function down()
    {
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists(self::IBLOCK_CODE, self::IBLOCK_TYPE);

        $helper->Iblock()->saveProperty($iblockId, [
            'NAME' => 'Статус верификации',
            'CODE' => 'VERIFICATION_STATUS',
            'PROPERTY_TYPE' => 'L',
            'SORT' => 110,
            'VALUES' => [
                ['VALUE' => 'На проверке', 'XML_ID' => 'pending', 'DEF' => 'Y'],
                ['VALUE' => 'Проверен', 'XML_ID' => 'verified'],
                ['VALUE' => 'Отклонён', 'XML_ID' => 'rejected'],
            ],
        ]);
    }
}
