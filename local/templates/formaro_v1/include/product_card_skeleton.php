<?php
/**
 * Скелетон карточки товара (.product-card--skeleton) — заглушка, пока
 * товары подгружаются через AJAX. Клиентская копия —
 * FormaroProductCard.skeletonHtml() в js/catalog-common.js, стили — в
 * styles.css. Обёртку (.product-item / .carousel-cell) с классом
 * product-skeleton добавляет вызывающий шаблон — по нему скрипты
 * убирают заглушки, когда пришли товары.
 */
?>
<div class="product-card product-card--skeleton" aria-hidden="true">
    <div class="product-card__img">
        <div class="embed-responsive embed-responsive-1by1 skeleton-img"></div>
    </div>
    <div class="product-card__info">
        <div class="skeleton-title">
            <div class="skeleton-line" style="width: 95%"></div>
            <div class="skeleton-line" style="width: 70%"></div>
        </div>
        <div class="skeleton-line skeleton-price"></div>
        <div class="skeleton-line skeleton-meta"></div>
        <div class="skeleton-line skeleton-meta" style="width: 45%"></div>
    </div>
    <div class="product-card__actions">
        <div class="skeleton-action"></div>
    </div>
</div>
