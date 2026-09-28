<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20260929130001 extends Version
{
    protected $description = 'formaro.cabinet: HL-блок Favorites (избранные товары авторизованных пользователей)';

    /**
     * Избранное на витрине (сердечко .favorite в карточке товара) для
     * авторизованных пользователей. У гостя избранное в localStorage и при
     * входе переносится сюда (см. FavoriteRepository::merge(), favorites.js).
     * UF_PRODUCT_ID — ID элемента инфоблока cabinet_catalog.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $hlblockId = $helper->Hlblock()->saveHlblock([
            'NAME' => 'Favorites',
            'TABLE_NAME' => 'formaro_favorites',
            'LANG' => ['ru' => ['NAME' => 'Избранное'], 'en' => ['NAME' => 'Favorites']],
        ]);

        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('HLBLOCK_' . $hlblockId, [
            ['FIELD_NAME' => 'UF_USER_ID', 'USER_TYPE_ID' => 'integer', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_PRODUCT_ID', 'USER_TYPE_ID' => 'integer', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_ADDED_AT', 'USER_TYPE_ID' => 'datetime', 'MANDATORY' => 'Y'],
        ]);

        $this->outSuccess('HL-блок "Favorites" готов (ID=%d)', $hlblockId);
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('Favorites');

        if ($hlblockId) {
            $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists('HLBLOCK_' . $hlblockId, [
                'UF_USER_ID', 'UF_PRODUCT_ID', 'UF_ADDED_AT',
            ]);
            $helper->Hlblock()->deleteHlblock($hlblockId);
            $this->outSuccess('HL-блок "Favorites" удалён');
        } else {
            $this->outError('HL-блок "Favorites" не найден');
        }
    }
}
