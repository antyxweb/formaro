<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20260911193003 extends Version
{
    protected $description = 'formaro.cabinet: инфоблок "Новости" (cabinet_news, тип content) + раздел partners';

    /**
     * Раздел "partners" (код partners) — специально под партнёрские новости,
     * как попросил пользователь; свойство PARTNER_ID на элементе разруливает
     * "чьё это" внутри раздела для фильтра "мои новости" в кабинете.
     *
     * Нативные поля элемента: NAME=title, CODE=slug, PREVIEW_TEXT=short_desc,
     * DETAIL_TEXT=full_desc, PREVIEW_PICTURE=image, ACTIVE=status.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $partnersIblockId = $helper->Iblock()->getIblockIdIfExists('cabinet_partners', 'marketplace');
        if (!$partnersIblockId) {
            throw new HelperException(
                'Инфоблок "cabinet_partners" не найден — сначала должна отработать миграция Version20260911193001'
            );
        }

        $iblockId = $helper->Iblock()->saveIblock([
            'NAME' => 'Новости (кабинет)',
            'CODE' => 'cabinet_news',
            'IBLOCK_TYPE_ID' => 'content',
            'LID' => ['s1'],
            'VERSION' => 2,
            'GROUP_ID' => ['2' => 'R'],
            'LIST_PAGE_URL' => '',
            'DETAIL_PAGE_URL' => '',
            'SORT' => 100,
        ]);

        $helper->Iblock()->saveIblockFields($iblockId, [
            'CODE' => [
                'DEFAULT_VALUE' => [
                    'TRANSLITERATION' => 'Y',
                    'UNIQUE' => 'Y',
                ],
            ],
        ]);

        $helper->Iblock()->saveProperty($iblockId, [
            'NAME' => 'Партнёр', 'CODE' => 'PARTNER_ID', 'PROPERTY_TYPE' => 'E',
            'LINK_IBLOCK_ID' => $partnersIblockId, 'SORT' => 100,
        ]);

        $helper->Iblock()->saveSectionByCode($iblockId, [
            'CODE' => 'partners',
            'NAME' => 'Партнёры',
            'SORT' => 500,
        ]);

        $this->outSuccess('Инфоблок "cabinet_news" готов (ID=%d)', $iblockId);
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();
        $ok = $helper->Iblock()->deleteIblockIfExists('cabinet_news', 'content');

        if ($ok) {
            $this->outSuccess('Инфоблок "cabinet_news" удалён');
        } else {
            $this->outError('Инфоблок "cabinet_news" не найден');
        }
    }
}
