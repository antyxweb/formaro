<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20260911193001 extends Version
{
    protected $description = 'formaro.cabinet: инфоблок "Партнёры" (cabinet_partners, тип marketplace)';

    /**
     * Профиль партнёра — новый инфоблок (НЕ тот, что уже отдаёт /partners/
     * на сайте), но того же типа marketplace (тип уже зарегистрирован в
     * структуре сайта, здесь не создаётся). Редактирование в кабинете —
     * это редактирование ровно этого элемента.
     *
     * Нативные поля элемента: NAME=name_full, CODE=slug, PREVIEW_TEXT=
     * short_desc, DETAIL_TEXT=full_desc, PREVIEW_PICTURE=logo,
     * DETAIL_PICTURE=cover, ACTIVE=status(active/hidden).
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $iblockId = $helper->Iblock()->saveIblock([
            'NAME' => 'Партнёры (кабинет)',
            'CODE' => 'cabinet_partners',
            'IBLOCK_TYPE_ID' => 'marketplace',
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
            'NAME' => 'Короткое название',
            'CODE' => 'NAME_SHORT',
            'PROPERTY_TYPE' => 'S',
            'SORT' => 100,
        ]);

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

        $legalProps = [
            'LEGAL_INN' => 'ИНН',
            'LEGAL_OGRN' => 'ОГРН',
            'LEGAL_ADDRESS' => 'Юридический адрес',
            'LEGAL_BANK_NAME' => 'Банк',
            'LEGAL_BIK' => 'БИК',
            'LEGAL_ACCOUNT' => 'Расчётный счёт',
            'LEGAL_CORR_ACCOUNT' => 'Корр. счёт',
            'LEGAL_CEO_NAME' => 'Руководитель',
        ];
        $sort = 200;
        foreach ($legalProps as $code => $name) {
            $helper->Iblock()->saveProperty($iblockId, [
                'NAME' => $name,
                'CODE' => $code,
                'PROPERTY_TYPE' => 'S',
                'SORT' => $sort,
            ]);
            $sort += 10;
        }

        $contactProps = [
            'CONTACT_PHONE' => 'Телефон',
            'CONTACT_EMAIL' => 'Email',
            'CONTACT_PERSON' => 'Контактное лицо',
            'CONTACT_POSITION' => 'Должность контактного лица',
        ];
        $sort = 300;
        foreach ($contactProps as $code => $name) {
            $helper->Iblock()->saveProperty($iblockId, [
                'NAME' => $name,
                'CODE' => $code,
                'PROPERTY_TYPE' => 'S',
                'SORT' => $sort,
            ]);
            $sort += 10;
        }

        $this->outSuccess('Инфоблок "cabinet_partners" готов (ID=%d)', $iblockId);
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();
        $ok = $helper->Iblock()->deleteIblockIfExists('cabinet_partners', 'marketplace');

        if ($ok) {
            $this->outSuccess('Инфоблок "cabinet_partners" удалён');
        } else {
            $this->outError('Инфоблок "cabinet_partners" не найден');
        }
    }
}
