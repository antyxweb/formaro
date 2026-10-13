<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261013150001 extends Version
{
    protected $description = 'Рассылка новостей подписчикам «Будьте в курсе»: свойство MAILED_AT в cabinet_news, письмо FORMARO_NEWS, агент';

    private const AGENT = '\\Formaro\\Cabinet\\Service\\NewsMailingService::agent();';

    /**
     * Новость площадки — всем общим подписчикам, новость партнёра — его
     * подписчикам (Formaro\Cabinet\Service\NewsMailingService). Уже
     * опубликованные новости помечаются разосланными, чтобы агент не
     * разослал их задним числом.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists('cabinet_news', 'content');

        $helper->Iblock()->saveProperty($iblockId, [
            'NAME' => 'Разослано подписчикам',
            'CODE' => 'MAILED_AT',
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE' => 'DateTime',
            'SORT' => 900,
            'HINT' => 'Когда письмо о новости ушло подписчикам. Заполнено — новость больше не рассылается.',
        ]);

        \Bitrix\Main\Loader::includeModule('iblock');
        $now = ConvertTimeStamp(time(), 'FULL');
        $marked = 0;
        $res = \CIBlockElement::GetList([], ['IBLOCK_ID' => $iblockId, 'PROPERTY_MAILED_AT' => false, 'CHECK_PERMISSIONS' => 'N'], false, false, ['ID']);
        while ($row = $res->Fetch()) {
            \CIBlockElement::SetPropertyValuesEx((int)$row['ID'], $iblockId, ['MAILED_AT' => $now]);
            $marked++;
        }

        $helper->Event()->saveEventType('FORMARO_NEWS', [
            'LID' => 'ru',
            'NAME' => 'Reppa: новая новость (подписчикам «Будьте в курсе»)',
            'DESCRIPTION' => "#TITLE# — заголовок новости\n#TEXT# — анонс\n#FROM# — «Новости Reppa» или «Новость поставщика «…»»\n#IMAGE# — картинка новости (готовый HTML, может быть пустым)\n#NEWS_URL# — страница новости\n#UNSUBSCRIBE_URL# — «Управление рассылками»: ссылка в подвале письма и заголовок List-Unsubscribe\n#EMAIL# — e-mail получателя\n#GREETING# — «Здравствуйте!»\n#SITE_URL# — адрес сайта\nВ HTML-тексте письма поля выводите как #HTML_ПОЛЕ# — так текст экранируется.",
        ]);
        $helper->Event()->saveEventMessage('FORMARO_NEWS', [
            'LID' => ['s1'],
            'ACTIVE' => 'Y',
            'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
            'EMAIL_TO' => '#EMAIL#',
            'SUBJECT' => '#TITLE#',
            'BODY_TYPE' => 'html',
            'SITE_TEMPLATE_ID' => 'mail_formaro',
            'ADDITIONAL_FIELD' => [['NAME' => 'List-Unsubscribe', 'VALUE' => '<#UNSUBSCRIBE_URL#>']],
            'MESSAGE' => '<p style="margin:0 0 8px;font-size:13px;line-height:20px;color:#6b7280;">#HTML_FROM#</p>' . "\n"
                . '<h1 style="margin:0 0 20px;font-size:22px;line-height:28px;font-weight:bold;color:#111111;">#HTML_TITLE#</h1>' . "\n"
                . '#IMAGE#' . "\n"
                . '<p style="margin:0;">#HTML_TEXT#</p>' . "\n"
                . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0 0;"><tr><td style="border-radius:6px;background:#1976d2;">'
                . '<a href="#NEWS_URL#" target="_blank" style="display:inline-block;padding:12px 24px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:6px;">Читать новость</a>'
                . '</td></tr></table>' . "\n"
                . '<p style="margin:16px 0 0;font-size:13px;line-height:20px;color:#6b7280;">Вы получили это письмо, потому что подписались на новости на сайте Reppa.</p>',
        ]);

        if (!\CAgent::GetList([], ['NAME' => self::AGENT])->Fetch()) {
            \CAgent::AddAgent(self::AGENT, 'formaro.cabinet', 'N', 600, '', 'Y', ConvertTimeStamp(time() + 600, 'FULL'));
        }

        $this->outSuccess('Рассылка новостей готова: свойство MAILED_AT (уже опубликованных новостей — %d), письмо FORMARO_NEWS, агент', $marked);
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        \CAgent::RemoveAgent(self::AGENT, 'formaro.cabinet');
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists('cabinet_news', 'content');
        $helper->Iblock()->deletePropertyIfExists($iblockId, 'MAILED_AT');
        $this->outSuccess('Агент рассылки новостей и свойство MAILED_AT удалены (письмо FORMARO_NEWS оставлено)');
    }
}
