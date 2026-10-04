<?php
/**
 * Карточка партнёра — модерация: данные компании (что на сайте и что
 * партнёр прислал на проверку — изменённые поля подсвечены), одобрить /
 * отклонить с причиной, показ на сайте, учётные записи, переходы в
 * разделы кабинета с фильтром по этому партнёру.
 *
 * @var array $arResult
 */
$activeKey = 'partners';
$pageTitle = 'Партнёр';
$routeId = (int)($arResult['VARIABLES']['ID'] ?? 0);
require __DIR__ . '/inc/layout_app_top.php';
$base = $arResult['SEF_FOLDER'];
?>
<a href="<?= $base ?>partners/" class="btn btn-link px-0 mb-2"><i class="bi bi-arrow-left"></i> К списку партнёров</a>

<div class="card mb-3">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="moderation-thumb" id="pLogo"></div>
            <div>
                <h4 class="mb-1" id="pName">—</h4>
                <div class="text-muted-2 small" id="pNameFull"></div>
                <div class="mt-1" id="pStatus"></div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="activeToggle">
                <label class="form-check-label" for="activeToggle">Показывать на сайте</label>
            </div>
            <a href="#" class="btn btn-outline-secondary d-none" id="publicLink" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Страница на сайте</a>
        </div>
    </div>
</div>

<div class="alert alert-danger d-none reason-note" id="rejectNote"></div>

<div class="card mb-3">
    <div class="card-header">
        <span id="dataTitle">Данные компании</span>
        <div class="d-flex gap-2" id="moderationActions">
            <button type="button" class="btn btn-success btn-sm" id="approveBtn"><i class="bi bi-check-lg"></i> Одобрить</button>
            <button type="button" class="btn btn-outline-danger btn-sm" id="rejectBtn"><i class="bi bi-slash-circle"></i> Отклонить</button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 moderation-diff">
                <thead><tr><th style="width:22%">Поле</th><th>На сайте</th><th id="pendingHead">Прислано на проверку</th></tr></thead>
                <tbody id="diffBody"><tr><td colspan="3" class="text-center text-muted-2 py-4">Загрузка…</td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><span>Учётные записи</span></div>
            <div class="card-body p-0">
                <table class="table mb-0"><tbody id="usersBody"></tbody></table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><span>Разделы по партнёру</span></div>
            <div class="card-body d-flex flex-wrap gap-2">
                <a class="btn btn-outline-secondary" href="<?= $base ?>categories/?partner=<?= $routeId ?>">Категории</a>
                <a class="btn btn-outline-secondary" href="<?= $base ?>orders/?partner=<?= $routeId ?>">Заказы</a>
                <a class="btn btn-outline-secondary" href="<?= $base ?>finance/?partner=<?= $routeId ?>">Финансы</a>
                <a class="btn btn-outline-secondary" href="<?= $base ?>news/?partner=<?= $routeId ?>">Новости</a>
                <a class="btn btn-outline-secondary" href="<?= $base ?>support/?partner=<?= $routeId ?>">Обращения</a>
                <a class="btn btn-outline-secondary" href="<?= $base ?>chat/?partner=<?= $routeId ?>">Чаты с клиентами</a>
            </div>
        </div>
    </div>
</div>
<script>var ROUTE_ID = <?= $routeId ?>;</script>
<?php
$pageScripts = ['partner-detail.js'];
require __DIR__ . '/inc/layout_app_bottom.php';
