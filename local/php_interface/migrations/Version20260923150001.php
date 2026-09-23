<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20260923150001 extends Version
{
    protected $description = 'formaro.cabinet: UF_APPROVED ("Допущен к показу") на разделах cabinet_catalog — '
        . 'модерация категорий отдельным полем вместо принудительного ACTIVE=N при создании';

    /**
     * Заменяет прежний подход (сервер форсировал ACTIVE=N для новых партнёрских
     * категорий, а ACTIVE заодно служило и "статусом показа", и "флагом
     * модерации"). Теперь ACTIVE — снова только собственный выбор партнёра
     * (Активен/Скрыт), а одобрение администратором площадки — отдельное
     * boolean-поле раздела, снятое по умолчанию. Поле реальное (не JSON/UF
     * поверх кабинета), поэтому редактируется прямо в стандартной админке
     * Битрикс на форме раздела — отдельный UI в кабинете не нужен.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $iblockId = $helper->Iblock()->getIblockIdIfExists('cabinet_catalog', 'catalog');
        if (!$iblockId) {
            throw new HelperException(
                'Инфоблок "cabinet_catalog" не найден — сначала должна отработать миграция Version20260911193002'
            );
        }

        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists(
            'IBLOCK_' . $iblockId . '_SECTION',
            [
                [
                    'FIELD_NAME' => 'UF_APPROVED',
                    'USER_TYPE_ID' => 'boolean',
                    'EDIT_FORM_LABEL' => ['ru' => 'Допущен к показу'],
                    'SETTINGS' => ['DEFAULT_VALUE' => 0],
                ],
            ]
        );

        // Бэкфилл: все разделы, существовавшие ДО появления поля (системные и
        // уже одобренные ранее партнёрские), считаем одобренными — иначе они
        // все разом "потеряют" пройденную проверку и начнут показывать
        // "На проверке" в кабинете, хотя реально уже давно на сайте.
        $count = 0;
        $res = \CIBlockSection::GetList([], ['IBLOCK_ID' => $iblockId, 'CHECK_PERMISSIONS' => 'N'], false, ['ID']);
        while ($row = $res->Fetch()) {
            (new \CIBlockSection())->Update($row['ID'], ['UF_APPROVED' => 1]);
            $count++;
        }

        $this->outSuccess('UF_APPROVED добавлено на IBLOCK_%d_SECTION, помечено одобренными разделов: %d', $iblockId, $count);
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();

        $iblockId = $helper->Iblock()->getIblockIdIfExists('cabinet_catalog', 'catalog');
        if ($iblockId) {
            $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists('IBLOCK_' . $iblockId . '_SECTION', ['UF_APPROVED']);
            $this->outSuccess('UF_APPROVED удалено');
        } else {
            $this->outError('Инфоблок "cabinet_catalog" не найден');
        }
    }
}
