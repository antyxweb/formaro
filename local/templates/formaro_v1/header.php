<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();
?>
<?php
global $USER;
global $APPLICATION;

$rsSites = CSite::GetByID(SITE_ID);
$arSite = $rsSites->Fetch();

$showTitleBlock = true;

if($GLOBALS["APPLICATION"]->GetCurPage(false) == '/'
    || $APPLICATION->GetDirProperty("hideTitleBlock")
    ) {
    $showTitleBlock = false;
}
?>
<!DOCTYPE html>
<html lang="<?=LANGUAGE_ID?>" class="h-100">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <?$APPLICATION->ShowHead();?>

    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="shortcut icon" href="/favicon.ico?v1" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
    <link rel="manifest" href="/site.webmanifest" />

    <link rel="stylesheet" href="<?=SITE_TEMPLATE_PATH;?>/css/styles.min.css?v<?=time()?>" />

    <title><?$APPLICATION->ShowTitle();?></title>
</head>
<body class="d-flex flex-column">
    <div id="panel">
        <?$APPLICATION->ShowPanel();?>
    </div>
    <header id="header" class="_main-page _main-page-active _partner-page _overtop-page">
        <div class="header d-flex">
            <div class="logo">
                <div class="f-letter show-bg-menu">
                    <div class="line-top"></div>
                    <div class="line-middle"></div>
                    <div class="line-bottom"></div>
                </div>
                <a href="/" class="name" id="logo">
                    <span>f</span>
                    <span>o</span>
                    <span>r</span>
                    <span>m</span>
                    <span>a</span>
                    <span>r</span>
                    <span>o</span>
                </a>
            </div>

            <?$APPLICATION->IncludeComponent(
                "bitrix:menu",
                "top",
                array(
                    "COMPONENT_TEMPLATE" => "left",
                    "ROOT_MENU_TYPE" => "top",
                    "MENU_CACHE_TYPE" => "N",
                    "MENU_CACHE_TIME" => "3600",
                    "MENU_CACHE_USE_GROUPS" => "Y",
                    "MENU_CACHE_GET_VARS" => array(
                    ),
                    "MAX_LEVEL" => "1",
                    "CHILD_MENU_TYPE" => "left",
                    "USE_EXT" => "Y",
                    "DELAY" => "Y",
                    "ALLOW_MULTI_SELECT" => "N"
                ),
                false
            );?>

            <div class="header__actions d-none d-sm-block d-xl-none mr-auto">
                <button class="header__action-link bg-menu-open pl-0 d-xl-none" type="button" aria-label="Меню" data-search-menu-toggle="">
                    <svg class="header__action-icon" width="20" height="20">
                        <use xlink:href="#icon-burger"></use>
                    </svg>
                    <svg class="header__action-icon" width="20" height="20">
                        <use xlink:href="#icon-close"></use>
                    </svg>
                    <span class="d-none d-md-inline">Меню</span>
                </button>
            </div>

            <div class="header__search">
                <div class="header__search-input">
                    <input id="header-search" class="search-input" type="text" placeholder="Поиск по каталогу">
                    <div class="search-result-block">
                        <div class="search-result-block-wrap p-3">
                            <div class="search-result-block-wrap-ajax d-none">
                                <ul class="mb-0">
                                    <li>
                                        <svg width="16" height="16">
                                            <use xlink:href="#icon-search"></use>
                                        </svg>
                                        <a href="#"><span></span> красный</a>
                                    </li>
                                    <li>
                                        <svg width="16" height="16">
                                            <use xlink:href="#icon-search"></use>
                                        </svg>
                                        <a href="#"><span></span> большой</a>
                                    </li>
                                    <li>
                                        <svg width="16" height="16">
                                            <use xlink:href="#icon-search"></use>
                                        </svg>
                                        <a href="#"><span></span> с подкладом</a>
                                    </li>
                                    <li>
                                        <svg width="16" height="16">
                                            <use xlink:href="#icon-search"></use>
                                        </svg>
                                        <a href="#"><span></span> и много с чем еще</a>
                                    </li>
                                </ul>
                            </div>

                            <div class="search-result-block-wrap-offer">
                                <h6 class="h6 text-secondary">История поиска</h6>
                                <ul>
                                    <li>
                                        <svg width="16" height="16">
                                            <use xlink:href="#icon-search"></use>
                                        </svg>
                                        <a href="#">Жилет утепленный Фаворит</a>
                                    </li>
                                    <li>
                                        <svg width="16" height="16">
                                            <use xlink:href="#icon-search"></use>
                                        </svg>
                                        <a href="#">Панорамная маска</a>
                                    </li>
                                    <li>
                                        <svg width="16" height="16">
                                            <use xlink:href="#icon-search"></use>
                                        </svg>
                                        <a href="#">Каскетка-бейсболка</a>
                                    </li>
                                    <li>
                                        <svg width="16" height="16">
                                            <use xlink:href="#icon-search"></use>
                                        </svg>
                                        <a href="#">Сапоги войлочные</a>
                                    </li>
                                </ul>

                                <h6 class="h6 text-secondary">Часто ищут</h6>
                                <ul class="mb-0">
                                    <li>
                                        <svg width="16" height="16">
                                            <use xlink:href="#icon-search"></use>
                                        </svg>
                                        <a href="#">Жилет утепленный Фаворит</a>
                                    </li>
                                    <li>
                                        <svg width="16" height="16">
                                            <use xlink:href="#icon-search"></use>
                                        </svg>
                                        <a href="#">Панорамная маска</a>
                                    </li>
                                    <li>
                                        <svg width="16" height="16">
                                            <use xlink:href="#icon-search"></use>
                                        </svg>
                                        <a href="#">Каскетка-бейсболка</a>
                                    </li>
                                    <li>
                                        <svg width="16" height="16">
                                            <use xlink:href="#icon-search"></use>
                                        </svg>
                                        <a href="#">Сапоги войлочные</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="search-result-bg"></div>
                    <button class="header-search-icon" type="submit">
                        <svg width="20" height="20">
                            <use xlink:href="#icon-search"></use>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="header__actions ml-auto">
                <button class="header__action-link header__action-link-search d-lg-none" type="button" aria-label="Поиск" data-search-menu-toggle="">
                    <svg class="header__action-icon" width="20" height="20">
                        <use xlink:href="#icon-search"></use>
                    </svg>
                </button>

                <a class="header__action-link d-none d-sm-flex" href="/personal/orders/" aria-label="Заказы">
                    <svg class="header__action-icon" width="26" height="28" viewBox="0 0 26 28">
                        <use xlink:href="#icon-order"></use>
                    </svg>
                    <small class="header__action-count">0</small>
                    <span class="d-none d-xl-inline">Заказы</span>
                </a>
                <a class="header__action-link d-none d-sm-flex" href="/personal/favorites/" aria-label="Избранное">
                    <svg class="header__action-icon" width="20" height="20">
                        <use xlink:href="#icon-favorites"></use>
                    </svg>
                    <small class="header__action-count">0</small>
                    <span class="d-none d-xl-inline">Избранное</span>
                </a>
                <a class="header__action-link" href="/personal/cart/" aria-label="Корзина">
                    <svg class="header__action-icon" width="20" height="20">
                        <use xlink:href="#icon-shopping"></use>
                    </svg>
                    <small class="header__action-count">0</small>
                    <span class="d-none d-xl-inline">Корзина</span>
                </a>

                <?if($USER->IsAuthorized()):?>
                    <a class="header__action-link d-none d-sm-flex" href="/personal/profile/" aria-label="Профиль">
                        <svg class="header__action-icon" width="20" height="20">
                            <use xlink:href="#icon-profile"></use>
                        </svg>
                        <span class="d-none d-sm-inline"><?=$USER->GetFirstName()?></span>
                    </a>
                <?else:?>
                    <a class="header__action-link d-none d-sm-flex" href="/login/" aria-label="Профиль">
                        <svg class="header__action-icon" width="20" height="20">
                            <use xlink:href="#icon-profile"></use>
                        </svg>
                        <span class="d-none d-sm-inline">Войти</span>
                    </a>
                <?endif;?>

                <button class="header__action-link bg-menu-open d-sm-none" type="button" aria-label="Меню" data-search-menu-toggle="">
                    <svg class="header__action-icon" width="20" height="20">
                        <use xlink:href="#icon-burger"></use>
                    </svg>
                    <svg class="header__action-icon" width="20" height="20">
                        <use xlink:href="#icon-close"></use>
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <div id="bg-menu">
        <div class="bg-menu-background">
            <div class="bg-menu-background-inner">
                <div class="container-fluid py-4 py-md-5">
                    <div class="row full-menu pb-5">
                        <div class="col-12 col-xl-4">
                            <div class="row">
                                <?$APPLICATION->IncludeComponent(
                                    "bitrix:menu",
                                    "catalog",
                                    array(
                                        "COMPONENT_TEMPLATE" => "left",
                                        "ROOT_MENU_TYPE" => "catalog",
                                        "MENU_CACHE_TYPE" => "N",
                                        "MENU_CACHE_TIME" => "3600",
                                        "MENU_CACHE_USE_GROUPS" => "Y",
                                        "MENU_CACHE_GET_VARS" => array(
                                        ),
                                        "MAX_LEVEL" => "2",
                                        "CHILD_MENU_TYPE" => "left",
                                        "USE_EXT" => "Y",
                                        "DELAY" => "Y",
                                        "ALLOW_MULTI_SELECT" => "N"
                                    ),
                                    false
                                );?>
                            </div>
                        </div>

                        <div class="col-12 d-xl-none my-4">
                            <div class="border-bottom border-white mb-4" style="opacity: 0.3"></div>
                        </div>

                        <div class="col-12 col-xl-4">
                            <div class="row">
                                <?$APPLICATION->IncludeComponent(
                                    "bitrix:menu",
                                    "content",
                                    array(
                                        "COMPONENT_TEMPLATE" => "left",
                                        "ROOT_MENU_TYPE" => "content",
                                        "MENU_CACHE_TYPE" => "N",
                                        "MENU_CACHE_TIME" => "3600",
                                        "MENU_CACHE_USE_GROUPS" => "Y",
                                        "MENU_CACHE_GET_VARS" => array(
                                        ),
                                        "MAX_LEVEL" => "2",
                                        "CHILD_MENU_TYPE" => "left",
                                        "USE_EXT" => "Y",
                                        "DELAY" => "Y",
                                        "ALLOW_MULTI_SELECT" => "N"
                                    ),
                                    false
                                );?>
                            </div>
                        </div>

                        <div class="col-12 d-xl-none my-4">
                            <div class="border-bottom border-white mb-4" style="opacity: 0.3"></div>
                        </div>

                        <div class="col-12 col-xl-4">
                            <div class="row h-100">
                                <?$APPLICATION->IncludeComponent(
                                    "bitrix:menu",
                                    "personal",
                                    array(
                                        "COMPONENT_TEMPLATE" => "left",
                                        "ROOT_MENU_TYPE" => "personal",
                                        "MENU_CACHE_TYPE" => "N",
                                        "MENU_CACHE_TIME" => "3600",
                                        "MENU_CACHE_USE_GROUPS" => "Y",
                                        "MENU_CACHE_GET_VARS" => array(
                                        ),
                                        "MAX_LEVEL" => "2",
                                        "CHILD_MENU_TYPE" => "left",
                                        "USE_EXT" => "Y",
                                        "DELAY" => "Y",
                                        "ALLOW_MULTI_SELECT" => "N"
                                    ),
                                    false
                                );?>

                                <?$APPLICATION->IncludeComponent("bitrix:news.list", "main-contacts", Array(
                                    "ACTIVE_DATE_FORMAT" => "d F Y",	// Формат показа даты
                                    "ADD_SECTIONS_CHAIN" => "N",	// Включать раздел в цепочку навигации
                                    "AJAX_MODE" => "N",	// Включить режим AJAX
                                    "AJAX_OPTION_ADDITIONAL" => "",	// Дополнительный идентификатор
                                    "AJAX_OPTION_HISTORY" => "N",	// Включить эмуляцию навигации браузера
                                    "AJAX_OPTION_JUMP" => "N",	// Включить прокрутку к началу компонента
                                    "AJAX_OPTION_STYLE" => "N",	// Включить подгрузку стилей
                                    "CACHE_FILTER" => "N",	// Кешировать при установленном фильтре
                                    "CACHE_GROUPS" => "Y",	// Учитывать права доступа
                                    "CACHE_TIME" => "36000000",	// Время кеширования (сек.)
                                    "CACHE_TYPE" => "A",	// Тип кеширования
                                    "CHECK_DATES" => "Y",	// Показывать только активные на данный момент элементы
                                    "COMPONENT_TEMPLATE" => ".default",
                                    "DETAIL_URL" => "",	// URL страницы детального просмотра (по умолчанию - из настроек инфоблока)
                                    "DISPLAY_BOTTOM_PAGER" => "Y",	// Выводить под списком
                                    "DISPLAY_DATE" => "Y",	// Выводить дату элемента
                                    "DISPLAY_NAME" => "Y",	// Выводить название элемента
                                    "DISPLAY_PICTURE" => "Y",	// Выводить изображение для анонса
                                    "DISPLAY_PREVIEW_TEXT" => "Y",	// Выводить текст анонса
                                    "DISPLAY_TOP_PAGER" => "N",	// Выводить над списком
                                    "FIELD_CODE" => array(	// Поля
                                        0 => "",
                                        1 => "",
                                    ),
                                    "FILTER_NAME" => "",	// Фильтр
                                    "HIDE_LINK_WHEN_NO_DETAIL" => "N",	// Скрывать ссылку, если нет детального описания
                                    "IBLOCK_ID" => "6",	// Код информационного блока
                                    "IBLOCK_TYPE" => "content",	// Тип информационного блока (используется только для проверки)
                                    "INCLUDE_IBLOCK_INTO_CHAIN" => "N",	// Включать инфоблок в цепочку навигации
                                    "INCLUDE_SUBSECTIONS" => "Y",	// Показывать элементы подразделов раздела
                                    "MESSAGE_404" => "",	// Сообщение для показа (по умолчанию из компонента)
                                    "NEWS_COUNT" => "20",	// Количество новостей на странице
                                    "PAGER_BASE_LINK_ENABLE" => "N",	// Включить обработку ссылок
                                    "PAGER_DESC_NUMBERING" => "N",	// Использовать обратную навигацию
                                    "PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",	// Время кеширования страниц для обратной навигации
                                    "PAGER_SHOW_ALL" => "N",	// Показывать ссылку "Все"
                                    "PAGER_SHOW_ALWAYS" => "N",	// Выводить всегда
                                    "PAGER_TEMPLATE" => ".default",	// Шаблон постраничной навигации
                                    "PAGER_TITLE" => "",	// Название категорий
                                    "PARENT_SECTION" => "",	// ID раздела
                                    "PARENT_SECTION_CODE" => "main-contacts",	// Код раздела
                                    "PREVIEW_TRUNCATE_LEN" => "",	// Максимальная длина анонса для вывода (только для типа текст)
                                    "PROPERTY_CODE" => array(	// Свойства
                                        0 => "LINK",
                                        1 => "",
                                    ),
                                    "SET_BROWSER_TITLE" => "N",	// Устанавливать заголовок окна браузера
                                    "SET_LAST_MODIFIED" => "N",	// Устанавливать в заголовках ответа время модификации страницы
                                    "SET_META_DESCRIPTION" => "N",	// Устанавливать описание страницы
                                    "SET_META_KEYWORDS" => "N",	// Устанавливать ключевые слова страницы
                                    "SET_STATUS_404" => "N",	// Устанавливать статус 404
                                    "SET_TITLE" => "N",	// Устанавливать заголовок страницы
                                    "SHOW_404" => "N",	// Показ специальной страницы
                                    "SORT_BY1" => "SORT",	// Поле для первой сортировки новостей
                                    "SORT_BY2" => "SORT",	// Поле для второй сортировки новостей
                                    "SORT_ORDER1" => "ASC",	// Направление для первой сортировки новостей
                                    "SORT_ORDER2" => "ASC",	// Направление для второй сортировки новостей
                                ),
                                    false
                                );?>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-none d-lg-block">
            <div class="bg-menu-close button-icon">
                <svg class="bg-menu-close-icon" width="20" height="20">
                    <use xlink:href="#icon-close"></use>
                </svg>
                <small class="sidebar-tooltip">Скрыть меню</small>
            </div>
            <div class="bg-menu-open button-icon">
                <svg class="bg-menu-close-icon" width="20" height="20">
                    <use xlink:href="#icon-burger"></use>
                </svg>
                <small class="sidebar-tooltip">Показать меню</small>
            </div>
            <div class="sidebar-bg"></div>
        </div>
    </div>

    <div class="sidebar overtop-page">
        <?$APPLICATION->IncludeComponent("bitrix:news.list", "sidebar-contacts", Array(
            "ACTIVE_DATE_FORMAT" => "d F Y",	// Формат показа даты
            "ADD_SECTIONS_CHAIN" => "N",	// Включать раздел в цепочку навигации
            "AJAX_MODE" => "N",	// Включить режим AJAX
            "AJAX_OPTION_ADDITIONAL" => "",	// Дополнительный идентификатор
            "AJAX_OPTION_HISTORY" => "N",	// Включить эмуляцию навигации браузера
            "AJAX_OPTION_JUMP" => "N",	// Включить прокрутку к началу компонента
            "AJAX_OPTION_STYLE" => "N",	// Включить подгрузку стилей
            "CACHE_FILTER" => "N",	// Кешировать при установленном фильтре
            "CACHE_GROUPS" => "Y",	// Учитывать права доступа
            "CACHE_TIME" => "36000000",	// Время кеширования (сек.)
            "CACHE_TYPE" => "A",	// Тип кеширования
            "CHECK_DATES" => "Y",	// Показывать только активные на данный момент элементы
            "COMPONENT_TEMPLATE" => ".default",
            "DETAIL_URL" => "",	// URL страницы детального просмотра (по умолчанию - из настроек инфоблока)
            "DISPLAY_BOTTOM_PAGER" => "Y",	// Выводить под списком
            "DISPLAY_DATE" => "Y",	// Выводить дату элемента
            "DISPLAY_NAME" => "Y",	// Выводить название элемента
            "DISPLAY_PICTURE" => "Y",	// Выводить изображение для анонса
            "DISPLAY_PREVIEW_TEXT" => "Y",	// Выводить текст анонса
            "DISPLAY_TOP_PAGER" => "N",	// Выводить над списком
            "FIELD_CODE" => array(	// Поля
                0 => "",
                1 => "",
            ),
            "FILTER_NAME" => "",	// Фильтр
            "HIDE_LINK_WHEN_NO_DETAIL" => "N",	// Скрывать ссылку, если нет детального описания
            "IBLOCK_ID" => "6",	// Код информационного блока
            "IBLOCK_TYPE" => "content",	// Тип информационного блока (используется только для проверки)
            "INCLUDE_IBLOCK_INTO_CHAIN" => "N",	// Включать инфоблок в цепочку навигации
            "INCLUDE_SUBSECTIONS" => "Y",	// Показывать элементы подразделов раздела
            "MESSAGE_404" => "",	// Сообщение для показа (по умолчанию из компонента)
            "NEWS_COUNT" => "20",	// Количество новостей на странице
            "PAGER_BASE_LINK_ENABLE" => "N",	// Включить обработку ссылок
            "PAGER_DESC_NUMBERING" => "N",	// Использовать обратную навигацию
            "PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",	// Время кеширования страниц для обратной навигации
            "PAGER_SHOW_ALL" => "N",	// Показывать ссылку "Все"
            "PAGER_SHOW_ALWAYS" => "N",	// Выводить всегда
            "PAGER_TEMPLATE" => ".default",	// Шаблон постраничной навигации
            "PAGER_TITLE" => "",	// Название категорий
            "PARENT_SECTION" => "",	// ID раздела
            "PARENT_SECTION_CODE" => "sidebar-contacts",	// Код раздела
            "PREVIEW_TRUNCATE_LEN" => "",	// Максимальная длина анонса для вывода (только для типа текст)
            "PROPERTY_CODE" => array(	// Свойства
                0 => "LINK",
                1 => "",
            ),
            "SET_BROWSER_TITLE" => "N",	// Устанавливать заголовок окна браузера
            "SET_LAST_MODIFIED" => "N",	// Устанавливать в заголовках ответа время модификации страницы
            "SET_META_DESCRIPTION" => "N",	// Устанавливать описание страницы
            "SET_META_KEYWORDS" => "N",	// Устанавливать ключевые слова страницы
            "SET_STATUS_404" => "N",	// Устанавливать статус 404
            "SET_TITLE" => "N",	// Устанавливать заголовок страницы
            "SHOW_404" => "N",	// Показ специальной страницы
            "SORT_BY1" => "SORT",	// Поле для первой сортировки новостей
            "SORT_BY2" => "SORT",	// Поле для второй сортировки новостей
            "SORT_ORDER1" => "ASC",	// Направление для первой сортировки новостей
            "SORT_ORDER2" => "ASC",	// Направление для второй сортировки новостей
        ),
            false
        );?>
    </div>

    <main id="main" class="flex-shrink-0 mb-auto">

        <?if($showTitleBlock):?>
            <section class="section section-first pb-3">
                <div class="section-header">
                    <div class="container-fluid">
                        <div class="d-flex align-items-center mb-3 mb-lg-4">
                            <h1 id="main-title" class="h2"><?$APPLICATION->ShowTitle(false);?></h1>
                        </div>
                        <div class="breadcrumbs">
                            <?$APPLICATION->IncludeComponent(
                                "bitrix:breadcrumb",
                                "header",
                                Array(
                                    "COMPONENT_TEMPLATE" => ".default",
                                    "PATH" => "",
                                    "SITE_ID" => SITE_ID,
                                    "START_FROM" => 0
                                )
                            );?>
                        </div>
                    </div>
                </div>
            </section>
        <?endif;?>
						