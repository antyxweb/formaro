<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20260924100001 extends Version
{
    protected $description = 'formaro.cabinet: свойство FULL_IMAGE (детальная картинка) на элементах cabinet_news';

    /**
     * У новости было только одно изображение (PREVIEW_PICTURE, нативное
     * поле). У категорий/товаров превью и детальная картинка — два разных
     * значения (PICTURE/UF_FULL_IMAGE у категории), у новости так и было
     * задумано в прототипе (cabinet-html/news-detail.html), но при переносе
     * на реальный инфоблок (Version20260911193003) второе поле не завели.
     * Элементы (в отличие от разделов) поддерживают обычные свойства
     * "из коробки" — берём File-свойство, как GALLERY у товара, а не UF,
     * как UF_FULL_IMAGE у категории (там UF был вынужденной мерой именно
     * для разделов).
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $iblockId = $helper->Iblock()->getIblockIdIfExists('cabinet_news', 'content');
        if (!$iblockId) {
            throw new HelperException(
                'Инфоблок "cabinet_news" не найден — сначала должна отработать миграция Version20260911193003'
            );
        }

        $helper->Iblock()->saveProperty($iblockId, [
            'NAME' => 'Детальная картинка', 'CODE' => 'FULL_IMAGE', 'PROPERTY_TYPE' => 'F', 'SORT' => 200,
        ]);

        $this->outSuccess('Свойство FULL_IMAGE добавлено в cabinet_news (ID=%d)', $iblockId);
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();

        $iblockId = $helper->Iblock()->getIblockIdIfExists('cabinet_news', 'content');
        if (!$iblockId) {
            $this->outError('Инфоблок "cabinet_news" не найден');
            return;
        }

        $helper->Iblock()->deletePropertyIfExists($iblockId, 'FULL_IMAGE');
        $this->outSuccess('Свойство FULL_IMAGE удалено');
    }
}
