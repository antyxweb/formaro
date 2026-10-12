<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Подвал письма: реквизиты площадки (настройки модуля formaro.cabinet,
 * вкладка «Реквизиты площадки» — Formaro\Cabinet\Settings\MarketplaceRequisites).
 * Пустые поля не выводятся.
 */
$requisites = [];
if (\Bitrix\Main\Loader::includeModule('formaro.cabinet')) {
    $requisites = \Formaro\Cabinet\Settings\MarketplaceRequisites::get();
}
$value = static fn(string $code) => htmlspecialcharsbx((string)($requisites[$code] ?? ''));
$name = $value('name_full') ?: $value('name_short');
$ids = array_filter([
    $value('inn') !== '' ? 'ИНН ' . $value('inn') : '',
    $value('kpp') !== '' ? 'КПП ' . $value('kpp') : '',
    $value('ogrn') !== '' ? \Formaro\Cabinet\Settings\MarketplaceRequisites::ogrnLabel($requisites['ogrn']) . ' ' . $value('ogrn') : '',
]);
$address = $value('legal_address') ?: $value('actual_address');
$contacts = array_filter([
    $value('phone') !== '' ? '<a href="tel:' . preg_replace('/[^\d+]/', '', $requisites['phone']) . '" style="color:#6b7280;text-decoration:none;">' . $value('phone') . '</a>' : '',
    $value('email') !== '' ? '<a href="mailto:' . $value('email') . '" style="color:#1976d2;text-decoration:none;">' . $value('email') . '</a>' : '',
]);
$siteUrl = htmlspecialcharsbx((string)($arParams['SITE_URL'] ?? ''));
$siteHost = htmlspecialcharsbx((string)parse_url((string)($arParams['SITE_URL'] ?? ''), PHP_URL_HOST));
?>
</td>
</tr>
<tr>
<td style="padding:24px 32px;background:#f8f9fb;border-top:1px solid #e5e7eb;border-radius:0 0 8px 8px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:18px;color:#6b7280;">
<p style="margin:0 0 12px;">Маркетплейс Reppa — товары из России от проверенных поставщиков.<?php if ($siteHost !== ''): ?> <a href="<?= $siteUrl ?>/" target="_blank" style="color:#1976d2;text-decoration:none;"><?= $siteHost ?></a><?php endif; ?></p>
<?php if ($contacts): ?><p style="margin:0 0 12px;"><?= implode(' &nbsp;·&nbsp; ', $contacts) ?></p><?php endif; ?>
<?php if ($name !== ''): ?><p style="margin:0;"><?= $name ?><?php if ($ids): ?><br><?= implode(', ', $ids) ?><?php endif; ?><?php if ($address !== ''): ?><br><?= $address ?><?php endif; ?></p><?php endif; ?>
<p style="margin:12px 0 0;color:#9ca3af;">Письмо отправлено автоматически, отвечать на него не нужно.</p>
</td>
</tr>
</table>
</td>
</tr>
</table>
</body>
</html>
