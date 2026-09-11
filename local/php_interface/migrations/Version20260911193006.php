<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20260911193006 extends Version
{
    protected $description = 'formaro.cabinet: UF_CABINET_PARTNER_ID на пользователе + группа "Партнёры кабинета"';

    /**
     * Замена захардкоженной CURRENT_PARTNER_ID из cabinet-html/assets/js/
     * common.js: партнёр резолвится из текущего пользователя на каждый
     * запрос. UF_CABINET_PARTNER_ID хранит ELEMENT_ID элемента в
     * cabinet_partners (не ссылку на отдельную "учётку" — партнёр может
     * иметь несколько логинов, у каждого своё значение этого поля, все
     * указывают на один и тот же элемент-партнёра). Доступ в /cabinet/
     * даёт членство в группе CABINET_PARTNERS + заполненное поле — оба
     * условия проверяет PartnerContext (lib/Security/PartnerContext.php).
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $helper->UserTypeEntity()->addUserTypeEntityIfNotExists(
            'USER',
            'UF_CABINET_PARTNER_ID',
            [
                'USER_TYPE_ID' => 'integer',
                'MANDATORY' => 'N',
                'EDIT_FORM_LABEL' => ['ru' => 'Партнёр (ID элемента в cabinet_partners)'],
            ]
        );

        $helper->UserGroup()->addGroupIfNotExists('CABINET_PARTNERS', [
            'ACTIVE' => 'Y',
            'NAME' => 'Партнёры кабинета',
            'DESCRIPTION' => 'Пользователи с доступом в личный кабинет партнёра (/cabinet/)',
        ]);

        $this->outSuccess('UF_CABINET_PARTNER_ID и группа "CABINET_PARTNERS" готовы');
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();

        $helper->UserTypeEntity()->deleteUserTypeEntityIfExists('USER', 'UF_CABINET_PARTNER_ID');
        $ok = $helper->UserGroup()->deleteGroup('CABINET_PARTNERS');

        if ($ok) {
            $this->outSuccess('UF-поле и группа удалены');
        } else {
            $this->outError('Группа "CABINET_PARTNERS" не найдена');
        }
    }
}
