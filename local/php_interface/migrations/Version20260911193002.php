<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20260911193002 extends Version
{
    protected $description = 'formaro.cabinet: инфоблок "Каталог" (cabinet_catalog, тип catalog) — категории и товары';

    /**
     * Категории = разделы, товары = элементы одного инфоблока. Партнёрская
     * метка — на элементах свойством PARTNER_ID (привязка к элементу
     * инфоблока "Партнёры"), на разделах — UF-полями UF_PARTNER_ID/
     * UF_IS_SYSTEM (у разделов нет своих custom-свойств "из коробки", поэтому
     * тут именно UF, а не CIBlockProperty). Общие системные категории — без
     * UF_PARTNER_ID, партнёрские — с ним; см. cabinet-html/CLAUDE.md,
     * раздел "Мультитенантность".
     *
     * Нативные поля элемента (товара): NAME=name, CODE=slug, PREVIEW_TEXT=
     * short_desc, DETAIL_TEXT=full_desc, PREVIEW_PICTURE=preview_image,
     * ACTIVE=status. Раздела (категории): NAME=name, CODE=slug,
     * DESCRIPTION=short_desc, PICTURE=preview_image, ACTIVE=status
     * (полное описание категории и второе изображение — тоже через UF,
     * у разделов нет второго нативного текстового поля).
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
            'NAME' => 'Каталог (кабинет)',
            'CODE' => 'cabinet_catalog',
            'IBLOCK_TYPE_ID' => 'catalog',
            'LID' => ['s1'],
            'VERSION' => 2,
            'GROUP_ID' => ['2' => 'R'],
            'LIST_PAGE_URL' => '',
            'DETAIL_PAGE_URL' => '',
            'SECTION_PAGE_URL' => '',
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

        // ---- свойства товара (элемента) ----
        $helper->Iblock()->saveProperty($iblockId, [
            'NAME' => 'Артикул', 'CODE' => 'SKU', 'PROPERTY_TYPE' => 'S', 'SORT' => 100,
        ]);
        $helper->Iblock()->saveProperty($iblockId, [
            'NAME' => 'Цвет', 'CODE' => 'COLOR', 'PROPERTY_TYPE' => 'S', 'SORT' => 110,
        ]);
        $helper->Iblock()->saveProperty($iblockId, [
            'NAME' => 'Размер', 'CODE' => 'SIZE', 'PROPERTY_TYPE' => 'S', 'SORT' => 120,
        ]);
        $helper->Iblock()->saveProperty($iblockId, [
            'NAME' => 'Цена', 'CODE' => 'PRICE', 'PROPERTY_TYPE' => 'N', 'SORT' => 130,
        ]);
        $helper->Iblock()->saveProperty($iblockId, [
            'NAME' => 'Остаток', 'CODE' => 'STOCK', 'PROPERTY_TYPE' => 'N', 'SORT' => 140,
        ]);
        $helper->Iblock()->saveProperty($iblockId, [
            'NAME' => 'Галерея', 'CODE' => 'GALLERY', 'PROPERTY_TYPE' => 'F',
            'MULTIPLE' => 'Y', 'SORT' => 150,
        ]);
        $helper->Iblock()->saveProperty($iblockId, [
            // [{"name":"...","value":"..."}] — как в прототипе (custom_props),
            // произвольные пары "название/значение" от партнёра, без
            // фиксированной схемы. JSON в одном текстовом свойстве — тот же
            // компромисс, что и для order.items/order.history в HL-блоках.
            'NAME' => 'Доп. свойства (JSON)', 'CODE' => 'CUSTOM_PROPS_JSON',
            'PROPERTY_TYPE' => 'S', 'ROW_COUNT' => 4, 'SORT' => 160,
        ]);
        $helper->Iblock()->saveProperty($iblockId, [
            'NAME' => 'Предзаказ', 'CODE' => 'IS_PREORDER', 'PROPERTY_TYPE' => 'S', 'SORT' => 170,
        ]);
        $helper->Iblock()->saveProperty($iblockId, [
            'NAME' => 'Метки', 'CODE' => 'TAGS', 'PROPERTY_TYPE' => 'S',
            'MULTIPLE' => 'Y', 'SORT' => 180,
        ]);
        $helper->Iblock()->saveProperty($iblockId, [
            'NAME' => 'Группа вариантов', 'CODE' => 'VARIANT_GROUP_ID', 'PROPERTY_TYPE' => 'N', 'SORT' => 190,
        ]);
        $helper->Iblock()->saveProperty($iblockId, [
            'NAME' => 'Партнёр', 'CODE' => 'PARTNER_ID', 'PROPERTY_TYPE' => 'E',
            'LINK_IBLOCK_ID' => $partnersIblockId, 'SORT' => 200,
        ]);

        // ---- UF-поля раздела (категории) ----
        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists(
            'IBLOCK_' . $iblockId . '_SECTION',
            [
                [
                    'FIELD_NAME' => 'UF_PARTNER_ID',
                    'USER_TYPE_ID' => 'integer',
                    'EDIT_FORM_LABEL' => ['ru' => 'Партнёр (ID элемента в cabinet_partners)'],
                ],
                [
                    'FIELD_NAME' => 'UF_IS_SYSTEM',
                    'USER_TYPE_ID' => 'boolean',
                    'EDIT_FORM_LABEL' => ['ru' => 'Системная категория (общая для всех)'],
                    'SETTINGS' => ['DEFAULT_VALUE' => 0],
                ],
                [
                    'FIELD_NAME' => 'UF_FULL_DESC',
                    'USER_TYPE_ID' => 'string',
                    'EDIT_FORM_LABEL' => ['ru' => 'Полное описание'],
                    'SETTINGS' => ['SIZE' => 1, 'ROWS' => 10],
                ],
                [
                    'FIELD_NAME' => 'UF_FULL_IMAGE',
                    'USER_TYPE_ID' => 'file',
                    'EDIT_FORM_LABEL' => ['ru' => 'Полное изображение'],
                ],
            ]
        );

        $this->outSuccess('Инфоблок "cabinet_catalog" готов (ID=%d)', $iblockId);
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();

        $iblockId = $helper->Iblock()->getIblockIdIfExists('cabinet_catalog', 'catalog');
        if ($iblockId) {
            $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists(
                'IBLOCK_' . $iblockId . '_SECTION',
                ['UF_PARTNER_ID', 'UF_IS_SYSTEM', 'UF_FULL_DESC', 'UF_FULL_IMAGE']
            );
        }

        $ok = $helper->Iblock()->deleteIblockIfExists('cabinet_catalog', 'catalog');

        if ($ok) {
            $this->outSuccess('Инфоблок "cabinet_catalog" удалён');
        } else {
            $this->outError('Инфоблок "cabinet_catalog" не найден');
        }
    }
}
