<?php
/** @var array $arResult */
$activeKey = 'import';
$pageTitle = 'Импорт товаров';
require __DIR__ . '/inc/layout_app_top.php';
?>
<div id="importStepFile" class="card mb-3">
    <div class="card-header">1. Файл с товарами</div>
    <div class="card-body">
        <ul class="nav nav-pills mb-3" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#srcFile" type="button" data-src="file">Загрузить файл</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#srcUrl" type="button" data-src="url">Ссылка на фид</button></li>
        </ul>
        <div class="tab-content mb-3">
            <div class="tab-pane fade show active" id="srcFile">
                <input type="file" class="form-control" id="importFile" accept=".xml,.yml,.csv,.txt">
            </div>
            <div class="tab-pane fade" id="srcUrl">
                <input type="url" class="form-control" id="importUrl" placeholder="https://example.ru/yandex-market.xml">
            </div>
        </div>
        <div class="small text-muted-2 mb-3">
            <b>YML</b> (Яндекс.Маркет) — XML-выгрузка из 1С, CMS интернет-магазина или другой площадки: берём название,
            артикул (<code>vendorCode</code>, иначе id предложения), цену, остаток (<code>count</code>), категорию,
            описание, картинки, параметры (<code>param</code>) и варианты (<code>group_id</code>).<br>
            <b>CSV</b> из Excel (разделитель «;» или «,», UTF-8 или Windows-1251): первая строка — названия колонок
            «Название», «Артикул», «Цена», «Остаток», «Категория», «Описание», «Картинки», «Цвет», «Размер», «Бренд»;
            остальные колонки станут характеристиками товара.
            <a href="#" id="csvTemplateBtn">Скачать шаблон CSV</a>.<br>
            Товары сопоставляются с уже добавленными по артикулу: найденные обновятся, остальные будут созданы. До 10 000 товаров, файл до 50 МБ.
        </div>
        <button class="btn btn-primary" id="parseBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"></path><path d="m17 8-5-5-5 5"></path><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path></svg> Загрузить и проверить</button>
    </div>
</div>

<div id="importStepMap" class="d-none">
    <div class="card mb-3">
        <div class="card-header">2. Проверка и настройки</div>
        <div class="card-body">
            <div class="mb-3" id="importSummary"></div>
            <div class="table-responsive mb-4">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th style="width:56px;"></th><th>Название</th><th>Артикул</th><th class="text-end">Цена</th><th class="text-end">Остаток</th></tr></thead>
                    <tbody id="sampleBody"></tbody>
                </table>
                <div class="small text-muted-2 mt-1">Первые товары из файла</div>
            </div>

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
                        <label class="form-check-label" for="optUpdate">Обновлять найденные по артикулу товары <span class="text-muted-2">(название, цена, остаток, описание)</span></label>
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
    <div class="card-header">3. Импорт</div>
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
