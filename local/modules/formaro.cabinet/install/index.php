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

        // urlrewrite.php на сервере не входит в репозиторий (ядро деплоится
        // отдельно) — программно дописывать его отсюда небезопасно без
        // проверки актуальной сигнатуры CUrlRewriter::Add на боевом ядре.
        // Правило нужно добавить один раз вручную — либо через административную
        // панель (Настройки → Инструменты → Настройки продукта → Маршрутизация
        // (ЧПУ) → добавить правило для сайта), либо вписать в bitrix/urlrewrite.php:
        //
        // array(
        //     "CONDITION" => "#^/cabinet/#",
        //     "RULE"      => "",
        //     "ID"        => "formaro:cabinet.partner",
        //     "PATH"      => "/cabinet/index.php",
        //     "SORT"      => 100,
        // ),

        return true;
    }

    public function DoUninstall()
    {
        UnRegisterModule($this->MODULE_ID);

        return true;
    }
}
