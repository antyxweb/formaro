<?php
/** @var array $arResult */
$activeKey = 'products';
$pageTitle = 'Товар — Formaro Partner';
$needRichText = true;
$routeId = $arResult['VARIABLES']['ID'] ?? 'new';
require __DIR__ . '/inc/layout_app_top.php';
?>
<a href="<?= $arResult['SEF_FOLDER'] ?>products/" class="btn btn-link px-0 mb-2"><i class="bi bi-arrow-left"></i> К списку товаров</a>

<div class="card">
    <div class="card-header"><span id="formTitle">Новый товар</span></div>
    <ul class="nav nav-tabs product-tabs" id="productTabs">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabMain" type="button">Основное</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabVariants" type="button">Варианты <span class="side-count" id="variantCountBadge" style="display:none;">0</span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabDiscounts" type="button">Скидки <span class="side-count" id="discountCountBadge" style="display:none;">0</span></button></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="tabMain">
            <div class="card-body">
                <form id="productForm" onsubmit="return false;">
                    <input type="hidden" id="f_id">

                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label class="form-label">Название товара *</label>
                            <input type="text" class="form-control" id="f_name" required maxlength="200" data-validate="text">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Код для ссылки</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="f_code" placeholder="генерируется автоматически" data-validate="slug">
                                <button type="button" class="btn btn-outline-secondary code-chain-btn active" id="codeChainBtn" title="Код генерируется автоматически из названия — нажмите, чтобы редактировать вручную">
                                    <svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 17H7A5 5 0 0 1 7 7h2"></path><path d="M15 7h2a5 5 0 1 1 0 10h-2"></path><line x1="8" x2="16" y1="12" y2="12"></line></svg>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Статус</label>
                            <select class="form-select" id="f_status"><option value="active">Активен</option><option value="hidden">Скрыт</option></select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Категории *</label>
                        <div id="f_category_tree"></div>
                        <div class="form-text">Можно выбрать несколько. При выборе категории её подкатегории отмечаются автоматически.</div>
                    </div>

                    <div class="row align-items-start mb-1">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Цена</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="f_price" min="0" step="1">
                                <span class="input-group-text">₽</span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Остаток на складе</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="f_stock" min="0" step="1">
                                <span class="input-group-text">шт.</span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label d-block">&nbsp;</label>
                            <div class="form-check form-switch preorder-check">
                                <input type="checkbox" class="form-check-input" id="f_preorder">
                                <label class="form-check-label" for="f_preorder">Предзаказ</label>
                            </div>
                            <div class="form-text">Можно заказывать при 0 остатка</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block">Метки</label>
                        <div class="tag-toggle-group" id="tagToggleGroup">
                            <label class="tag-toggle"><input type="checkbox" class="d-none" id="tag_new" value="new"><span>Новинка</span></label>
                            <label class="tag-toggle"><input type="checkbox" class="d-none" id="tag_bestseller" value="bestseller"><span>Топ продаж</span></label>
                            <label class="tag-toggle"><input type="checkbox" class="d-none" id="tag_sale" value="sale"><span>Распродажа</span></label>
                            <span id="customTagsWrap"></span>
                        </div>
                        <div class="input-group mt-2" style="max-width:320px;">
                            <input type="text" class="form-control form-control-sm" id="customTagInput" placeholder="Своя метка…" maxlength="30">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addCustomTagBtn">
                                <svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Краткое описание</label>
                        <textarea class="form-control autoheight-input" id="f_short_desc" rows="2" maxlength="300"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Подробное описание</label>
                        <textarea id="f_full_desc"></textarea>
                    </div>

                    <hr>
                    <h6 class="mb-3">Основные свойства</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Артикул</label><input type="text" class="form-control" id="f_sku"></div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Цвет</label>
                            <div class="color-input-wrap">
                                <span class="color-swatch" id="f_color_swatch"></span>
                                <input type="text" class="form-control" id="f_color" autocomplete="off">
                                <div class="gsearch-dropdown" id="colorSuggestions"></div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3"><label class="form-label">Размер</label><input type="text" class="form-control" id="f_size"></div>
                    </div>

                    <div class="mb-2 d-flex justify-content-between align-items-center">
                        <label class="form-label mb-0">Дополнительные свойства</label>
                        <button type="button" class="btn btn-primary" id="addPropBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg> Добавить свойство</button>
                    </div>
                    <div class="props-table mb-3">
                        <div class="props-table-head"><div>Название</div><div>Значение</div><div></div></div>
                        <div id="propsContainer"></div>
                    </div>

                    <hr>
                    <h6 class="mb-2">Картинка превью</h6>
                    <div class="img-field">
                        <div id="previewImg" class="thumb-detail bg-thumb mb-2" style="background-image:url('https://placehold.co/240x180?text=%20');"></div>
                        <div class="img-actions">
                            <label class="btn btn-outline-secondary mb-0">
                                <svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"></path><path d="m17 8-5-5-5 5"></path><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path></svg> Загрузить
                                <input type="file" class="d-none" id="previewFile" accept="image/*">
                            </label>
                            <button type="button" class="btn btn-outline-danger" id="previewRemoveBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
                        </div>
                    </div>

                    <h6 class="mb-2">Галерея (детальные фото)</h6>
                    <div id="galleryContainer" class="mb-2"></div>
                    <label class="btn btn-outline-secondary mb-0">
                        <svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"></path><path d="m17 8-5-5-5 5"></path><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path></svg> Добавить фото
                        <input type="file" class="d-none" id="galleryFile" accept="image/*" multiple>
                    </label>
                </form>
            </div>
        </div>
        <div class="tab-pane fade" id="tabVariants">
            <div class="card-body">
                <div id="variantNewHint" class="empty-state" style="display:none;">
                    <svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 17H7A5 5 0 0 1 7 7h2"></path><path d="M15 7h2a5 5 0 1 1 0 10h-2"></path><line x1="8" x2="16" y1="12" y2="12"></line></svg>Сначала сохраните товар (кнопка «Сохранить» или «Применить») — потом можно будет объединить его с другими вариантами.
                </div>
                <div id="variantEditor" style="display:none;">
                    <p class="text-muted-2 small">Объедините этот товар с другими вариантами (например, других цветов или размеров одной модели) — на витрине покупатель сможет переключаться между ними на карточке товара.</p>
                    <div id="variantGroupEmpty" class="empty-state" style="display:none;">
                        <svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 17H7A5 5 0 0 1 7 7h2"></path><path d="M15 7h2a5 5 0 1 1 0 10h-2"></path><line x1="8" x2="16" y1="12" y2="12"></line></svg>Товар пока не входит в группу вариантов.
                    </div>
                    <div id="variantGroupList" class="mb-3"></div>
                    <label class="form-label">Добавить товар в группу вариантов</label>
                    <div class="position-relative">
                        <input type="text" class="form-control" id="variantSearch" placeholder="Начните вводить название или артикул (от 3 символов)…" autocomplete="off">
                        <div class="gsearch-dropdown" id="variantSearchResults"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tabDiscounts">
            <div class="card-body">
                <div id="productDiscountsNewHint" class="empty-state" style="display:none;">
                    <svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" x2="5" y1="5" y2="19"></line><circle cx="6.5" cy="6.5" r="2.5"></circle><circle cx="17.5" cy="17.5" r="2.5"></circle></svg>Сначала сохраните товар — потом здесь будут видны применимые к нему скидки.
                </div>
                <div id="productDiscountsWrap" style="display:none;">
                    <p class="text-muted-2 small">Раздел «Скидки и купоны» ещё не подключён к реальной базе (появится в одной из следующих фаз) — здесь пока всегда будет пусто.</p>
                    <div id="productDiscountsEmpty" class="empty-state" style="display:none;">
                        <svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" x2="5" y1="5" y2="19"></line><circle cx="6.5" cy="6.5" r="2.5"></circle><circle cx="17.5" cy="17.5" r="2.5"></circle></svg>На этот товар пока не действует ни одна скидка.
                    </div>
                    <div id="productDiscountsList" class="mb-3"></div>
                    <a href="#" class="btn btn-primary disabled" id="createDiscountBtn" title="Появится позже" tabindex="-1" aria-disabled="true">
                        <svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg> Создать скидку
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="detail-actionbar">
        <button type="button" class="btn btn-primary" id="saveBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"></path><path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"></path><path d="M7 3v4a1 1 0 0 0 1 1h7"></path></svg> Сохранить</button>
        <button type="button" class="btn btn-outline-primary" id="applyBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg> Применить</button>
        <a href="<?= $arResult['SEF_FOLDER'] ?>products/" class="btn btn-light"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg> Отмена</a>
        <button type="button" class="btn btn-outline-danger d-none" id="deleteBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg> Удалить</button>
        <div class="flex-spacer"></div>
        <a href="#" class="btn btn-outline-secondary disabled" id="previewBtn" title="Появится позже — публичная витрина каталога ещё не подключена" tabindex="-1" aria-disabled="true"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"></path><circle cx="12" cy="12" r="3"></circle></svg> Предпросмотр</a>
    </div>
</div>
<script>var ROUTE_ID = <?= json_encode($routeId) ?>;</script>
<?php
$pageScripts = ['product-detail.js'];
require __DIR__ . '/inc/layout_app_bottom.php';
