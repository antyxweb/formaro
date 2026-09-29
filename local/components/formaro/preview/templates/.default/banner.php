<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
// Плашка над предпросмотром: страница собрана из формы, не из сайта.
?>
<div class="preview-banner" role="status">
    <div class="container-fluid d-flex align-items-center">
        <svg width="16" height="16" class="mr-2 flex-shrink-0"><use xlink:href="#icon-info"></use></svg>
        <span><b>Предпросмотр.</b> Так страница будет выглядеть на сайте — с изменениями из формы, даже несохранёнными. Покупатели её не видят.</span>
        <button type="button" class="preview-banner-close ml-auto" onclick="window.close()">Закрыть</button>
    </div>
</div>
