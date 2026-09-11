<?php
/** @var string $pageTitle */
global $APPLICATION;

$APPLICATION->SetTitle(($pageTitle ?? '') ?: 'Вход в кабинет партнёра');
require __DIR__ . '/head_assets.php';

$themeInitScript = <<<'HTML'
<script>(function(){var m=localStorage.getItem('formaro_theme_mode')||'auto';var r=(m==='light'||m==='dark')?m:((window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches)?'dark':'light');document.documentElement.setAttribute('data-theme',r);})();</script>
HTML;
$APPLICATION->AddHeadString($themeInitScript, true);
?>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="brand-logo"><div class="f-mark"><span></span><span></span><span></span></div>formaro</div>
