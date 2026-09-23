<?php
/**
 * Компонент рендерит только содержимое <body> (сайдбар/топбар/контент) —
 * <html><head> открывает/закрывает шаблон САЙТА (header.php/footer.php,
 * которые вызывает /cabinet/index.php). Поэтому CSS/JS подключаем через
 * $APPLICATION, а не своими <link>/<script> — так они попадут в тот же
 * ShowHead(), что и стили сайта.
 *
 * Раздел /cabinet/ использует отдельный урезанный шаблон сайта
 * (local/templates/cabinet_v1/) — без публичного меню/футера formaro_v1,
 * подключается кодом из cabinet/index.php (SetTemplateName до header.php).
 *
 * @var bool $needRichText подключить trumbowyg (страницы с полем "полное
 *                          описание" — партнёр, категория, товар)
 * @var bool $needDatePicker подключить flatpickr (страницы с фильтром/полем
 *                            по датам — список заказов)
 */
global $APPLICATION;

if (!function_exists('cabinetAssetUrl')) {
    /** Добавляет ?v=filemtime к локальному ассету кабинета, чтобы правка
     *  JS/CSS сразу подхватывалась у всех, а не только после ручного
     *  hard refresh (см. комментарий ниже про common.js/theme.css). Для
     *  CDN-ссылок не используется — версия там уже зашита в сам URL. */
    function cabinetAssetUrl(string $relativePath): string
    {
        $absolutePath = $_SERVER['DOCUMENT_ROOT'] . $relativePath;
        $version = file_exists($absolutePath) ? filemtime($absolutePath) : time();

        return $relativePath . '?v=' . $version;
    }
}

$assetsPath = '/local/components/formaro/cabinet.partner/templates/.default/assets';

$APPLICATION->SetAdditionalCSS('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
$APPLICATION->SetAdditionalCSS('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css');
if (!empty($needRichText)) {
    $APPLICATION->SetAdditionalCSS('https://cdn.jsdelivr.net/npm/trumbowyg@2.27.3/dist/ui/trumbowyg.min.css');
}
if (!empty($needDatePicker)) {
    $APPLICATION->SetAdditionalCSS('https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
}
// ?v=filemtime — без этого браузер партнёра после любой правки common.js/
// theme.css продолжает молча использовать старую закэшированную версию,
// пока сам не сделает hard refresh (реально приводило к "фича не работает",
// хотя на сервере код уже обновлён).
$APPLICATION->SetAdditionalCSS(cabinetAssetUrl($assetsPath . '/css/theme.css'));

$APPLICATION->AddHeadScript('https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js');
$APPLICATION->AddHeadScript('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js');
if (!empty($needRichText)) {
    $APPLICATION->AddHeadScript('https://cdn.jsdelivr.net/npm/trumbowyg@2.27.3/dist/trumbowyg.min.js');
    $APPLICATION->AddHeadScript('https://cdn.jsdelivr.net/npm/trumbowyg@2.27.3/dist/langs/ru.min.js');
}
if (!empty($needDatePicker)) {
    $APPLICATION->AddHeadScript('https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js');
    $APPLICATION->AddHeadScript('https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ru.js');
}
$APPLICATION->AddHeadScript(cabinetAssetUrl($assetsPath . '/js/common.js'));
