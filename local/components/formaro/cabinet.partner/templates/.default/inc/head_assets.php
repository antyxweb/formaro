<?php
/**
 * Компонент рендерит только содержимое <body> (сайдбар/топбар/контент) —
 * <html><head> открывает/закрывает шаблон САЙТА (header.php/footer.php,
 * которые вызывает /cabinet/index.php). Поэтому CSS/JS подключаем через
 * $APPLICATION, а не своими <link>/<script> — так они попадут в тот же
 * ShowHead(), что и стили сайта.
 *
 * ВАЖНО (см. отчёт после реализации): пока /cabinet/ использует общий шаблон
 * formaro_v1, публичное меню/футер сайта будет показываться ПОВЕРХ этого
 * контента — визуально задвоение шапки, пока не подключат отдельный/урезанный
 * шаблон для раздела (см. план, раздел "Фронтенд").
 *
 * @var bool $needRichText подключить trumbowyg (только на страницах с полем
 *                          "полное описание" — партнёр, категория, товар)
 */
global $APPLICATION;

$assetsPath = '/local/components/formaro/cabinet.partner/templates/.default/assets';

$APPLICATION->SetAdditionalCSS('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
$APPLICATION->SetAdditionalCSS('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css');
if (!empty($needRichText)) {
    $APPLICATION->SetAdditionalCSS('https://cdn.jsdelivr.net/npm/trumbowyg@2.27.3/dist/ui/trumbowyg.min.css');
}
$APPLICATION->SetAdditionalCSS($assetsPath . '/css/theme.css');

$APPLICATION->AddHeadScript('https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js');
$APPLICATION->AddHeadScript('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js');
if (!empty($needRichText)) {
    $APPLICATION->AddHeadScript('https://cdn.jsdelivr.net/npm/trumbowyg@2.27.3/dist/trumbowyg.min.js');
    $APPLICATION->AddHeadScript('https://cdn.jsdelivr.net/npm/trumbowyg@2.27.3/dist/langs/ru.min.js');
}
$APPLICATION->AddHeadScript($assetsPath . '/js/common.js');
