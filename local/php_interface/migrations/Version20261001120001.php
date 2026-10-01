<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261001120001 extends Version
{
    protected $description = 'formaro.cabinet: юридические реквизиты покупателя (UF_BUYER_* у пользователя)';

    /** Поле => подпись. Состав — как «Юридические данные» партнёра плюс название и КПП. */
    private const FIELDS = [
        'UF_BUYER_COMPANY' => 'Покупатель: название организации / ИП',
        'UF_BUYER_INN' => 'Покупатель: ИНН',
        'UF_BUYER_KPP' => 'Покупатель: КПП',
        'UF_BUYER_OGRN' => 'Покупатель: ОГРН / ОГРНИП',
        'UF_BUYER_ADDRESS' => 'Покупатель: юридический адрес',
        'UF_BUYER_BANK' => 'Покупатель: банк',
        'UF_BUYER_BIK' => 'Покупатель: БИК',
        'UF_BUYER_ACCOUNT' => 'Покупатель: расчётный счёт',
        'UF_BUYER_CORR_ACCOUNT' => 'Покупатель: корр. счёт',
        'UF_BUYER_CEO' => 'Покупатель: ФИО руководителя',
    ];

    /**
     * Реквизиты покупателя-юрлица/ИП для раздела «Ваш профиль»
     * (/personal/profile/). У партнёра кабинета вместо них показываются
     * реквизиты его компании из кабинета (BuyerProfileRepository).
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        foreach (self::FIELDS as $code => $label) {
            $helper->UserTypeEntity()->addUserTypeEntityIfNotExists('USER', $code, [
                'USER_TYPE_ID' => 'string',
                'MANDATORY' => 'N',
                'EDIT_FORM_LABEL' => ['ru' => $label],
                'LIST_COLUMN_LABEL' => ['ru' => $label],
            ]);
        }
        $this->outSuccess('Поля UF_BUYER_* у пользователя готовы');
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();
        foreach (array_keys(self::FIELDS) as $code) {
            $helper->UserTypeEntity()->deleteUserTypeEntityIfExists('USER', $code);
        }
        $this->outSuccess('Поля UF_BUYER_* удалены');
    }
}
