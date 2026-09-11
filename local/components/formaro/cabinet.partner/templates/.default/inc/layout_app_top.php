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
