<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;

/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var CatalogProductsViewedComponent $component
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $componentPath
 * @var string $templateFolder
 */

$this->setFrameMode(true);

if (isset($arResult['ITEM']))
{
	$item = $arResult['ITEM'];
	$areaId = $arResult['AREA_ID'];

	?>
    <div class="product-card" id="<?=$areaId?>" data-entity="item">
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
            <a href="/html/product-detail.html"><h3 title="Дождевик мужской Squall, желтый"><?=$item['NAME']?></h3></a>
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
	<?php
	unset($item, $actualItem, $minOffer, $itemIds, $jsParams);
}
