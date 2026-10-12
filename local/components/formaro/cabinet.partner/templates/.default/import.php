<?php
/** @var array $arResult */
$activeKey = 'import';
$pageTitle = 'Импорт товаров';
require __DIR__ . '/inc/layout_app_top.php';
?>
<div id="importStepFile" class="card mb-3">
    <div class="card-header">1. Файл с товарами</div>
    <?php // Вкладки — как в «Скидках» и «Данных партнёра» (nav-tabs product-tabs под заголовком карточки). ?>
    <ul class="nav nav-tabs product-tabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#srcFile" type="button" data-src="file">Файл</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#srcUrl" type="button" data-src="url">Ссылка на фид</button></li>
    </ul>
    <div class="card-body">
        <div class="tab-content mb-3">
            <div class="tab-pane fade show active" id="srcFile">
                <?php // Как «Загрузить файлы» в «Документах» профиля: кнопка-label со скрытым input, выбор файла сразу запускает проверку. ?>
                <label class="btn btn-outline-primary mb-0" id="importFileBtn">
                    <svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"></path><path d="m17 8-5-5-5 5"></path><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path></svg> Загрузить файл
                    <input type="file" id="importFile" class="d-none" accept=".xml,.yml,.csv,.txt">
                </label>
            </div>
            <div class="tab-pane fade" id="srcUrl">
                <div class="input-group" style="max-width:640px;">
                    <input type="url" class="form-control" id="importUrl" placeholder="https://example.ru/yandex-market.xml">
                    <button class="btn btn-outline-primary" id="parseBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"></path><path d="m17 8-5-5-5 5"></path><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path></svg> Загрузить</button>
                </div>
            </div>
        </div>
        <div class="small text-muted-2 mb-3">
            <b>YML</b> (Яндекс.Маркет) — XML-выгрузка из 1С, CMS интернет-магазина или другой площадки: берём название,
            артикул (<code>vendorCode</code>, иначе id предложения), цену, остаток (<code>count</code>), категорию,
            описание, картинки и варианты (<code>group_id</code>); параметры (<code>param</code>) и остальные теги
            (вес, штрихкод, страна производства и т. п.) станут дополнительными свойствами товара. <a href="#" id="xmlTemplateBtn">Скачать шаблон YML</a>.<br>
            <b>CSV</b> из Excel (разделитель «;» или «,», UTF-8 или Windows-1251): первая строка — названия колонок
            «Название», «Артикул», «Цена», «Остаток», «Категория», «Описание», «Картинки», «Цвет», «Размер», «Бренд»;
            остальные колонки станут дополнительными свойствами товара. <a href="#" id="csvTemplateBtn">Скачать шаблон CSV</a>.<br>
            Товары сопоставляются с уже добавленными по артикулу: найденные обновятся, остальные будут созданы. До 10 000 товаров, файл до 50 МБ.
        </div>
    </div>
</div>

<div id="importStepMap" class="d-none">
    <div class="card mb-3" id="itemsCard">
        <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <span>2. Товары в файле</span>
            <div class="d-flex flex-wrap gap-2">
                <input type="text" class="form-control form-control-sm" id="itemsSearch" placeholder="Название или артикул" style="width:220px;">
                <select class="form-select form-select-sm" id="itemsFilter" style="width:auto;">
                    <option value="">Все</option><option value="new">Новые</option><option value="exists">Уже есть</option>
                </select>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="px-3 pt-3" id="importSummary"></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th style="width:56px;"></th><th>Название</th><th>Артикул</th><th>Категория в файле</th><th class="text-end">Цена</th><th class="text-end">Остаток</th><th>Импорт</th></tr></thead>
                    <tbody id="itemsBody"></tbody>
                </table>
            </div>
            <div class="table-footer-bar">
                <div class="tf-left"><span class="tf-count"></span></div>
                <div class="tf-right">
                    <div class="tf-pagesize">Показывать
                        <select class="form-select tf-pagesize-select">
                            <option value="20">20</option><option value="50">50</option><option value="100">100</option>
                        </select>
                    </div>
                    <div class="tf-pager"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">3. Категории и настройки</div>
        <div class="card-body">
            <h6 class="mb-2">Категории</h6>
            <div class="small text-muted-2 mb-2">Укажите, в какую категорию Reppa попадут товары из каждой категории файла. Нет подходящей — <a href="<?= $arResult['SEF_FOLDER'] ?>categories/edit/new/" target="_blank">создайте свою</a> и обновите список.</div>
            <div class="table-responsive mb-4">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Категория в файле</th><th class="text-end">Товаров</th><th style="min-width:260px;">Категория на Reppa</th></tr></thead>
                    <tbody id="mappingBody"></tbody>
                </table>
            </div>

            <h6 class="mb-2">Настройки</h6>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="optUpdate" checked>
                        <label class="form-check-label" for="optUpdate">Обновлять найденные по артикулу товары <span class="text-muted-2">(название, цена, остаток, описание, свойства)</span></label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="optImages" checked>
                        <label class="form-check-label" for="optImages">Загружать картинки по ссылкам из файла <span class="text-muted-2">(у обновляемых — только если фото ещё нет)</span></label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1" for="optStatus">Статус новых товаров</label>
                    <select class="form-select" id="optStatus"><option value="active">Активен</option><option value="hidden">Скрыт</option></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1" for="optStock">Остаток, если в файле не указан</label>
                    <input type="number" class="form-control" id="optStock" min="0" value="0">
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-success" id="runBtn">Импортировать</button>
                <button class="btn btn-light" id="cancelBtn">Отмена</button>
            </div>
        </div>
    </div>
</div>

<div id="importStepRun" class="card mb-3 d-none">
    <div class="card-header">4. Импорт</div>
    <div class="card-body">
        <div class="d-flex justify-content-between small mb-1"><span id="runStatus">Импортируем…</span><span id="runCounter"></span></div>
        <div class="progress mb-3" style="height:10px;"><div class="progress-bar bg-success" id="runBar" style="width:0%"></div></div>
        <div class="d-flex flex-wrap gap-4 mb-3">
            <div><div class="fs-4 fw-semibold" id="statCreated">0</div><div class="small text-muted-2">создано</div></div>
            <div><div class="fs-4 fw-semibold" id="statUpdated">0</div><div class="small text-muted-2">обновлено</div></div>
            <div><div class="fs-4 fw-semibold" id="statSkipped">0</div><div class="small text-muted-2">пропущено</div></div>
            <div><div class="fs-4 fw-semibold text-danger" id="statErrors">0</div><div class="small text-muted-2">ошибок</div></div>
        </div>
        <div id="runErrors" class="d-none mb-3">
            <h6 class="mb-2">Проблемы</h6>
            <div class="table-responsive" style="max-height:320px;">
                <table class="table table-sm mb-0"><tbody id="runErrorsBody"></tbody></table>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 d-none" id="runDoneBtns">
            <a href="<?= $arResult['SEF_FOLDER'] ?>products/" class="btn btn-primary">К товарам</a>
            <button class="btn btn-light" id="againBtn">Импортировать ещё файл</button>
        </div>
        <button class="btn btn-outline-danger btn-sm" id="stopBtn">Остановить</button>
    </div>
</div>
<?php
$pageScripts = ['import.js'];
require __DIR__ . '/inc/layout_app_bottom.php';
