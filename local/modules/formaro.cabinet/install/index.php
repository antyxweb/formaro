<?php

use Bitrix\Main\Application;
use Bitrix\Main\Loader;

if (class_exists('formaro_cabinet')) {
    return;
}

Loader::registerAutoLoadClasses(null, [
    'formaro_cabinet' => __DIR__ . '/index.php',
]);

class formaro_cabinet extends CModule
{
    public $MODULE_ID = 'formaro.cabinet';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME = 'Formaro';

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = 'Кабинет партнёра Formaro';
        $this->MODULE_DESCRIPTION = 'Публичный раздел /cabinet/ — личный кабинет партнёра маркетплейса '
            . '(каталог, новости, профиль); схема данных заводится миграциями модуля sprint.migration '
            . '(local/php_interface/migrations/), этот install только регистрирует модуль и группу/UF-поле партнёра.';
    }

    /** Вся схема данных (инфоблоки, HL-блоки, UF-поля, группа "Партнёры
     *  кабинета") заводится миграциями sprint.migration
     *  (local/php_interface/migrations/), не отсюда — единое место правды
     *  для схемы, с идемпотентностью и откатом (down()) из коробки. Этот
     *  install только регистрирует модуль в b_module, чтобы заработал
     *  Loader::includeModule('formaro.cabinet') для lib/ и компонента. */
    public function DoInstall()
    {
        global $APPLICATION;

        if (!Loader::includeModule('iblock')) {
            $APPLICATION->ThrowException('Требуется модуль "Информационные блоки" (iblock)');
            return false;
        }

        RegisterModule($this->MODULE_ID);

        $this->registerUrlRewrite();
        $this->registerSiteTemplate();

        return true;
    }

    public function DoUninstall()
    {
        \Bitrix\Main\SiteTemplateTable::deleteByFilter(['=TEMPLATE' => 'cabinet_v1']);

        UnRegisterModule($this->MODULE_ID);

        return true;
    }

    /** SEF-роутинг для /cabinet/ — CUrlRewriter::Add идемпотентен (не дублирует
     *  правило с тем же CONDITION при повторной установке модуля). */
    private function registerUrlRewrite(): void
    {
        foreach (\Bitrix\Main\SiteTable::getList(['select' => ['LID']])->fetchAll() as $site) {
            CUrlRewriter::Add([
                'SITE_ID' => $site['LID'],
                'CONDITION' => '#^/cabinet/#',
                'RULE' => '',
                'ID' => 'formaro:cabinet.partner',
                'PATH' => '/cabinet/index.php',
                'SORT' => 100,
            ]);
        }
    }

    /** Шаблон cabinet_v1 (local/templates/cabinet_v1) назначается по условию
     *  URL, а не программным $APPLICATION->SetTemplateName() из cabinet/
     *  index.php — так надёжнее (решение принято после того, как в реальной
     *  установке SetTemplateName() из index.php не сработал: движок уже
     *  закэшировал шаблон .default к этому моменту исполнения). */
    private function registerSiteTemplate(): void
    {
        foreach (\Bitrix\Main\SiteTable::getList(['select' => ['LID']])->fetchAll() as $site) {
            $exists = \Bitrix\Main\SiteTemplateTable::getList([
                'filter' => ['=SITE_ID' => $site['LID'], '=TEMPLATE' => 'cabinet_v1'],
            ])->fetch();
            if ($exists) {
                continue;
            }

            \Bitrix\Main\SiteTemplateTable::add([
                'SITE_ID' => $site['LID'],
                'CONDITION' => 'strpos($_SERVER["REQUEST_URI"], "/cabinet/") === 0',
                'SORT' => 100,
                'TEMPLATE' => 'cabinet_v1',
            ]);
        }
    }
}
