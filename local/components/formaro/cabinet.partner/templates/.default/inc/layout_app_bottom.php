<?php
/**
 * Закрывающая часть каркаса — footer, модалки confirm/result (обязательны
 * на каждой странице, где common.js может вызвать showConfirm/showResult —
 * см. cabinet-html/CLAUDE.md про "тихий фолбэк на нативный confirm()"),
 * инициализация топбара/счётчиков и bootstrap-объект для common.js.
 *
 * @var array $arResult
 * @var string $pageTitle
 * @var string[] $pageScripts имена файлов из assets/js/, подключаемые после common.js
 */
global $APPLICATION;
$assetsJsPath = '/local/components/formaro/cabinet.partner/templates/.default/assets/js';
?>
        </div>

        <footer class="app-footer" id="footerPlaceholder">
            <?php include __DIR__ . '/footer.php'; ?>
        </footer>
    </div>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title confirm-title">Подтвердите действие</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><p class="confirm-message mb-0">Вы уверены?</p></div>
  <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button><button type="button" class="btn btn-primary confirm-ok-btn">Подтвердить</button></div>
</div></div></div>

<div class="modal fade" id="resultModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title result-title">Готово</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body d-flex align-items-center gap-3"><div class="result-icon fs-2"></div><p class="result-message mb-0">Действие выполнено.</p></div>
  <div class="modal-footer"><button type="button" class="btn btn-primary" data-bs-dismiss="modal">Ок</button></div>
</div></div></div>

<script>
window.CABINET_BOOTSTRAP = {
    partnerId: <?= (int)($arResult['PARTNER_ID'] ?? 0) ?>,
    ajaxUrl: <?= json_encode($arResult['SEF_FOLDER'], JSON_UNESCAPED_SLASHES) ?>,
    cabinetUrl: <?= json_encode($arResult['SEF_FOLDER'], JSON_UNESCAPED_SLASHES) ?>,
    assetsUrl: <?= json_encode('/local/components/formaro/cabinet.partner/templates/.default/assets', JSON_UNESCAPED_SLASHES) ?>
};
</script>
<?php foreach ($pageScripts ?? [] as $script): ?>
<script src="<?= $assetsJsPath . '/' . $script ?>"></script>
<?php endforeach; ?>
<script>$(function () { initCabinetChrome(<?= json_encode($pageTitle ?? '') ?>); });</script>
