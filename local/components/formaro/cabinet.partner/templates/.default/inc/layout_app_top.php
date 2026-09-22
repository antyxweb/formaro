<?php
/**
 * Открывающая часть "рабочего" (авторизованного) каркаса — сайдбар + топбар,
 * дальше action-шаблон рендерит содержимое .app-content сам и подключает
 * layout_app_bottom.php.
 *
 * @var string $activeKey пункт меню, который подсветить (см. inc/sidebar.php)
 * @var string $pageTitle заголовок страницы (топбар + <title>)
 * @var bool $needRichText см. inc/head_assets.php
 * @var CabinetPartnerComponent $component передаётся из action-шаблона как $this->getComponent() либо $arResult
 */
global $APPLICATION;

$cabinetUrl = $arResult['SEF_FOLDER'];

$APPLICATION->SetTitle(($pageTitle ?? '') ?: 'Кабинет партнёра');

// ВАЖНО: CABINET_BOOTSTRAP должен попасть в <head> ДО common.js (который
// читает window.CABINET_BOOTSTRAP.partnerId в CURRENT_PARTNER_ID один раз,
// при загрузке скрипта). common.js подключается через head_assets.php ниже
// как <script src> в <head> — если бы CABINET_BOOTSTRAP объявлялся в конце
// <body> (как было раньше), common.js читал бы ещё не существующий
// window.CABINET_BOOTSTRAP и CURRENT_PARTNER_ID навсегда застревал бы на 0
// (баг был найден вручную: "Только мои" у категорий не находил ничего,
// хотя партнёр реально владел записью).
$cabinetBootstrapScript = '<script>window.CABINET_BOOTSTRAP = '
    . json_encode([
        'partnerId' => (int)($arResult['PARTNER_ID'] ?? 0),
        'ajaxUrl' => $arResult['SEF_FOLDER'],
        'cabinetUrl' => $arResult['SEF_FOLDER'],
        'assetsUrl' => '/local/components/formaro/cabinet.partner/templates/.default/assets',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    . ';</script>';
$APPLICATION->AddHeadString($cabinetBootstrapScript, true);

require __DIR__ . '/head_assets.php';

$themeInitScript = <<<'HTML'
<script>(function(){var m=localStorage.getItem('formaro_theme_mode')||'auto';var r=(m==='light'||m==='dark')?m:((window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches)?'dark':'light');document.documentElement.setAttribute('data-theme',r);})();</script>
HTML;
$APPLICATION->AddHeadString($themeInitScript, true);
?>
<div id="appShell">
    <aside class="sidebar" id="sidebarPlaceholder">
        <?php include __DIR__ . '/sidebar.php'; ?>
    </aside>
    <div class="app-main">
        <header class="topbar" id="topbarPlaceholder">
            <?php include __DIR__ . '/topbar.php'; ?>
        </header>

        <div class="app-content">
