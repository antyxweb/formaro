<?php
/**
 * Настройки модуля formaro.cabinet (Настройки → Настройки модулей →
 * «Кабинет партнёра Formaro»): вкладка «Реквизиты площадки» — реквизиты
 * юрлица маркетплейса для счетов на оплату, страниц «Реквизиты» /
 * «Способ оплаты» и подвала писем (Formaro\Cabinet\Settings\MarketplaceRequisites);
 * вкладка «Почта» — адрес сайта для ссылок в письмах, кому писать
 * администраторам, список писем и тестовое письмо (MailSettings, Mailer).
 *
 * @var CMain $APPLICATION
 * @var CUser $USER
 */

use Bitrix\Main\Loader;
use Formaro\Cabinet\Mail\Mailer;
use Formaro\Cabinet\Settings\MailSettings;
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
$testResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test']) && check_bitrix_sessid()) {
    $testEmail = trim((string)($_POST['test_email'] ?? ''));
    $testResult = Mailer::send('FORMARO_ADMIN_SYSTEM', $testEmail, [
        'NAME' => (string)$USER->GetFirstName(),
        'TITLE' => 'Тестовое письмо',
        'MESSAGE' => 'Так выглядят письма сайта: шапка с логотипом, текст, кнопка и подвал с реквизитами площадки.',
        'LINK_URL' => Mailer::url('/'),
        'LINK_TEXT' => 'Открыть сайт',
    ]);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_requisites']) && check_bitrix_sessid()) {
    MarketplaceRequisites::set((array)($_POST['requisites'] ?? []));
    MailSettings::set((array)($_POST['mail'] ?? []));
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
$mail = MailSettings::get();
$tabControl = new CAdminTabControl('formaroCabinetOptions', [
    ['DIV' => 'requisites', 'TAB' => 'Реквизиты площадки', 'TITLE' => 'Реквизиты юрлица маркетплейса для счетов на оплату, страниц сайта и писем'],
    ['DIV' => 'mail', 'TAB' => 'Почта', 'TITLE' => 'Письма сайта'],
]);

// Письма сайта — свои почтовые события FORMARO_* (ссылки на их шаблоны).
$letters = [];
$types = CEventType::GetList(['LID' => LANGUAGE_ID]);
while ($type = $types->Fetch()) {
    if (strpos($type['EVENT_NAME'], 'FORMARO_') === 0) {
        $letters[$type['EVENT_NAME']] = $type['NAME'];
    }
}
ksort($letters);

if ($saved) {
    CAdminMessage::ShowNote('Настройки сохранены');
}
if ($testResult === true) {
    CAdminMessage::ShowNote('Тестовое письмо поставлено в очередь отправки');
} elseif ($testResult === false) {
    CAdminMessage::ShowMessage('Проверьте e-mail для тестового письма');
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
    <?php $tabControl->BeginNextTab(); ?>
    <tr>
        <td colspan="2">
            <?php
            echo BeginNote();
            echo 'Все письма сайта — свои почтовые события <b>FORMARO_*</b> (стандартные события Битрикса не используются). '
                . 'Оформление — шаблон сайта <b>mail_formaro</b>: шапка с логотипом и подвал с реквизитами площадки с вкладки «Реквизиты площадки». '
                . 'Уведомления в кабинетах покупателя, поставщика и администратора дублируются письмом. '
                . 'Текст и тему письма можно поменять в его почтовом шаблоне, отключить письмо — снять у шаблона галочку «Активен».';
            echo EndNote();
            ?>
        </td>
    </tr>
    <tr>
        <td width="40%"><label for="mail_site_url">Адрес сайта для ссылок в письмах:</label></td>
        <td width="60%">
            <input type="text" id="mail_site_url" name="mail[site_url]" size="50" value="<?= htmlspecialcharsbx($mail['site_url']) ?>" placeholder="https://formaro.ru">
            <br><small>Пусто — «Адрес сервера» из настроек главного модуля. Сейчас: <?= htmlspecialcharsbx(MailSettings::siteUrl() ?: 'не определён') ?></small>
        </td>
    </tr>
    <tr>
        <td width="40%"><label for="mail_admin_emails">E-mail администраторов площадки:</label></td>
        <td width="60%">
            <input type="text" id="mail_admin_emails" name="mail[admin_emails]" size="50" value="<?= htmlspecialcharsbx($mail['admin_emails']) ?>">
            <br><small>Через запятую. Сюда приходят уведомления кабинета администратора. Пусто — «E-mail адрес по умолчанию» главного модуля.</small>
        </td>
    </tr>
    <tr class="heading"><td colspan="2">Письма</td></tr>
    <?php foreach ($letters as $event => $name): ?>
        <tr>
            <td width="40%"><?= htmlspecialcharsbx($name) ?>:</td>
            <td width="60%"><a href="/bitrix/admin/message_admin.php?lang=<?= LANGUAGE_ID ?>&amp;set_filter=Y&amp;find_type_id=<?= urlencode($event) ?>"><?= htmlspecialcharsbx($event) ?></a></td>
        </tr>
    <?php endforeach; ?>
    <tr class="heading"><td colspan="2">Тестовое письмо</td></tr>
    <tr>
        <td width="40%"><label for="test_email">Отправить на e-mail:</label></td>
        <td width="60%">
            <input type="text" id="test_email" name="test_email" size="30" value="<?= htmlspecialcharsbx((string)$USER->GetEmail()) ?>">
            <input type="submit" name="send_test" value="Отправить">
        </td>
    </tr>
    <?php $tabControl->Buttons(); ?>
    <input type="submit" name="save_requisites" value="Сохранить" class="adm-btn-save">
    <?php $tabControl->End(); ?>
</form>
