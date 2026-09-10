<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
?>


<div class="news-sidebar-wrap col-12 col-xl-4">
    <div id="news-sidebar">

        <?php if($arResult['PROPERTIES']['PARTNER_ID']['VALUE']):?>
            <?
            $GLOBALS['arrFilterPartner'] = ['ID'=>$arResult['PROPERTIES']['PARTNER_ID']['VALUE']];
            ?>
            <?$APPLICATION->IncludeComponent("bitrix:news.list", "news-partner", Array(
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
                "FILTER_NAME" => "arrFilterPartner",	// Фильтр
                "HIDE_LINK_WHEN_NO_DETAIL" => "N",	// Скрывать ссылку, если нет детального описания
                "IBLOCK_ID" => "2",	// Код информационного блока
                "IBLOCK_TYPE" => "marketplace",	// Тип информационного блока (используется только для проверки)
                "INCLUDE_IBLOCK_INTO_CHAIN" => "N",	// Включать инфоблок в цепочку навигации
                "INCLUDE_SUBSECTIONS" => "Y",	// Показывать элементы подразделов раздела
                "MESSAGE_404" => "",	// Сообщение для показа (по умолчанию из компонента)
                "NEWS_COUNT" => "1",	// Количество новостей на странице
                "PAGER_BASE_LINK_ENABLE" => "N",	// Включить обработку ссылок
                "PAGER_DESC_NUMBERING" => "N",	// Использовать обратную навигацию
                "PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",	// Время кеширования страниц для обратной навигации
                "PAGER_SHOW_ALL" => "N",	// Показывать ссылку "Все"
                "PAGER_SHOW_ALWAYS" => "N",	// Выводить всегда
                "PAGER_TEMPLATE" => ".default",	// Шаблон постраничной навигации
                "PAGER_TITLE" => "Новости маркеплейса",	// Название категорий
                "PARENT_SECTION" => "",	// ID раздела
                "PARENT_SECTION_CODE" => "",	// Код раздела
                "PREVIEW_TRUNCATE_LEN" => "",	// Максимальная длина анонса для вывода (только для типа текст)
                "PROPERTY_CODE" => array(	// Свойства
                    0 => "LOGO",
                    1 => "",
                ),
                "SET_BROWSER_TITLE" => "N",	// Устанавливать заголовок окна браузера
                "SET_LAST_MODIFIED" => "N",	// Устанавливать в заголовках ответа время модификации страницы
                "SET_META_DESCRIPTION" => "N",	// Устанавливать описание страницы
                "SET_META_KEYWORDS" => "N",	// Устанавливать ключевые слова страницы
                "SET_STATUS_404" => "N",	// Устанавливать статус 404
                "SET_TITLE" => "N",	// Устанавливать заголовок страницы
                "SHOW_404" => "N",	// Показ специальной страницы
                "SORT_BY1" => "ACTIVE_FROM",	// Поле для первой сортировки новостей
                "SORT_BY2" => "SORT",	// Поле для второй сортировки новостей
                "SORT_ORDER1" => "DESC",	// Направление для первой сортировки новостей
                "SORT_ORDER2" => "ASC",	// Направление для второй сортировки новостей
            ),
                false
            );?>
        <?php endif;?>

        <?php if($arResult['PROPERTIES']['CATALOG_SECTIONS_ID']['VALUE']):?>
            <div class="category-list mb-5">
                <div class="category-item">
                    <div class="bg-cat-img" style="background-image: url('/upload/cats/1.jpg')"></div>
                    <div class="cat-content">
                        <a href="/html/catalog.html" _href="#"><h3 class="mb-4">Спецодежда</h3></a>

                        <div class="cat-content-wrap mb-2">
                            <ul>
                                <li class="sect"><a href="/html/section.html" _href="#" class="dark_link">Ветрозащитная одежда&nbsp;<span>128</span></a></li>
                                <li class="sect"><a href="/html/section.html" _href="#" class="dark_link">Влагозащитная одежда&nbsp;<span>170</span></a></li>
                                <li class="sect"><a href="/html/section.html" _href="#" class="dark_link">Зимняя спецодежда&nbsp;<span>230</span></a></li>
                                <li class="sect"><a href="/html/section.html" _href="#" class="dark_link">Кислотощелочестойкая одежда&nbsp;<span>9</span></a></li>
                                <li class="sect"><a href="/html/section.html" _href="#" class="dark_link">Летняя спецодежда&nbsp;<span>288</span></a></li>
                                <li class="sect"><a href="/html/section.html" _href="#" class="dark_link">Одежда для сварщиков и металлургов&nbsp;<span>74</span></a></li>
                                <li class="sect"><a href="/html/section.html" _href="#" class="dark_link">Одежда химзащиты&nbsp;<span>10</span></a></li>
                                <li class="sect"><a href="/html/section.html" _href="#" class="dark_link">Рабочие халаты&nbsp;<span>40</span></a></li>
                                <li class="sect"><a href="/html/section.html" _href="#" class="dark_link">Разгрузочные и сигнальные жилеты&nbsp;<span>6</span></a></li>
                                <li class="sect"><a href="/html/section.html" _href="#" class="dark_link">Сигнальная одежда&nbsp;<span>60</span></a></li>
                                <li class="sect"><a href="/html/section.html" _href="#" class="dark_link">Рабочие халаты&nbsp;<span>40</span></a></li>
                                <li class="sect"><a href="/html/section.html" _href="#" class="dark_link">Разгрузочные и сигнальные жилеты&nbsp;<span>6</span></a></li>
                                <li class="sect"><a href="/html/section.html" _href="#" class="dark_link">Сигнальная одежда&nbsp;<span>60</span></a></li>
                                <li class="sect"><a href="/html/section.html" _href="#" class="dark_link">Рабочие халаты&nbsp;<span>40</span></a></li>
                                <li class="sect"><a href="/html/section.html" _href="#" class="dark_link">Разгрузочные и сигнальные жилеты&nbsp;<span>6</span></a></li>
                                <li class="sect"><a href="/html/section.html" _href="#" class="dark_link">Сигнальная одежда&nbsp;<span>60</span></a></li>
                            </ul>
                        </div>
                        <ul>
                            <li class="sect"><a href="/html/section.html" _href="/catalog/obuv/letnyaya_rabochaya_obuv/" class="text-primary"><u>Все товары</u></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif;?>

        <?php if($arResult['PROPERTIES']['CATALOG_PRODUCTS_ID']['VALUE']):?>
            <h5 class="mb-0" style="height: 60px">Товары из новости</h5>
            <div id="catalog-grid" class="row product-list d-flex flex-wrap mb-5">

                <div class="product-item col-sm-6 col-md-4 col-xl-6">
                    <div class="product-card">
                        <div class="product-card__img">
                            <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/1.jpeg')"></a>
                            <div class="badges">
                                <small class="bg-success">новинка</small><br/>
                                <small class="bg-danger">-15%</small><br/>
                            </div>
                            <div class="favorite">
                                <button class="button-icon">
                                    <svg width="20" height="20">
                                        <use xlink:href="#icon-favorite-stroke"></use>
                                    </svg>
                                </button>
                                <button class="button-icon d-none">
                                    <svg width="20" height="20">
                                        <use xlink:href="#icon-favorite"></use>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="product-card__info">
                            <a href="/html/product-detail.html"><h3 title="Дождевик мужской Squall, желтый">Дождевик мужской Squall, желтый</h3></a>
                            <div class="price mb-2">
                                <s>3 200</s> <b class="text-danger" data-price="2000">2 800</b> <small>руб/шт.</small>
                            </div>
                            <div class="text-secondary">
                                <small>Артикул: 1233445</small>
                                <small>В наличии: 120 шт.</small>
                            </div>
                        </div>
                        <div class="product-card__actions">
                            <div class="cart-cnt">
                                <div class="buttons">
                                    <button class="cart-cnt-plus">
                                        <svg width="20" height="20">
                                            <use xlink:href="#icon-arrow-up"></use>
                                        </svg>
                                    </button>
                                    <button class="cart-cnt-minus">
                                        <svg width="20" height="20">
                                            <use xlink:href="#icon-arrow-down"></use>
                                        </svg>
                                    </button>
                                </div>
                                <input type="text" data-min="1" data-max="120" value="1">
                            </div>
                            <button class="cart-add f-button c-primary">В корзину</button>
                            <button class="cart-remove f-button c-gray text-secondary d-none">
                                <svg width="16" height="16">
                                    <use xlink:href="#icon-delete"></use>
                                </svg>
                                <span class="pl-2">Удалить</span>
                            </button>
                        </div>
                        <a href="#" class="stretched-link"></a>
                    </div>
                </div>
                <div class="product-item col-sm-6 col-md-4 col-xl-6">
                    <div class="product-card">
                        <div class="product-card__img">
                            <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/2.jpeg')"></a>
                            <div class="badges">
                                <small class="bg-success">новинка</small><br/>
                            </div>
                            <div class="favorite">
                                <button class="button-icon">
                                    <svg width="20" height="20">
                                        <use xlink:href="#icon-favorite-stroke"></use>
                                    </svg>
                                </button>
                                <button class="button-icon d-none">
                                    <svg width="20" height="20">
                                        <use xlink:href="#icon-favorite"></use>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="product-card__info">
                            <a href="/html/product-detail.html"><h3 title="Костюм зимний Дорожник (тк.Оксфорд) брюки, оранжевый/черный">Костюм зимний Дорожник (тк.Оксфорд) брюки, оранжевый/черный</h3></a>
                            <div class="price mb-2">
                                <b class="text-danger" data-price="2000">2 800</b> руб/шт.
                            </div>
                            <div class="text-secondary">
                                <small>Артикул: 1233445</small>
                                <small>Под заказ (2 недели)</small>
                            </div>
                        </div>
                        <div class="product-card__actions">
                            <div class="cart-cnt">
                                <div class="buttons">
                                    <button class="cart-cnt-plus">
                                        <svg width="20" height="20">
                                            <use xlink:href="#icon-arrow-up"></use>
                                        </svg>
                                    </button>
                                    <button class="cart-cnt-minus">
                                        <svg width="20" height="20">
                                            <use xlink:href="#icon-arrow-down"></use>
                                        </svg>
                                    </button>
                                </div>
                                <input type="text" data-min="10" data-max="0" value="10">
                            </div>
                            <button class="cart-add f-button c-primary">В корзину</button>
                            <button class="cart-remove f-button c-gray text-secondary d-none">
                                <svg width="16" height="16">
                                    <use xlink:href="#icon-delete"></use>
                                </svg>
                                <span class="pl-2">Удалить</span>
                            </button>
                        </div>
                        <a href="#" class="stretched-link"></a>
                    </div>
                </div>

            </div>
        <?php endif;?>
    </div>
</div>
