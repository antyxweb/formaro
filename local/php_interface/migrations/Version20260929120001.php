<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20260929120001 extends Version
{
    protected $description = 'formaro.cabinet: HL-блок SearchHistory (история поиска авторизованных пользователей)';

    /**
     * История поиска по каталогу на главной (компонент formaro:catalog.search)
     * для авторизованных пользователей — привязана к аккаунту, а не к
     * браузеру. У гостей история остаётся в localStorage и при входе
     * переносится сюда (см. SearchHistoryRepository::merge()).
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $hlblockId = $helper->Hlblock()->saveHlblock([
            'NAME' => 'SearchHistory',
            'TABLE_NAME' => 'formaro_search_history',
            'LANG' => ['ru' => ['NAME' => 'История поиска'], 'en' => ['NAME' => 'Search history']],
        ]);

        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('HLBLOCK_' . $hlblockId, [
            ['FIELD_NAME' => 'UF_USER_ID', 'USER_TYPE_ID' => 'integer', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_QUERY', 'USER_TYPE_ID' => 'string', 'MANDATORY' => 'Y'],
            ['FIELD_NAME' => 'UF_SEARCHED_AT', 'USER_TYPE_ID' => 'datetime', 'MANDATORY' => 'Y'],
        ]);

        $this->outSuccess('HL-блок "SearchHistory" готов (ID=%d)', $hlblockId);
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('SearchHistory');

        if ($hlblockId) {
            $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists('HLBLOCK_' . $hlblockId, [
                'UF_USER_ID', 'UF_QUERY', 'UF_SEARCHED_AT',
            ]);
            $helper->Hlblock()->deleteHlblock($hlblockId);
            $this->outSuccess('HL-блок "SearchHistory" удалён');
        } else {
            $this->outError('HL-блок "SearchHistory" не найден');
        }
    }
}
