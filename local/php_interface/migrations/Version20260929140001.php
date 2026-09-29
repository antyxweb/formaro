<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20260929140001 extends Version
{
    protected $description = 'formaro.cabinet: cabinet_news — привязка новости к категориям и товарам каталога';

    /**
     * Вкладка «Каталог» в форме новости кабинета партнёра: новость можно
     * привязать к категориям каталога и/или к товарам (cabinet_catalog).
     * CATALOG_SECTIONS — привязка к разделам (G), CATALOG_PRODUCTS — к
     * элементам (E), обе множественные.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $newsIblockId = $helper->Iblock()->getIblockIdIfExists('cabinet_news', 'content');
        $catalogIblockId = $helper->Iblock()->getIblockIdIfExists('cabinet_catalog', 'catalog');
        if (!$newsIblockId || !$catalogIblockId) {
            throw new HelperException('Инфоблок cabinet_news или cabinet_catalog не найден');
        }

        $helper->Iblock()->saveProperty($newsIblockId, [
            'NAME' => 'Категории каталога', 'CODE' => 'CATALOG_SECTIONS', 'PROPERTY_TYPE' => 'G',
            'LINK_IBLOCK_ID' => $catalogIblockId, 'MULTIPLE' => 'Y', 'SORT' => 200,
        ]);
        $helper->Iblock()->saveProperty($newsIblockId, [
            'NAME' => 'Товары', 'CODE' => 'CATALOG_PRODUCTS', 'PROPERTY_TYPE' => 'E',
            'LINK_IBLOCK_ID' => $catalogIblockId, 'MULTIPLE' => 'Y', 'SORT' => 210,
        ]);

        $this->outSuccess('Свойства CATALOG_SECTIONS/CATALOG_PRODUCTS добавлены в cabinet_news (ID=%d)', $newsIblockId);
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();
        $newsIblockId = $helper->Iblock()->getIblockIdIfExists('cabinet_news', 'content');

        if ($newsIblockId) {
            $helper->Iblock()->deletePropertyIfExists($newsIblockId, 'CATALOG_SECTIONS');
            $helper->Iblock()->deletePropertyIfExists($newsIblockId, 'CATALOG_PRODUCTS');
            $this->outSuccess('Свойства CATALOG_SECTIONS/CATALOG_PRODUCTS удалены');
        } else {
            $this->outError('Инфоблок "cabinet_news" не найден');
        }
    }
}
