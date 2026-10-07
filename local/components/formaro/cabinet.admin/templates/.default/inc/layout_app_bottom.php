<?php
/**
 * Закрывающая часть каркаса: футер, модалки confirm/result (common.js),
 * скрипты страницы. $pageScripts — файлы из assets/js/ этого шаблона;
 * «partner:имя.js» — общий скрипт кабинета партнёра (тот же экран).
 *
 * @var string $pageTitle
 * @var string[] $pageScripts
 */
$adminJsPath = '/local/components/formaro/cabinet.admin/templates/.default/assets/js';
$partnerJsPath = '/local/components/formaro/cabinet.partner/templates/.default/assets/js';
?>
        </div>

        <footer class="app-footer" id="footerPlaceholder">
            <?php include $_SERVER['DOCUMENT_ROOT'] . '/local/components/formaro/cabinet.partner/templates/.default/inc/footer.php'; ?>
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

<?php // Причина отклонения (модерация, выплаты) — adminAskReason() в admin.js. ?>
<div class="modal fade" id="reasonModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title reason-title">Причина отклонения</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <label class="form-label reason-label" for="reasonInput">Причина — её увидит партнёр</label>
    <textarea class="form-control" id="reasonInput" rows="4" maxlength="1000"></textarea>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button><button type="button" class="btn btn-danger reason-ok-btn">Отклонить</button></div>
</div></div></div>

<script src="<?= cabinetAssetUrl($adminJsPath . '/admin.js') ?>"></script>
<?php foreach ($pageScripts ?? [] as $script): ?>
<?php $src = strpos($script, 'partner:') === 0 ? $partnerJsPath . '/' . substr($script, 8) : $adminJsPath . '/' . $script; ?>
<script src="<?= cabinetAssetUrl($src) ?>"></script>
<?php endforeach; ?>
<script>$(function () { initCabinetChrome(<?= json_encode($pageTitle ?? '') ?>); });</script>
