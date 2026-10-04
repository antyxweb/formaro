<?php
/**
 * Открывающая часть каркаса кабинета администратора — как у кабинета
 * партнёра (formaro:cabinet.partner, inc/layout_app_top.php): стили и
 * common.js — оттуда же (head_assets.php партнёра), свои — сайдбар/топбар
 * и admin.js. В CABINET_BOOTSTRAP — список партнёров для фильтров
 * (partners: [{id, name}]), partnerId = 0 (администратор не партнёр).
 *
 * @var array $arResult
 * @var string $activeKey пункт меню (inc/sidebar.php)
 * @var string $pageTitle заголовок страницы
 * @var bool $needRichText
 * @var bool $needDatePicker
 */

use Formaro\Cabinet\Repository\PartnerRepository;

global $APPLICATION, $USER;

$cabinetUrl = $arResult['SEF_FOLDER'];
$APPLICATION->SetTitle(($pageTitle ?? '') ?: 'Кабинет администратора');

$adminPartners = array_map(static fn(array $p) => [
    'id' => $p['id'],
    'name' => $p['name'],
    'active' => $p['active'],
    'verification_status' => $p['verification_status'],
], (new PartnerRepository())->listAll());
$adminUserName = trim($USER->GetFirstName() . ' ' . $USER->GetLastName()) ?: $USER->GetLogin();

$APPLICATION->AddHeadString('<script>window.CABINET_BOOTSTRAP = ' . json_encode([
    'partnerId' => 0,
    'isAdmin' => true,
    'ajaxUrl' => $cabinetUrl,
    'cabinetUrl' => $cabinetUrl,
    'assetsUrl' => '/local/components/formaro/cabinet.partner/templates/.default/assets',
    'partners' => $adminPartners,
    'userName' => $adminUserName,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ';</script>', true);

require $_SERVER['DOCUMENT_ROOT'] . '/local/components/formaro/cabinet.partner/templates/.default/inc/head_assets.php';
$APPLICATION->SetAdditionalCSS(cabinetAssetUrl('/local/components/formaro/cabinet.admin/templates/.default/assets/css/admin.css'));

$APPLICATION->AddHeadString(<<<'HTML'
<script>(function(){var m=localStorage.getItem('formaro_theme_mode')||'auto';var r=(m==='light'||m==='dark')?m:((window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches)?'dark':'light');document.documentElement.setAttribute('data-theme',r);})();</script>
HTML, true);
?>
<div id="appShell" class="admin-shell">
    <aside class="sidebar" id="sidebarPlaceholder">
        <?php include __DIR__ . '/sidebar.php'; ?>
    </aside>
    <div class="app-main">
        <header class="topbar" id="topbarPlaceholder">
            <?php include __DIR__ . '/topbar.php'; ?>
        </header>

        <div class="app-content">
