<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Шапка письма — выполняется при сборке письма (EventMessageThemeCompiler):
 * $arParams — поля события, #SITE_URL# — Formaro\Cabinet\Mail\Mailer.
 *
 * Вёрстка — таблицами и инлайн-стилями: почтовые клиенты не понимают
 * внешние стили и flex. Логотип — PNG двойной плотности (SVG показывают
 * не все клиенты).
 */
$siteUrl = (string)($arParams['SITE_URL'] ?? '');
if ($siteUrl === '' && \Bitrix\Main\Loader::includeModule('formaro.cabinet')) {
    $siteUrl = \Formaro\Cabinet\Settings\MailSettings::siteUrl();
}
$siteUrl = htmlspecialcharsbx($siteUrl);
?><!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="<?= SITE_CHARSET ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<title>Formaro</title>
</head>
<body style="margin:0;padding:0;background:#f1f3f6;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f1f3f6;">
<tr>
<td align="center" style="padding:24px 12px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;background:#ffffff;border-radius:8px;">
<tr>
<td style="padding:24px 32px;border-bottom:3px solid #1976d2;">
<a href="<?= $siteUrl ?>/" target="_blank" style="text-decoration:none;"><img src="<?= $siteUrl ?>/local/templates/mail_formaro/images/logo.png" width="178" height="45" alt="formaro" style="display:block;border:0;width:178px;height:45px;"></a>
</td>
</tr>
<tr>
<td style="padding:32px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:23px;color:#333333;">
