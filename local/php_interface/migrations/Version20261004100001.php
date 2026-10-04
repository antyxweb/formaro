<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261004100001 extends Version
{
    protected $description = 'Кабинет администратора /admin/: уведомления администратора, причины отклонения при модерации, шаблон и ЧПУ раздела';

    private const TEMPLATE = 'cabinet_v1';
    private const TEMPLATE_CONDITION = 'strpos($_SERVER["REQUEST_URI"], "/admin/") === 0';
    private const REWRITE_CONDITION = '#^/admin/#';

    /**
     * Кабинет администратора площадки (formaro:cabinet.admin, /admin/):
     *   UF_ADMIN — уведомление для администраторов (CabinetNotifications,
     *              общая лента всех администраторов: модерация, вывод
     *              средств, обращения в техподдержку);
     *   UF_REJECT_REASON — причина отклонения категории партнёра (раздел
     *              cabinet_catalog): не допущена и есть причина — «Отклонена»;
     *   MODERATION_COMMENT — причина отклонения данных партнёра
     *              (cabinet_partners);
     *   шаблон сайта cabinet_v1 для /admin/ и правило ЧПУ.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('CabinetNotifications');
        $label = 'Уведомление администратора';
        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('HLBLOCK_' . $hlblockId, [[
            'FIELD_NAME' => 'UF_ADMIN',
            'USER_TYPE_ID' => 'integer',
            'MANDATORY' => 'N',
            'SORT' => 150,
            'SHOW_FILTER' => 'I',
            'EDIT_FORM_LABEL' => ['ru' => $label],
            'LIST_COLUMN_LABEL' => ['ru' => $label],
            'LIST_FILTER_LABEL' => ['ru' => $label],
        ]]);

        $catalogId = $helper->Iblock()->getIblockIdIfExists('cabinet_catalog', 'catalog');
        $label = 'Причина отклонения (модерация)';
        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('IBLOCK_' . $catalogId . '_SECTION', [[
            'FIELD_NAME' => 'UF_REJECT_REASON',
            'USER_TYPE_ID' => 'string',
            'MANDATORY' => 'N',
            'SORT' => 600,
            'SETTINGS' => ['ROWS' => 3],
            'EDIT_FORM_LABEL' => ['ru' => $label],
            'LIST_COLUMN_LABEL' => ['ru' => $label],
            'LIST_FILTER_LABEL' => ['ru' => $label],
        ]]);

        $partnersId = $helper->Iblock()->getIblockIdIfExists('cabinet_partners', 'marketplace');
        $helper->Iblock()->saveProperty($partnersId, [
            'NAME' => 'Причина отклонения (модерация)',
            'CODE' => 'MODERATION_COMMENT',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 3,
            'SORT' => 115,
        ]);

        $this->addSiteTemplate();
        $this->addUrlRewrite();

        $this->outSuccess('Кабинет администратора: поля, шаблон и ЧПУ готовы');
    }

    /** @throws HelperException */
    public function down()
    {
        $helper = $this->getHelperManager();

        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('CabinetNotifications');
        $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists('HLBLOCK_' . $hlblockId, ['UF_ADMIN']);

        $catalogId = $helper->Iblock()->getIblockIdIfExists('cabinet_catalog', 'catalog');
        $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists('IBLOCK_' . $catalogId . '_SECTION', ['UF_REJECT_REASON']);

        $partnersId = $helper->Iblock()->getIblockIdIfExists('cabinet_partners', 'marketplace');
        $helper->Iblock()->deletePropertyIfExists($partnersId, 'MODERATION_COMMENT');

        \Bitrix\Main\SiteTemplateTable::deleteByFilter(['=TEMPLATE' => self::TEMPLATE, '=CONDITION' => self::TEMPLATE_CONDITION]);
        foreach ($this->siteIds() as $siteId) {
            \CUrlRewriter::Delete(['SITE_ID' => $siteId, 'CONDITION' => self::REWRITE_CONDITION]);
        }

        $this->outSuccess('Кабинет администратора: поля, шаблон и ЧПУ удалены');
    }

    private function addSiteTemplate(): void
    {
        foreach ($this->siteIds() as $siteId) {
            $exists = \Bitrix\Main\SiteTemplateTable::getList([
                'filter' => ['=SITE_ID' => $siteId, '=TEMPLATE' => self::TEMPLATE, '=CONDITION' => self::TEMPLATE_CONDITION],
            ])->fetch();
            if (!$exists) {
                \Bitrix\Main\SiteTemplateTable::add([
                    'SITE_ID' => $siteId,
                    'CONDITION' => self::TEMPLATE_CONDITION,
                    'SORT' => 100,
                    'TEMPLATE' => self::TEMPLATE,
                ]);
            }
        }
    }

    private function addUrlRewrite(): void
    {
        foreach ($this->siteIds() as $siteId) {
            \CUrlRewriter::Add([
                'SITE_ID' => $siteId,
                'CONDITION' => self::REWRITE_CONDITION,
                'RULE' => '',
                'ID' => 'formaro:cabinet.admin',
                'PATH' => '/admin/index.php',
                'SORT' => 100,
            ]);
        }
    }

    private function siteIds(): array
    {
        return array_column(\Bitrix\Main\SiteTable::getList(['select' => ['LID']])->fetchAll(), 'LID');
    }
}
