<?php
/**
 * Текстовая страница (разделы «Покупателям», «Партнерам», «О нас»,
 * «Поддержка»): слева белый блок с текстом (.text-page, css/pages.css),
 * справа меню раздела (text_page_bottom.php). Страница:
 *
 *   <?php include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/text_page_top.php'; ?>
 *   ...текст страницы (HTML)...
 *   <?php include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/include/text_page_bottom.php'; ?>
 */
?>
<section class="section pt-4">
    <div class="section-body">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 col-xl-9">
                    <article class="text-page bg-white py-4 px-3 px-lg-5 mb-4 mr-xl-5">
