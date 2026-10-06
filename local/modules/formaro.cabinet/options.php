<?php
/**
 * Настройки модуля formaro.cabinet (Настройки → Настройки модулей →
 * «Кабинет партнёра Formaro»): вкладка «Реквизиты площадки» — реквизиты
 * юрлица маркетплейса для счетов на оплату и страниц «Реквизиты» /
 * «Способ оплаты» (Formaro\Cabinet\Settings\MarketplaceRequisites).
 *
 * @var CMain $APPLICATION
 * @var CUser $USER
 */

use Bitrix\Main\Loader;
use Formaro\Cabinet\Settings\MarketplaceRequisites;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$moduleId = 'formaro.cabinet';
if (!$USER->IsAdmin()) {
    return;
}
Loader::includeModule($moduleId);

$saved = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_requisites']) && check_bitrix_sessid()) {
    MarketplaceRequisites::set((array)($_POST['requisites'] ?? []));
    // Реквизиты выводятся на контентных страницах — сбросить их кэш.
    if (Loader::includeModule('iblock')) {
        $iblock = CIBlock::GetList([], ['=CODE' => 'content_pages', 'CHECK_PERMISSIONS' => 'N'])->Fetch();
        if ($iblock) {
            CIBlock::clearIblockTagCache((int)$iblock['ID']);
        }
    }
    $saved = true;
}

$values = MarketplaceRequisites::get();
$tabControl = new CAdminTabControl('formaroCabinetOptions', [
    ['DIV' => 'requisites', 'TAB' => 'Реквизиты площадки', 'TITLE' => 'Реквизиты юрлица маркетплейса для счетов на оплату и страниц сайта'],
]);

if ($saved) {
    CAdminMessage::ShowNote('Реквизиты сохранены');
}
?>
<form method="post" action="<?= htmlspecialcharsbx($APPLICATION->GetCurPage()) ?>?mid=<?= urlencode($moduleId) ?>&amp;lang=<?= LANGUAGE_ID ?>">
    <?= bitrix_sessid_post() ?>
    <?php $tabControl->Begin(); ?>
    <?php $tabControl->BeginNextTab(); ?>
    <tr>
        <td colspan="2">
            <?php
            echo BeginNote();
            echo 'Эти реквизиты подставляются в счёт на оплату заказа (получатель и поставщик), '
                . 'на страницу «Реквизиты» (метка <b>#REQUISITES#</b> в тексте страницы) и в банковские реквизиты '
                . 'на странице «Способ оплаты» (метка <b>#REQUISITES_BANK#</b>). Пустые поля не выводятся.';
            echo EndNote();
            ?>
        </td>
    </tr>
    <?php foreach (MarketplaceRequisites::FIELDS as $code => $label): ?>
        <tr>
            <td width="40%"><label for="requisites_<?= $code ?>"><?= htmlspecialcharsbx($label) ?>:</label></td>
            <td width="60%">
                <?php if (in_array($code, ['name_full', 'legal_address', 'actual_address', 'bank_name'], true)): ?>
                    <textarea id="requisites_<?= $code ?>" name="requisites[<?= $code ?>]" cols="50" rows="2"><?= htmlspecialcharsbx($values[$code]) ?></textarea>
                <?php else: ?>
                    <input type="text" id="requisites_<?= $code ?>" name="requisites[<?= $code ?>]" size="50" value="<?= htmlspecialcharsbx($values[$code]) ?>">
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php $tabControl->Buttons(); ?>
    <input type="submit" name="save_requisites" value="Сохранить" class="adm-btn-save">
    <?php $tabControl->End(); ?>
</form>
