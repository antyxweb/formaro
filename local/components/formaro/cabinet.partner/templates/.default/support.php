<?php
/** @var array $arResult */
$activeKey = 'support';
$pageTitle = 'Техподдержка — Formaro Partner';
require __DIR__ . '/inc/layout_app_top.php';
?>
<div class="filter-bar d-flex flex-wrap gap-2 align-items-end justify-content-between">
    <div class="d-flex flex-wrap gap-2 align-items-end">
        <div><label class="form-label small mb-1">Поиск по теме</label>
            <input type="text" class="form-control" id="qInput" placeholder="Например: комиссия" style="min-width:220px;"></div>
        <div><label class="form-label small mb-1">Статус</label>
            <select class="form-select" id="statusFilter">
                <option value="">Все статусы</option>
                <option value="open">Открыт</option>
                <option value="answered">Отвечен</option>
                <option value="closed">Закрыт</option>
            </select></div>
        <button class="btn btn-outline-secondary" id="findBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 21-4.34-4.34"></path><circle cx="11" cy="11" r="8"></circle></svg></button>
        <button class="btn btn-outline-secondary filter-clear-btn" id="clearBtn" title="Сбросить фильтры"><i class="bi bi-x-lg"></i></button>
    </div>
    <button class="btn btn-success" id="newTicketBtn"><svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg> Новое обращение</button>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle" id="ticketsTable">
                        <thead><tr><th>Тема</th><th>Статус</th><th>Сообщений</th><th>Дата создания</th><th class="text-end">Действия</th></tr></thead>
                        <tbody id="ticketsBody"><tr><td colspan="5" class="text-center text-muted-2 py-4">Загрузка…</td></tr></tbody>
                    </table>
                </div>
                <div class="table-footer-bar">
                    <div class="tf-left"><span class="tf-count"></span></div>
                    <div class="tf-right">
                        <div class="tf-pagesize">Показывать
                            <select class="form-select tf-pagesize-select">
                                <option value="20">20</option><option value="50">50</option><option value="100">100</option><option value="500">500</option>
                            </select>
                        </div>
                        <div class="tf-pager"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 sticky-side" id="marketplaceContacts">
        <div class="card mb-3"><div class="card-body py-4 text-center text-muted-2">Загрузка…</div></div>
    </div>
</div>

<div class="modal fade" id="newTicketModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Новое обращение в поддержку</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <label class="form-label">Тема</label>
    <input type="text" class="form-control mb-3" id="newTicketSubject">
    <label class="form-label">Сообщение</label>
    <textarea class="form-control mb-3" id="newTicketMessage" rows="4"></textarea>
    <label class="btn btn-outline-secondary mb-0">
      <svg class="ic-inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"></path><path d="m17 8-5-5-5 5"></path><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path></svg> Выбрать файлы
      <input type="file" class="d-none" id="newTicketFile" multiple>
    </label>
    <label class="form-label d-block mt-1 mb-0">Прикрепить файлы (необязательно)</label>
    <div id="newTicketFilesPreview" class="small text-muted-2 mt-1"></div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button><button type="button" class="btn btn-primary" id="newTicketConfirmBtn">Отправить</button></div>
</div></div></div>
<?php
$pageScripts = ['support.js'];
require __DIR__ . '/inc/layout_app_bottom.php';
