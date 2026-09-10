<?
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');
$APPLICATION->SetTitle('Интернет-магазин специализированной одежды и аксессуаров Формаро');
$APPLICATION->SetPageProperty("title", "Интернет-магазин специализированной одежды и аксессуаров Формаро");
?>

    <!-- Каталог - Поиск -->
    <section id="hero-search" class="section pt-5">
        <div class="section-header mb-4">
            <div class="container-fluid pt-5 d-md-flex align-items-center">
                <h1 class="h2 mb-4"><span class="text-primary">Товары из России</span> от <span class="border-bottom border-primary">проверенных</span> поставщиков</h1>
                <a href="/for-partners/become-a-partner/" class="f-button c-warning text-uppercase ml-auto mb-3">Начать продавать</a>
            </div>
        </div>
        <div class="section-body">
            <div class="container-fluid">
                <div class="hero-search-block mb-5">
                    <div class="hero-search-group">
                        <div class="hero-search-input">
                            <input type="text" id="hero-search-input" class="search-input" placeholder="Начните поиск здесь...">
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
                            <svg class="hero-search-input-icon" width="24" height="24">
                                <use xlink:href="#icon-search"></use>
                            </svg>
                        </div>
                        <div class="hero-search-buttons">
                            <button class="f-button c-success f-clear mr-0 px-4" data-fancybox data-src="#filter-popup">
                                <svg width="16" height="16">
                                    <use xlink:href="#icon-filter-white"></use>
                                </svg>
                                <span class="pl-2 text-uppercase d-sm-none d-md-inline">Фильтры</span>
                            </button>
                            <button class="f-button c-primary">
                                <svg width="16" height="16" class="d-none d-sm-inline d-md-none">
                                    <use xlink:href="#icon-arrow-control"></use>
                                </svg>
                                <span class="pl-2 text-uppercase d-sm-none d-md-inline">Искать</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="d-none d-md-block">
                    <div id="catalog-grid" class="row product-list d-flex flex-wrap">

                        <div class="product-item">
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
                        <div class="product-item">
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
                        <div class="product-item">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/3.jpeg')"></a>
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
                                    <a href="/html/product-detail.html"><h3 title="Жилет Спасательный Двухсторонний С Воротником 060-80Кг">Жилет Спасательный Двухсторонний С Воротником 060-80Кг</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="2029">2 029</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: 1233445</small>
                                        <small>Под заказ (1 месяц)</small>
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
                                        <input type="text" data-min="5" data-max="0" value="5">
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
                        <div class="product-item">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/4.jpg')"></a>
                                    <div class="badges">
                                        <small class="bg-warning">хит</small><br/>
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
                                    <a href="/html/product-detail.html"><h3 title="Жилет с подогревом Thermalli La Norma, черный">Жилет с подогревом Thermalli La Norma, черный</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="8107">8 107</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: 1233445</small>
                                        <small>В наличии: 12 шт.</small>
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
                                        <input type="text" data-min="1" data-max="12" value="1">
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
                        <div class="product-item">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/5.jpeg')"></a>
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
                                    <a href="/html/product-detail.html"><h3 title="Нашивка КАДЕТЫ (перо и шпага) белая и желтая на красном сукне">Нашивка КАДЕТЫ (перо и шпага) белая и желтая на красном сукне</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="35">35</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: ФОР-НАШ-014</small>
                                        <small>В наличии: 15 уп.</small>
                                    </div>
                                </div>
                                <div class="product-card__actions in-cart">
                                    <div class="cart-cnt">
                                        <div class="buttons">
                                            <button class="cart-cnt-plus">
                                                <svg width="20" height="20">
                                                    <use xlink:href="#icon-plus"></use>
                                                </svg>
                                            </button>
                                            <button class="cart-cnt-minus">
                                                <svg width="20" height="20">
                                                    <use xlink:href="#icon-minus"></use>
                                                </svg>
                                            </button>
                                        </div>
                                        <input type="text" data-min="2" data-max="15" value="2">
                                    </div>
                                    <button class="cart-add f-button c-primary d-none">В корзину</button>
                                    <button class="cart-remove f-button c-gray text-secondary">
                                        <svg width="16" height="16">
                                            <use xlink:href="#icon-delete"></use>
                                        </svg>
                                        <span class="pl-2">Удалить</span>
                                    </button>
                                </div>
                                <a href="#" class="stretched-link"></a>
                            </div>
                        </div>
                        <div class="product-item">
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
                        <div class="product-item">
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
                        <div class="product-item">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/3.jpeg')"></a>
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
                                    <a href="/html/product-detail.html"><h3 title="Жилет Спасательный Двухсторонний С Воротником 060-80Кг">Жилет Спасательный Двухсторонний С Воротником 060-80Кг</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="2029">2 029</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: 1233445</small>
                                        <small>Под заказ (1 месяц)</small>
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
                                        <input type="text" data-min="5" data-max="0" value="5">
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
                        <div class="product-item">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/4.jpg')"></a>
                                    <div class="badges">
                                        <small class="bg-warning">хит</small><br/>
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
                                    <a href="/html/product-detail.html"><h3 title="Жилет с подогревом Thermalli La Norma, черный">Жилет с подогревом Thermalli La Norma, черный</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="8107">8 107</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: 1233445</small>
                                        <small>В наличии: 12 шт.</small>
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
                                        <input type="text" data-min="1" data-max="12" value="1">
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
                        <div class="product-item">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/5.jpeg')"></a>
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
                                    <a href="/html/product-detail.html"><h3 title="Нашивка КАДЕТЫ (перо и шпага) белая и желтая на красном сукне">Нашивка КАДЕТЫ (перо и шпага) белая и желтая на красном сукне</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="35">35</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: ФОР-НАШ-014</small>
                                        <small>В наличии: 15 уп.</small>
                                    </div>
                                </div>
                                <div class="product-card__actions in-cart">
                                    <div class="cart-cnt">
                                        <div class="buttons">
                                            <button class="cart-cnt-plus">
                                                <svg width="20" height="20">
                                                    <use xlink:href="#icon-plus"></use>
                                                </svg>
                                            </button>
                                            <button class="cart-cnt-minus">
                                                <svg width="20" height="20">
                                                    <use xlink:href="#icon-minus"></use>
                                                </svg>
                                            </button>
                                        </div>
                                        <input type="text" data-min="2" data-max="15" value="2">
                                    </div>
                                    <button class="cart-add f-button c-primary d-none">В корзину</button>
                                    <button class="cart-remove f-button c-gray text-secondary">
                                        <svg width="16" height="16">
                                            <use xlink:href="#icon-delete"></use>
                                        </svg>
                                        <span class="pl-2">Удалить</span>
                                    </button>
                                </div>
                                <a href="#" class="stretched-link"></a>
                            </div>
                        </div>
                        <div class="product-item">
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
                        <div class="product-item">
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
                        <div class="product-item">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/3.jpeg')"></a>
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
                                    <a href="/html/product-detail.html"><h3 title="Жилет Спасательный Двухсторонний С Воротником 060-80Кг">Жилет Спасательный Двухсторонний С Воротником 060-80Кг</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="2029">2 029</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: 1233445</small>
                                        <small>Под заказ (1 месяц)</small>
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
                                        <input type="text" data-min="5" data-max="0" value="5">
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
                        <div class="product-item">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/4.jpg')"></a>
                                    <div class="badges">
                                        <small class="bg-warning">хит</small><br/>
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
                                    <a href="/html/product-detail.html"><h3 title="Жилет с подогревом Thermalli La Norma, черный">Жилет с подогревом Thermalli La Norma, черный</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="8107">8 107</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: 1233445</small>
                                        <small>В наличии: 12 шт.</small>
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
                                        <input type="text" data-min="1" data-max="12" value="1">
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
                        <div class="product-item">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/5.jpeg')"></a>
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
                                    <a href="/html/product-detail.html"><h3 title="Нашивка КАДЕТЫ (перо и шпага) белая и желтая на красном сукне">Нашивка КАДЕТЫ (перо и шпага) белая и желтая на красном сукне</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="35">35</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: ФОР-НАШ-014</small>
                                        <small>В наличии: 15 уп.</small>
                                    </div>
                                </div>
                                <div class="product-card__actions in-cart">
                                    <div class="cart-cnt">
                                        <div class="buttons">
                                            <button class="cart-cnt-plus">
                                                <svg width="20" height="20">
                                                    <use xlink:href="#icon-plus"></use>
                                                </svg>
                                            </button>
                                            <button class="cart-cnt-minus">
                                                <svg width="20" height="20">
                                                    <use xlink:href="#icon-minus"></use>
                                                </svg>
                                            </button>
                                        </div>
                                        <input type="text" data-min="2" data-max="15" value="2">
                                    </div>
                                    <button class="cart-add f-button c-primary d-none">В корзину</button>
                                    <button class="cart-remove f-button c-gray text-secondary">
                                        <svg width="16" height="16">
                                            <use xlink:href="#icon-delete"></use>
                                        </svg>
                                        <span class="pl-2">Удалить</span>
                                    </button>
                                </div>
                                <a href="#" class="stretched-link"></a>
                            </div>
                        </div>
                        <div class="product-item">
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
                        <div class="product-item">
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
                        <div class="product-item">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/3.jpeg')"></a>
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
                                    <a href="/html/product-detail.html"><h3 title="Жилет Спасательный Двухсторонний С Воротником 060-80Кг">Жилет Спасательный Двухсторонний С Воротником 060-80Кг</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="2029">2 029</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: 1233445</small>
                                        <small>Под заказ (1 месяц)</small>
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
                                        <input type="text" data-min="5" data-max="0" value="5">
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
                        <div class="product-item">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/4.jpg')"></a>
                                    <div class="badges">
                                        <small class="bg-warning">хит</small><br/>
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
                                    <a href="/html/product-detail.html"><h3 title="Жилет с подогревом Thermalli La Norma, черный">Жилет с подогревом Thermalli La Norma, черный</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="8107">8 107</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: 1233445</small>
                                        <small>В наличии: 12 шт.</small>
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
                                        <input type="text" data-min="1" data-max="12" value="1">
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
                        <div class="product-item">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/5.jpeg')"></a>
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
                                    <a href="/html/product-detail.html"><h3 title="Нашивка КАДЕТЫ (перо и шпага) белая и желтая на красном сукне">Нашивка КАДЕТЫ (перо и шпага) белая и желтая на красном сукне</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="35">35</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: ФОР-НАШ-014</small>
                                        <small>В наличии: 15 уп.</small>
                                    </div>
                                </div>
                                <div class="product-card__actions in-cart">
                                    <div class="cart-cnt">
                                        <div class="buttons">
                                            <button class="cart-cnt-plus">
                                                <svg width="20" height="20">
                                                    <use xlink:href="#icon-plus"></use>
                                                </svg>
                                            </button>
                                            <button class="cart-cnt-minus">
                                                <svg width="20" height="20">
                                                    <use xlink:href="#icon-minus"></use>
                                                </svg>
                                            </button>
                                        </div>
                                        <input type="text" data-min="2" data-max="15" value="2">
                                    </div>
                                    <button class="cart-add f-button c-primary d-none">В корзину</button>
                                    <button class="cart-remove f-button c-gray text-secondary">
                                        <svg width="16" height="16">
                                            <use xlink:href="#icon-delete"></use>
                                        </svg>
                                        <span class="pl-2">Удалить</span>
                                    </button>
                                </div>
                                <a href="#" class="stretched-link"></a>
                            </div>
                        </div>
                        <div class="product-item">
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
                        <div class="product-item">
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
                        <div class="product-item">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/3.jpeg')"></a>
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
                                    <a href="/html/product-detail.html"><h3 title="Жилет Спасательный Двухсторонний С Воротником 060-80Кг">Жилет Спасательный Двухсторонний С Воротником 060-80Кг</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="2029">2 029</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: 1233445</small>
                                        <small>Под заказ (1 месяц)</small>
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
                                        <input type="text" data-min="5" data-max="0" value="5">
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
                        <div class="product-item">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/4.jpg')"></a>
                                    <div class="badges">
                                        <small class="bg-warning">хит</small><br/>
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
                                    <a href="/html/product-detail.html"><h3 title="Жилет с подогревом Thermalli La Norma, черный">Жилет с подогревом Thermalli La Norma, черный</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="8107">8 107</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: 1233445</small>
                                        <small>В наличии: 12 шт.</small>
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
                                        <input type="text" data-min="1" data-max="12" value="1">
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

                        <div class="pager">
                            <div class="d-flex justify-content-center pt-4">
                                <button class="f-button c-secondary" id="load-more">
                                    <svg width="16" height="16">
                                        <use xlink:href="#icon-grid"></use>
                                    </svg>
                                    <svg width="16" height="16" class="icon-progress d-none">
                                        <use xlink:href="#icon-progress"></use>
                                    </svg>
                                    <span class="pl-2">Показать еще</span>
                                </button>
                            </div>

                            <div class="d-flex justify-content-center hide-on-main pt-5">
                                <ul class="pagging__list">
                                    <li class="mr-4">
                                        <a href="#" class="pagging__item bg-secondary text-white">
                                            <svg width="16" height="16">
                                                <use xlink:href="#icon-arrow-left"></use>
                                            </svg>
                                        </a>
                                    </li>
                                    <li><a href="#" class="pagging__item">1</a></li>
                                    <li><span class="pagging__item">2</span></li>
                                    <li><a href="#" class="pagging__item">3</a></li>
                                    <li><a href="#" class="pagging__item">4</a></li>
                                    <li><a href="#" class="pagging__item">5</a></li>
                                    <li><a href="#" class="pagging__item">...</a></li>
                                    <li><a href="#" class="pagging__item">7</a></li>
                                    <li class="ml-4">
                                        <a href="#" class="pagging__item bg-secondary text-white">
                                            <svg width="16" height="16">
                                                <use xlink:href="#icon-arrow-right"></use>
                                            </svg>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>

                    </div>
                </div>
                <style>
                    .hide-on-main {
                        display: none !important;
                    }
                </style>

                <div class="d-md-none">
                    <div id="catalog-search" class="main-carousel product-carousel" _data-flickity='{ "cellAlign": "left", "wrapAround": true, "pageDots": false }'>

                        <div class="carousel-cell">
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
                        <div class="carousel-cell">
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
                        <div class="carousel-cell">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/3.jpeg')"></a>
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
                                    <a href="/html/product-detail.html"><h3 title="Жилет Спасательный Двухсторонний С Воротником 060-80Кг">Жилет Спасательный Двухсторонний С Воротником 060-80Кг</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="2029">2 029</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: 1233445</small>
                                        <small>Под заказ (1 месяц)</small>
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
                                        <input type="text" data-min="5" data-max="0" value="5">
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
                        <div class="carousel-cell">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/4.jpg')"></a>
                                    <div class="badges">
                                        <small class="bg-warning">хит</small><br/>
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
                                    <a href="/html/product-detail.html"><h3 title="Жилет с подогревом Thermalli La Norma, черный">Жилет с подогревом Thermalli La Norma, черный</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="8107">8 107</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: 1233445</small>
                                        <small>В наличии: 12 шт.</small>
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
                                        <input type="text" data-min="1" data-max="12" value="1">
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
                        <div class="carousel-cell">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/5.jpeg')"></a>
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
                                    <a href="/html/product-detail.html"><h3 title="Нашивка КАДЕТЫ (перо и шпага) белая и желтая на красном сукне">Нашивка КАДЕТЫ (перо и шпага) белая и желтая на красном сукне</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="35">35</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: ФОР-НАШ-014</small>
                                        <small>В наличии: 15 уп.</small>
                                    </div>
                                </div>
                                <div class="product-card__actions in-cart">
                                    <div class="cart-cnt">
                                        <div class="buttons">
                                            <button class="cart-cnt-plus">
                                                <svg width="20" height="20">
                                                    <use xlink:href="#icon-plus"></use>
                                                </svg>
                                            </button>
                                            <button class="cart-cnt-minus">
                                                <svg width="20" height="20">
                                                    <use xlink:href="#icon-minus"></use>
                                                </svg>
                                            </button>
                                        </div>
                                        <input type="text" data-min="2" data-max="15" value="2">
                                    </div>
                                    <button class="cart-add f-button c-primary d-none">В корзину</button>
                                    <button class="cart-remove f-button c-gray text-secondary">
                                        <svg width="16" height="16">
                                            <use xlink:href="#icon-delete"></use>
                                        </svg>
                                        <span class="pl-2">Удалить</span>
                                    </button>
                                </div>
                                <a href="#" class="stretched-link"></a>
                            </div>
                        </div>
                        <div class="carousel-cell">
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
                        <div class="carousel-cell">
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
                        <div class="carousel-cell">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/3.jpeg')"></a>
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
                                    <a href="/html/product-detail.html"><h3 title="Жилет Спасательный Двухсторонний С Воротником 060-80Кг">Жилет Спасательный Двухсторонний С Воротником 060-80Кг</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="2029">2 029</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: 1233445</small>
                                        <small>Под заказ (1 месяц)</small>
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
                                        <input type="text" data-min="5" data-max="0" value="5">
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
                        <div class="carousel-cell">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/4.jpg')"></a>
                                    <div class="badges">
                                        <small class="bg-warning">хит</small><br/>
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
                                    <a href="/html/product-detail.html"><h3 title="Жилет с подогревом Thermalli La Norma, черный">Жилет с подогревом Thermalli La Norma, черный</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="8107">8 107</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: 1233445</small>
                                        <small>В наличии: 12 шт.</small>
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
                                        <input type="text" data-min="1" data-max="12" value="1">
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
                        <div class="carousel-cell">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/5.jpeg')"></a>
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
                                    <a href="/html/product-detail.html"><h3 title="Нашивка КАДЕТЫ (перо и шпага) белая и желтая на красном сукне">Нашивка КАДЕТЫ (перо и шпага) белая и желтая на красном сукне</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="35">35</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: ФОР-НАШ-014</small>
                                        <small>В наличии: 15 уп.</small>
                                    </div>
                                </div>
                                <div class="product-card__actions in-cart">
                                    <div class="cart-cnt">
                                        <div class="buttons">
                                            <button class="cart-cnt-plus">
                                                <svg width="20" height="20">
                                                    <use xlink:href="#icon-plus"></use>
                                                </svg>
                                            </button>
                                            <button class="cart-cnt-minus">
                                                <svg width="20" height="20">
                                                    <use xlink:href="#icon-minus"></use>
                                                </svg>
                                            </button>
                                        </div>
                                        <input type="text" data-min="2" data-max="15" value="2">
                                    </div>
                                    <button class="cart-add f-button c-primary d-none">В корзину</button>
                                    <button class="cart-remove f-button c-gray text-secondary">
                                        <svg width="16" height="16">
                                            <use xlink:href="#icon-delete"></use>
                                        </svg>
                                        <span class="pl-2">Удалить</span>
                                    </button>
                                </div>
                                <a href="#" class="stretched-link"></a>
                            </div>
                        </div>
                        <div class="carousel-cell">
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
                        <div class="carousel-cell">
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
                        <div class="carousel-cell">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/3.jpeg')"></a>
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
                                    <a href="/html/product-detail.html"><h3 title="Жилет Спасательный Двухсторонний С Воротником 060-80Кг">Жилет Спасательный Двухсторонний С Воротником 060-80Кг</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="2029">2 029</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: 1233445</small>
                                        <small>Под заказ (1 месяц)</small>
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
                                        <input type="text" data-min="5" data-max="0" value="5">
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
                        <div class="carousel-cell">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/4.jpg')"></a>
                                    <div class="badges">
                                        <small class="bg-warning">хит</small><br/>
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
                                    <a href="/html/product-detail.html"><h3 title="Жилет с подогревом Thermalli La Norma, черный">Жилет с подогревом Thermalli La Norma, черный</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="8107">8 107</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: 1233445</small>
                                        <small>В наличии: 12 шт.</small>
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
                                        <input type="text" data-min="1" data-max="12" value="1">
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
                        <div class="carousel-cell">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/5.jpeg')"></a>
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
                                    <a href="/html/product-detail.html"><h3 title="Нашивка КАДЕТЫ (перо и шпага) белая и желтая на красном сукне">Нашивка КАДЕТЫ (перо и шпага) белая и желтая на красном сукне</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="35">35</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: ФОР-НАШ-014</small>
                                        <small>В наличии: 15 уп.</small>
                                    </div>
                                </div>
                                <div class="product-card__actions in-cart">
                                    <div class="cart-cnt">
                                        <div class="buttons">
                                            <button class="cart-cnt-plus">
                                                <svg width="20" height="20">
                                                    <use xlink:href="#icon-plus"></use>
                                                </svg>
                                            </button>
                                            <button class="cart-cnt-minus">
                                                <svg width="20" height="20">
                                                    <use xlink:href="#icon-minus"></use>
                                                </svg>
                                            </button>
                                        </div>
                                        <input type="text" data-min="2" data-max="15" value="2">
                                    </div>
                                    <button class="cart-add f-button c-primary d-none">В корзину</button>
                                    <button class="cart-remove f-button c-gray text-secondary">
                                        <svg width="16" height="16">
                                            <use xlink:href="#icon-delete"></use>
                                        </svg>
                                        <span class="pl-2">Удалить</span>
                                    </button>
                                </div>
                                <a href="#" class="stretched-link"></a>
                            </div>
                        </div>
                        <div class="carousel-cell">
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
                        <div class="carousel-cell">
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
                        <div class="carousel-cell">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/3.jpeg')"></a>
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
                                    <a href="/html/product-detail.html"><h3 title="Жилет Спасательный Двухсторонний С Воротником 060-80Кг">Жилет Спасательный Двухсторонний С Воротником 060-80Кг</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="2029">2 029</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: 1233445</small>
                                        <small>Под заказ (1 месяц)</small>
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
                                        <input type="text" data-min="5" data-max="0" value="5">
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
                        <div class="carousel-cell">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/4.jpg')"></a>
                                    <div class="badges">
                                        <small class="bg-warning">хит</small><br/>
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
                                    <a href="/html/product-detail.html"><h3 title="Жилет с подогревом Thermalli La Norma, черный">Жилет с подогревом Thermalli La Norma, черный</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="8107">8 107</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: 1233445</small>
                                        <small>В наличии: 12 шт.</small>
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
                                        <input type="text" data-min="1" data-max="12" value="1">
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
                        <div class="carousel-cell">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/5.jpeg')"></a>
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
                                    <a href="/html/product-detail.html"><h3 title="Нашивка КАДЕТЫ (перо и шпага) белая и желтая на красном сукне">Нашивка КАДЕТЫ (перо и шпага) белая и желтая на красном сукне</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="35">35</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: ФОР-НАШ-014</small>
                                        <small>В наличии: 15 уп.</small>
                                    </div>
                                </div>
                                <div class="product-card__actions in-cart">
                                    <div class="cart-cnt">
                                        <div class="buttons">
                                            <button class="cart-cnt-plus">
                                                <svg width="20" height="20">
                                                    <use xlink:href="#icon-plus"></use>
                                                </svg>
                                            </button>
                                            <button class="cart-cnt-minus">
                                                <svg width="20" height="20">
                                                    <use xlink:href="#icon-minus"></use>
                                                </svg>
                                            </button>
                                        </div>
                                        <input type="text" data-min="2" data-max="15" value="2">
                                    </div>
                                    <button class="cart-add f-button c-primary d-none">В корзину</button>
                                    <button class="cart-remove f-button c-gray text-secondary">
                                        <svg width="16" height="16">
                                            <use xlink:href="#icon-delete"></use>
                                        </svg>
                                        <span class="pl-2">Удалить</span>
                                    </button>
                                </div>
                                <a href="#" class="stretched-link"></a>
                            </div>
                        </div>
                        <div class="carousel-cell">
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
                        <div class="carousel-cell">
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
                        <div class="carousel-cell">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/3.jpeg')"></a>
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
                                    <a href="/html/product-detail.html"><h3 title="Жилет Спасательный Двухсторонний С Воротником 060-80Кг">Жилет Спасательный Двухсторонний С Воротником 060-80Кг</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="2029">2 029</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: 1233445</small>
                                        <small>Под заказ (1 месяц)</small>
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
                                        <input type="text" data-min="5" data-max="0" value="5">
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
                        <div class="carousel-cell">
                            <div class="product-card">
                                <div class="product-card__img">
                                    <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/4.jpg')"></a>
                                    <div class="badges">
                                        <small class="bg-warning">хит</small><br/>
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
                                    <a href="/html/product-detail.html"><h3 title="Жилет с подогревом Thermalli La Norma, черный">Жилет с подогревом Thermalli La Norma, черный</h3></a>
                                    <div class="price mb-2">
                                        <b class="text-danger" data-price="8107">8 107</b> руб/шт.
                                    </div>
                                    <div class="text-secondary">
                                        <small>Артикул: 1233445</small>
                                        <small>В наличии: 12 шт.</small>
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
                                        <input type="text" data-min="1" data-max="12" value="1">
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

                        <div class="carousel-cell carousel-cell-last">
                            <div class="product-card">
                                <button class="load-more-slider">
                                    <svg width="36" height="36">
                                        <use xlink:href="#icon-plus"></use>
                                    </svg>
                                    <svg width="24" height="24" class="icon-progress d-none">
                                        <use xlink:href="#icon-progress"></use>
                                    </svg>
                                </button>
                            </div>
                        </div>


                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Каталог - Фильтр -->
    <div id="filter-popup" style="display: none">
        <form id="filter">
            <div class="filter bg-white">
                <div class="catalog-options mb-1 d-flex align-items-center">
                    <button class="f-button f-dropdown bg-white text-decoration-none">
                        <svg width="16" height="16">
                            <use xlink:href="#icon-filter-black"></use>
                        </svg>
                        <span class="pl-2 d-none d-md-inline-block">Фильтры</span>
                    </button>
                </div>

                <div class="filter-row">
                    <div class="filter-row-title d-flex align-items-center py-2 py-lg-3 px-3 px-lg-4">
                        <h6 class="mr-auto mb-0">Спецодежда</h6>
                        <svg width="20" height="20">
                            <use xlink:href="#icon-arrow-up"></use>
                        </svg>
                    </div>
                    <div class="filter-row-list py-2">
                        <label class="px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox" checked>
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>Ветрозащитная одежда</span>
                        </label>
                        <label class="px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>Влагозащитная одежда</span>
                        </label>
                        <label class="px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>Зимняя спецодежда</span>
                        </label>
                        <label class="px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>Кислотощелочестойкая одежда</span>
                        </label>
                        <label class="px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>Летняя спецодежда</span>
                        </label>
                        <label class="px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>Одежда для сварщиков и металлургов</span>
                        </label>
                        <label class="px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>Одежда химзащиты</span>
                        </label>
                        <label class="px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>Рабочие халаты</span>
                        </label>
                        <label class="collapse-list d-none px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>Разгрузочные и сигнальные жилеты</span>
                        </label>
                        <label class="collapse-list d-none px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>Сигнальная одежда</span>
                        </label>
                        <label class="collapse-list d-none px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>Рабочие халаты</span>
                        </label>
                        <label class="collapse-list d-none px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>Разгрузочные и сигнальные жилеты</span>
                        </label>
                        <label class="collapse-list d-none px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>Сигнальная одежда</span>
                        </label>
                        <label class="collapse-list d-none px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>Рабочие халаты</span>
                        </label>
                        <label class="collapse-list d-none px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>Разгрузочные и сигнальные жилеты</span>
                        </label>
                        <label class="collapse-list d-none px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>Сигнальная одежда</span>
                        </label>
                        <a href="javascript:void(0);" class="d-block px-3 px-lg-4 py-2"><span>Показать все</span><span class="d-none">Свернуть</span></a>
                    </div>
                </div>

                <div class="filter-row">
                    <div class="filter-row-title d-flex align-items-center py-2 py-lg-3 px-3 px-lg-4">
                        <h6 class="mr-auto mb-0">Цена, руб.</h6>
                        <svg width="20" height="20">
                            <use xlink:href="#icon-arrow-up"></use>
                        </svg>
                    </div>
                    <div class="filter-row-list filter-row-price">
                        <div class="filter-row-price-inputs d-flex align-items-center justify-content-between py-2 py-lg-3 px-3 px-lg-4">
                            <input type="text" class="filterPriceMin" value="100" placeholder="100">
                            <span class="text-secondary">&mdash;</span>
                            <input type="text" class="filterPriceMax" value="9999" placeholder="9999">
                        </div>
                        <div class="mx-2 mx-lg-3 d-flex justify-content-center mb-3">
                            <input id="filterPrice" type="text" value="" data-slider-min="100" data-slider-max="9999" data-slider-step="1" data-slider-value="[100,9999]" data-slider-tooltip="hide"/>
                        </div>
                    </div>
                </div>

                <div class="filter-row collapse-row">
                    <div class="filter-row-title d-flex align-items-center py-2 py-lg-3 px-3 px-lg-4">
                        <h6 class="mr-auto mb-0">Цвет</h6>
                        <svg width="20" height="20">
                            <use xlink:href="#icon-arrow-up"></use>
                        </svg>
                    </div>
                    <div class="filter-row-list py-2">
                        <label class="px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>Черный</span>
                        </label>
                        <label class="px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>Красный</span>
                        </label>
                        <label class="px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>Синий</span>
                        </label>
                        <label class="px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>Хаки</span>
                        </label>
                        <label class="px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>Отражающий</span>
                        </label>
                    </div>
                </div>

                <div class="filter-row collapse-row">
                    <div class="filter-row-title d-flex align-items-center py-2 py-lg-3 px-3 px-lg-4">
                        <h6 class="mr-auto mb-0">Рамеры</h6>
                        <svg width="20" height="20">
                            <use xlink:href="#icon-arrow-up"></use>
                        </svg>
                    </div>
                    <div class="filter-row-list py-2">
                        <label class="px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>XXS</span>
                        </label>
                        <label class="px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>XS</span>
                        </label>
                        <label class="px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>S</span>
                        </label>
                        <label class="px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>M</span>
                        </label>
                        <label class="px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>L</span>
                        </label>
                        <label class="px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>XL</span>
                        </label>
                        <label class="px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>XXL</span>
                        </label>
                        <label class="px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>3XL</span>
                        </label>
                        <label class="collapse-list d-none px-3 px-lg-4 py-2 mb-0">
                            <input type="checkbox">
                            <svg width="20" height="20">
                                <use xlink:href="#icon-checkbox-tick"></use>
                            </svg>
                            <span>4XL</span>
                        </label>
                        <a href="javascript:void(0);" class="d-block px-3 px-lg-4 py-2"><span>Показать все</span><span class="d-none">Свернуть</span></a>
                    </div>
                </div>
            </div>

            <div class="d-flex">
                <button class="f-button c-white text-secondary f-clear mr-0 px-2 px-lg-4 w-50">
                    <svg width="16" height="16">
                        <use xlink:href="#icon-close"></use>
                    </svg>
                    <span class="pl-2">Сбросить</span>
                </button>
                <button class="f-button c-success px-2 px-lg-4 w-50" style="margin-right: 15px">
                    <svg width="16" height="16" class="d-none d-sm-inline d-md-none">
                        <use xlink:href="#icon-arrow-control"></use>
                    </svg>
                    <span class="pl-2">Применить</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Выгодные предложения -->
    <section class="section light-gray-stripe">
        <div class="section-header mb-5">
            <div class="container-fluid">
                <div class="d-flex align-items-center">
                    <h3 class="h2"><span class="text-primary">Выгодные</span> предложения</h3>
                    <a href="/html/catalog.html" class="f-button c-gray ml-auto">
                        <svg width="16" height="16" class="d-md-none">
                            <use xlink:href="#icon-arrow-control"></use>
                        </svg>
                        <span class="pl-2 text-uppercase d-none d-md-inline">Перейти в каталог</span>
                    </a>
                </div>
            </div>
        </div>
        <div class="section-body">
            <div class="container-fluid">

                <div class="main-carousel product-carousel" _data-flickity='{ "cellAlign": "left", "wrapAround": true, "pageDots": false }'>

                    <div class="carousel-cell">
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
                    <div class="carousel-cell">
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
                    <div class="carousel-cell">
                        <div class="product-card">
                            <div class="product-card__img">
                                <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/3.jpeg')"></a>
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
                                <a href="/html/product-detail.html"><h3 title="Жилет Спасательный Двухсторонний С Воротником 060-80Кг">Жилет Спасательный Двухсторонний С Воротником 060-80Кг</h3></a>
                                <div class="price mb-2">
                                    <b class="text-danger" data-price="2029">2 029</b> руб/шт.
                                </div>
                                <div class="text-secondary">
                                    <small>Артикул: 1233445</small>
                                    <small>Под заказ (1 месяц)</small>
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
                                    <input type="text" data-min="5" data-max="0" value="5">
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
                    <div class="carousel-cell">
                        <div class="product-card">
                            <div class="product-card__img">
                                <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/4.jpg')"></a>
                                <div class="badges">
                                    <small class="bg-warning">хит</small><br/>
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
                                <a href="/html/product-detail.html"><h3 title="Жилет с подогревом Thermalli La Norma, черный">Жилет с подогревом Thermalli La Norma, черный</h3></a>
                                <div class="price mb-2">
                                    <b class="text-danger" data-price="8107">8 107</b> руб/шт.
                                </div>
                                <div class="text-secondary">
                                    <small>Артикул: 1233445</small>
                                    <small>В наличии: 12 шт.</small>
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
                                    <input type="text" data-min="1" data-max="12" value="1">
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
                    <div class="carousel-cell">
                        <div class="product-card">
                            <div class="product-card__img">
                                <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/5.jpeg')"></a>
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
                                <a href="/html/product-detail.html"><h3 title="Нашивка КАДЕТЫ (перо и шпага) белая и желтая на красном сукне">Нашивка КАДЕТЫ (перо и шпага) белая и желтая на красном сукне</h3></a>
                                <div class="price mb-2">
                                    <b class="text-danger" data-price="35">35</b> руб/шт.
                                </div>
                                <div class="text-secondary">
                                    <small>Артикул: ФОР-НАШ-014</small>
                                    <small>В наличии: 15 уп.</small>
                                </div>
                            </div>
                            <div class="product-card__actions in-cart">
                                <div class="cart-cnt">
                                    <div class="buttons">
                                        <button class="cart-cnt-plus">
                                            <svg width="20" height="20">
                                                <use xlink:href="#icon-plus"></use>
                                            </svg>
                                        </button>
                                        <button class="cart-cnt-minus">
                                            <svg width="20" height="20">
                                                <use xlink:href="#icon-minus"></use>
                                            </svg>
                                        </button>
                                    </div>
                                    <input type="text" data-min="2" data-max="15" value="2">
                                </div>
                                <button class="cart-add f-button c-primary d-none">В корзину</button>
                                <button class="cart-remove f-button c-gray text-secondary">
                                    <svg width="16" height="16">
                                        <use xlink:href="#icon-delete"></use>
                                    </svg>
                                    <span class="pl-2">Удалить</span>
                                </button>
                            </div>
                            <a href="#" class="stretched-link"></a>
                        </div>
                    </div>
                    <div class="carousel-cell">
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
                    <div class="carousel-cell">
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
                    <div class="carousel-cell">
                        <div class="product-card">
                            <div class="product-card__img">
                                <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/3.jpeg')"></a>
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
                                <a href="/html/product-detail.html"><h3 title="Жилет Спасательный Двухсторонний С Воротником 060-80Кг">Жилет Спасательный Двухсторонний С Воротником 060-80Кг</h3></a>
                                <div class="price mb-2">
                                    <b class="text-danger" data-price="2029">2 029</b> руб/шт.
                                </div>
                                <div class="text-secondary">
                                    <small>Артикул: 1233445</small>
                                    <small>Под заказ (1 месяц)</small>
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
                                    <input type="text" data-min="5" data-max="0" value="5">
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
                    <div class="carousel-cell">
                        <div class="product-card">
                            <div class="product-card__img">
                                <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/4.jpg')"></a>
                                <div class="badges">
                                    <small class="bg-warning">хит</small><br/>
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
                                <a href="/html/product-detail.html"><h3 title="Жилет с подогревом Thermalli La Norma, черный">Жилет с подогревом Thermalli La Norma, черный</h3></a>
                                <div class="price mb-2">
                                    <b class="text-danger" data-price="8107">8 107</b> руб/шт.
                                </div>
                                <div class="text-secondary">
                                    <small>Артикул: 1233445</small>
                                    <small>В наличии: 12 шт.</small>
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
                                    <input type="text" data-min="1" data-max="12" value="1">
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
                    <div class="carousel-cell">
                        <div class="product-card">
                            <div class="product-card__img">
                                <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/5.jpeg')"></a>
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
                                <a href="/html/product-detail.html"><h3 title="Нашивка КАДЕТЫ (перо и шпага) белая и желтая на красном сукне">Нашивка КАДЕТЫ (перо и шпага) белая и желтая на красном сукне</h3></a>
                                <div class="price mb-2">
                                    <b class="text-danger" data-price="35">35</b> руб/шт.
                                </div>
                                <div class="text-secondary">
                                    <small>Артикул: ФОР-НАШ-014</small>
                                    <small>В наличии: 15 уп.</small>
                                </div>
                            </div>
                            <div class="product-card__actions in-cart">
                                <div class="cart-cnt">
                                    <div class="buttons">
                                        <button class="cart-cnt-plus">
                                            <svg width="20" height="20">
                                                <use xlink:href="#icon-plus"></use>
                                            </svg>
                                        </button>
                                        <button class="cart-cnt-minus">
                                            <svg width="20" height="20">
                                                <use xlink:href="#icon-minus"></use>
                                            </svg>
                                        </button>
                                    </div>
                                    <input type="text" data-min="2" data-max="15" value="2">
                                </div>
                                <button class="cart-add f-button c-primary d-none">В корзину</button>
                                <button class="cart-remove f-button c-gray text-secondary">
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
            </div>
        </div>
    </section>

    <!-- Новые поступления -->
    <section class="section">
        <div class="section-header mb-5">
            <div class="container-fluid">
                <div class="d-flex align-items-center">
                    <h3 class="h2"><span class="text-primary">Новые</span> поступления</h3>
                    <a href="/html/catalog.html" class="f-button c-gray ml-auto">
                        <svg width="16" height="16" class="d-md-none">
                            <use xlink:href="#icon-arrow-control"></use>
                        </svg>
                        <span class="pl-2 text-uppercase d-none d-md-inline">Перейти в каталог</span>
                    </a>
                </div>
            </div>
        </div>
        <div class="section-body">
            <div class="container-fluid">

                <div class="main-carousel product-carousel" _data-flickity='{ "cellAlign": "left", "wrapAround": true, "pageDots": false }'>

                    <div class="carousel-cell">
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
                    <div class="carousel-cell">
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
                    <div class="carousel-cell">
                        <div class="product-card">
                            <div class="product-card__img">
                                <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/3.jpeg')"></a>
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
                                <a href="/html/product-detail.html"><h3 title="Жилет Спасательный Двухсторонний С Воротником 060-80Кг">Жилет Спасательный Двухсторонний С Воротником 060-80Кг</h3></a>
                                <div class="price mb-2">
                                    <b class="text-danger" data-price="2029">2 029</b> руб/шт.
                                </div>
                                <div class="text-secondary">
                                    <small>Артикул: 1233445</small>
                                    <small>Под заказ (1 месяц)</small>
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
                                    <input type="text" data-min="5" data-max="0" value="5">
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
                    <div class="carousel-cell">
                        <div class="product-card">
                            <div class="product-card__img">
                                <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/4.jpg')"></a>
                                <div class="badges">
                                    <small class="bg-warning">хит</small><br/>
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
                                <a href="/html/product-detail.html"><h3 title="Жилет с подогревом Thermalli La Norma, черный">Жилет с подогревом Thermalli La Norma, черный</h3></a>
                                <div class="price mb-2">
                                    <b class="text-danger" data-price="8107">8 107</b> руб/шт.
                                </div>
                                <div class="text-secondary">
                                    <small>Артикул: 1233445</small>
                                    <small>В наличии: 12 шт.</small>
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
                                    <input type="text" data-min="1" data-max="12" value="1">
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
                    <div class="carousel-cell">
                        <div class="product-card">
                            <div class="product-card__img">
                                <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/5.jpeg')"></a>
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
                                <a href="/html/product-detail.html"><h3 title="Нашивка КАДЕТЫ (перо и шпага) белая и желтая на красном сукне">Нашивка КАДЕТЫ (перо и шпага) белая и желтая на красном сукне</h3></a>
                                <div class="price mb-2">
                                    <b class="text-danger" data-price="35">35</b> руб/шт.
                                </div>
                                <div class="text-secondary">
                                    <small>Артикул: ФОР-НАШ-014</small>
                                    <small>В наличии: 15 уп.</small>
                                </div>
                            </div>
                            <div class="product-card__actions in-cart">
                                <div class="cart-cnt">
                                    <div class="buttons">
                                        <button class="cart-cnt-plus">
                                            <svg width="20" height="20">
                                                <use xlink:href="#icon-plus"></use>
                                            </svg>
                                        </button>
                                        <button class="cart-cnt-minus">
                                            <svg width="20" height="20">
                                                <use xlink:href="#icon-minus"></use>
                                            </svg>
                                        </button>
                                    </div>
                                    <input type="text" data-min="2" data-max="15" value="2">
                                </div>
                                <button class="cart-add f-button c-primary d-none">В корзину</button>
                                <button class="cart-remove f-button c-gray text-secondary">
                                    <svg width="16" height="16">
                                        <use xlink:href="#icon-delete"></use>
                                    </svg>
                                    <span class="pl-2">Удалить</span>
                                </button>
                            </div>
                            <a href="#" class="stretched-link"></a>
                        </div>
                    </div>
                    <div class="carousel-cell">
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
                    <div class="carousel-cell">
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
                    <div class="carousel-cell">
                        <div class="product-card">
                            <div class="product-card__img">
                                <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/3.jpeg')"></a>
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
                                <a href="/html/product-detail.html"><h3 title="Жилет Спасательный Двухсторонний С Воротником 060-80Кг">Жилет Спасательный Двухсторонний С Воротником 060-80Кг</h3></a>
                                <div class="price mb-2">
                                    <b class="text-danger" data-price="2029">2 029</b> руб/шт.
                                </div>
                                <div class="text-secondary">
                                    <small>Артикул: 1233445</small>
                                    <small>Под заказ (1 месяц)</small>
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
                                    <input type="text" data-min="5" data-max="0" value="5">
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
                    <div class="carousel-cell">
                        <div class="product-card">
                            <div class="product-card__img">
                                <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/4.jpg')"></a>
                                <div class="badges">
                                    <small class="bg-warning">хит</small><br/>
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
                                <a href="/html/product-detail.html"><h3 title="Жилет с подогревом Thermalli La Norma, черный">Жилет с подогревом Thermalli La Norma, черный</h3></a>
                                <div class="price mb-2">
                                    <b class="text-danger" data-price="8107">8 107</b> руб/шт.
                                </div>
                                <div class="text-secondary">
                                    <small>Артикул: 1233445</small>
                                    <small>В наличии: 12 шт.</small>
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
                                    <input type="text" data-min="1" data-max="12" value="1">
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
                    <div class="carousel-cell">
                        <div class="product-card">
                            <div class="product-card__img">
                                <a href="/html/product-detail.html" class="embed-responsive embed-responsive-1by1" style="background-image: url('/upload/products/5.jpeg')"></a>
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
                                <a href="/html/product-detail.html"><h3 title="Нашивка КАДЕТЫ (перо и шпага) белая и желтая на красном сукне">Нашивка КАДЕТЫ (перо и шпага) белая и желтая на красном сукне</h3></a>
                                <div class="price mb-2">
                                    <b class="text-danger" data-price="35">35</b> руб/шт.
                                </div>
                                <div class="text-secondary">
                                    <small>Артикул: ФОР-НАШ-014</small>
                                    <small>В наличии: 15 уп.</small>
                                </div>
                            </div>
                            <div class="product-card__actions in-cart">
                                <div class="cart-cnt">
                                    <div class="buttons">
                                        <button class="cart-cnt-plus">
                                            <svg width="20" height="20">
                                                <use xlink:href="#icon-plus"></use>
                                            </svg>
                                        </button>
                                        <button class="cart-cnt-minus">
                                            <svg width="20" height="20">
                                                <use xlink:href="#icon-minus"></use>
                                            </svg>
                                        </button>
                                    </div>
                                    <input type="text" data-min="2" data-max="15" value="2">
                                </div>
                                <button class="cart-add f-button c-primary d-none">В корзину</button>
                                <button class="cart-remove f-button c-gray text-secondary">
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
            </div>
        </div>
    </section>

    <?$APPLICATION->IncludeComponent(
        "bitrix:catalog.section.list",
        "catalog",
        [
            "COMPONENT_TEMPLATE" => ".default",
            "IBLOCK_TYPE" => "catalog",
            "IBLOCK_ID" => "3",
            "SECTION_ID" => $_REQUEST["SECTION_ID"],
            "SECTION_CODE" => "",
            "COUNT_ELEMENTS" => "Y",
            "COUNT_ELEMENTS_FILTER" => "CNT_ACTIVE",
            "ADDITIONAL_COUNT_ELEMENTS_FILTER" => "additionalCountFilter",
            "HIDE_SECTIONS_WITH_ZERO_COUNT_ELEMENTS" => "N",
            "TOP_DEPTH" => "2",
            "SECTION_FIELDS" => [
                0 => "NAME",
                1 => "PICTURE",
                2 => "",
            ],
            "SECTION_USER_FIELDS" => [
                0 => "",
                1 => "",
            ],
            "FILTER_NAME" => "sectionsFilter",
            "VIEW_MODE" => "LIST",
            "SHOW_PARENT_NAME" => "Y",
            "SECTION_URL" => "",
            "CACHE_TYPE" => "A",
            "CACHE_TIME" => "36000000",
            "CACHE_GROUPS" => "Y",
            "CACHE_FILTER" => "N",
            "ADD_SECTIONS_CHAIN" => "N"
        ],
        false
    );?>

    <?$APPLICATION->IncludeComponent(
        "bitrix:news.detail",
        "sell-on-formaro",
        Array(
            "ACTIVE_DATE_FORMAT" => "d.m.Y",
            "ADD_ELEMENT_CHAIN" => "N",
            "ADD_SECTIONS_CHAIN" => "N",
            "AJAX_MODE" => "N",
            "AJAX_OPTION_ADDITIONAL" => "",
            "AJAX_OPTION_HISTORY" => "N",
            "AJAX_OPTION_JUMP" => "N",
            "AJAX_OPTION_STYLE" => "Y",
            "BROWSER_TITLE" => "-",
            "CACHE_GROUPS" => "Y",
            "CACHE_TIME" => "36000000",
            "CACHE_TYPE" => "A",
            "CHECK_DATES" => "Y",
            "COMPONENT_TEMPLATE" => ".default",
            "DETAIL_URL" => "",
            "DISPLAY_BOTTOM_PAGER" => "N",
            "DISPLAY_DATE" => "Y",
            "DISPLAY_NAME" => "Y",
            "DISPLAY_PICTURE" => "Y",
            "DISPLAY_PREVIEW_TEXT" => "Y",
            "DISPLAY_TOP_PAGER" => "N",
            "ELEMENT_CODE" => "sell-on-formaro",
            "ELEMENT_ID" => $_REQUEST["ELEMENT_ID"],
            "FIELD_CODE" => array(	// Поля
                0 => "PREVIEW_PICTURE",
                1 => "PREVIEW_TEXT",
            ),
            "IBLOCK_ID" => "7",
            "IBLOCK_TYPE" => "content",
            "IBLOCK_URL" => "",
            "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
            "MESSAGE_404" => "",
            "META_DESCRIPTION" => "-",
            "META_KEYWORDS" => "-",
            "PAGER_BASE_LINK_ENABLE" => "N",
            "PAGER_SHOW_ALL" => "N",
            "PAGER_TEMPLATE" => ".default",
            "PAGER_TITLE" => "Страница",
            "PROPERTY_CODE" => [0=>"",1=>"",],
            "SET_BROWSER_TITLE" => "N",
            "SET_CANONICAL_URL" => "N",
            "SET_LAST_MODIFIED" => "N",
            "SET_META_DESCRIPTION" => "N",
            "SET_META_KEYWORDS" => "N",
            "SET_STATUS_404" => "N",
            "SET_TITLE" => "N",
            "SHOW_404" => "N",
            "STRICT_SECTION_CHECK" => "N",
            "USE_PERMISSIONS" => "N",
            "USE_SHARE" => "N"
        )
    );?>

    <?$APPLICATION->IncludeComponent("bitrix:news.list", "partners-slider", Array(
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
        "IBLOCK_ID" => "2",	// Код информационного блока
        "IBLOCK_TYPE" => "marketplace",	// Тип информационного блока (используется только для проверки)
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
        "PAGER_TITLE" => "Новые партнеры",	// Название категорий
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
        "SECTION_CLASS" => "",
    ),
        false
    );?>

    <?$APPLICATION->IncludeComponent("bitrix:news.list", "news-slider", Array(
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
        "IBLOCK_ID" => "1",	// Код информационного блока
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
        "PAGER_TITLE" => "Новости маркеплейса",	// Название категорий
        "PARENT_SECTION" => "",	// ID раздела
        "PARENT_SECTION_CODE" => "",	// Код раздела
        "PREVIEW_TRUNCATE_LEN" => "",	// Максимальная длина анонса для вывода (только для типа текст)
        "PROPERTY_CODE" => array(	// Свойства
            0 => "",
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
        "SECTION_CLASS" => "light-gray-stripe",
    ),
        false
    );?>

<?
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
?>