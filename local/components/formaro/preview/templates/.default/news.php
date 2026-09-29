<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arResult */

// Новость — шаблоном детальной страницы новостей сайта (news/news →
// news.detail) с данными из формы: картинка, текст, партнёр и товары.
include __DIR__ . '/banner.php';
require __DIR__ . '/render.php';
$view = new FormaroPreviewTemplate();
$detail = $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/components/bitrix/news/news/bitrix/news.detail/.default';
?>
<section class="section pt-4">
    <div class="section-body">
        <div class="container-fluid">
            <div class="row news-detail position-relative">
                <?php
                $view->render($detail . '/template.php', $arResult['NEWS'], $arResult['NEWS_PARAMS']);
                $view->render($detail . '/component_epilog.php', $arResult['NEWS'], $arResult['NEWS_PARAMS']);
                ?>
            </div>
        </div>
    </div>
</section>
