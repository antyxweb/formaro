<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261013190001 extends Version
{
    protected $description = 'Рассылка «Новый поставщик» общим подписчикам: свойство ANNOUNCED_AT в cabinet_partners, письмо FORMARO_NEW_PARTNER, агент';

    private const AGENT = '\\Formaro\\Cabinet\\Service\\NewPartnerMailingService::agent();';

    /**
     * Formaro\Cabinet\Service\NewPartnerMailingService. Уже работающие
     * поставщики помечаются объявленными, чтобы агент не разослал их.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists('cabinet_partners', 'marketplace');

        $helper->Iblock()->saveProperty($iblockId, [
            'NAME' => 'Объявлен подписчикам',
            'CODE' => 'ANNOUNCED_AT',
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE' => 'DateTime',
            'SORT' => 900,
            'HINT' => 'Когда подписчикам ушло письмо о новом поставщике. Заполнено — письмо больше не отправляется.',
        ]);

        \Bitrix\Main\Loader::includeModule('iblock');
        $now = ConvertTimeStamp(time(), 'FULL');
        $marked = 0;
        $res = \CIBlockElement::GetList([], ['IBLOCK_ID' => $iblockId, 'PROPERTY_ANNOUNCED_AT' => false, 'CHECK_PERMISSIONS' => 'N'], false, false, ['ID']);
        while ($row = $res->Fetch()) {
            \CIBlockElement::SetPropertyValuesEx((int)$row['ID'], $iblockId, ['ANNOUNCED_AT' => $now]);
            $marked++;
        }

        $helper->Event()->saveEventType('FORMARO_NEW_PARTNER', [
            'LID' => 'ru',
            'NAME' => 'Reppa: новый поставщик (общим подписчикам)',
            'DESCRIPTION' => "#PARTNER_NAME# — название поставщика\n#TEXT# — описание поставщика (может быть пустым)\n#LOGO# — логотип (готовый HTML, может быть пустым)\n#ITEMS# — первые товары (готовый HTML)\n#PARTNER_URL# — страница поставщика\n#UNSUBSCRIBE_URL# — «Управление рассылками»: ссылка в подвале письма и заголовок List-Unsubscribe\n#EMAIL# — e-mail получателя\n#GREETING# — «Здравствуйте!»\n#SITE_URL# — адрес сайта\nВ HTML-тексте письма поля выводите как #HTML_ПОЛЕ# — так текст экранируется.",
        ]);
        $helper->Event()->saveEventMessage('FORMARO_NEW_PARTNER', [
            'LID' => ['s1'],
            'ACTIVE' => 'Y',
            'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
            'EMAIL_TO' => '#EMAIL#',
            'SUBJECT' => 'Новый поставщик на Reppa: #PARTNER_NAME#',
            'BODY_TYPE' => 'html',
            'SITE_TEMPLATE_ID' => 'mail_formaro',
            'ADDITIONAL_FIELD' => [['NAME' => 'List-Unsubscribe', 'VALUE' => '<#UNSUBSCRIBE_URL#>']],
            'MESSAGE' => '<p style="margin:0 0 8px;font-size:13px;line-height:20px;color:#6b7280;">Новый поставщик</p>' . "\n"
                . '#LOGO#' . "\n"
                . '<h1 style="margin:0 0 12px;font-size:22px;line-height:28px;font-weight:bold;color:#111111;">#HTML_PARTNER_NAME#</h1>' . "\n"
                . '<p style="margin:0;">#HTML_TEXT#</p>' . "\n"
                . '#ITEMS#' . "\n"
                . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0 0;"><tr><td style="border-radius:6px;background:#1976d2;">'
                . '<a href="#PARTNER_URL#" target="_blank" style="display:inline-block;padding:12px 24px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:6px;">Все товары поставщика</a>'
                . '</td></tr></table>' . "\n"
                . '<p style="margin:16px 0 0;font-size:13px;line-height:20px;color:#6b7280;">Вы получили это письмо, потому что подписались на новости на сайте Reppa.</p>',
        ]);

        if (!\CAgent::GetList([], ['NAME' => self::AGENT])->Fetch()) {
            \CAgent::AddAgent(self::AGENT, 'formaro.cabinet', 'N', 3600, '', 'Y', ConvertTimeStamp(time() + 3600, 'FULL'));
        }

        $this->outSuccess('Рассылка о новых поставщиках готова: свойство ANNOUNCED_AT (уже работающих поставщиков — %d), письмо FORMARO_NEW_PARTNER, агент', $marked);
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        \CAgent::RemoveAgent(self::AGENT, 'formaro.cabinet');
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists('cabinet_partners', 'marketplace');
        $helper->Iblock()->deletePropertyIfExists($iblockId, 'ANNOUNCED_AT');
        $this->outSuccess('Агент рассылки о новых поставщиках и свойство ANNOUNCED_AT удалены (письмо FORMARO_NEW_PARTNER оставлено)');
    }
}
