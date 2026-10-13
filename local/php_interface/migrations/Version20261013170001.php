<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261013170001 extends Version
{
    protected $description = 'Еженедельный дайджест «Новинки и скидки» подписчикам тем «Будьте в курсе»: письмо FORMARO_TOPIC_DIGEST, агент';

    private const AGENT = '\\Formaro\\Cabinet\\Service\\TopicDigestService::agent();';

    /**
     * Formaro\Cabinet\Service\TopicDigestService — раз в неделю, по
     * понедельникам в 10:00 (по времени сервера).
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $helper->Event()->saveEventType('FORMARO_TOPIC_DIGEST', [
            'LID' => 'ru',
            'NAME' => 'Reppa: новинки и скидки недели (подписчикам тем «Будьте в курсе»)',
            'DESCRIPTION' => "#SUBJECT# — тема письма («Новинки и скидки недели: …»)\n#ITEMS# — товары по темам подписчика (готовый HTML)\n#CATALOG_URL# — каталог\n#UNSUBSCRIBE_URL# — «Управление рассылками»: ссылка в подвале письма и заголовок List-Unsubscribe\n#EMAIL# — e-mail получателя\n#GREETING# — «Здравствуйте!»\n#SITE_URL# — адрес сайта",
        ]);
        $helper->Event()->saveEventMessage('FORMARO_TOPIC_DIGEST', [
            'LID' => ['s1'],
            'ACTIVE' => 'Y',
            'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
            'EMAIL_TO' => '#EMAIL#',
            'SUBJECT' => '#SUBJECT#',
            'BODY_TYPE' => 'html',
            'SITE_TEMPLATE_ID' => 'mail_formaro',
            'ADDITIONAL_FIELD' => [['NAME' => 'List-Unsubscribe', 'VALUE' => '<#UNSUBSCRIBE_URL#>']],
            'MESSAGE' => '<h1 style="margin:0 0 8px;font-size:22px;line-height:28px;font-weight:bold;color:#111111;">Новинки и скидки недели</h1>' . "\n"
                . '<p style="margin:0;">#GREETING# Собрали новые товары и акции в разделах, на которые вы подписаны.</p>' . "\n"
                . '#ITEMS#' . "\n"
                . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:28px 0 0;"><tr><td style="border-radius:6px;background:#1976d2;">'
                . '<a href="#CATALOG_URL#" target="_blank" style="display:inline-block;padding:12px 24px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:6px;">Перейти в каталог</a>'
                . '</td></tr></table>' . "\n"
                . '<p style="margin:16px 0 0;font-size:13px;line-height:20px;color:#6b7280;">Вы получили это письмо, потому что подписались на новости разделов каталога на сайте Reppa.</p>',
        ]);

        if (!\CAgent::GetList([], ['NAME' => self::AGENT])->Fetch()) {
            $next = strtotime('next monday 10:00');
            \CAgent::AddAgent(self::AGENT, 'formaro.cabinet', 'Y', 7 * 86400, '', 'Y', ConvertTimeStamp($next, 'FULL'));
        }

        $this->outSuccess('Дайджест по темам готов: письмо FORMARO_TOPIC_DIGEST, агент по понедельникам в 10:00');
    }

    public function down()
    {
        \CAgent::RemoveAgent(self::AGENT, 'formaro.cabinet');
        $this->outSuccess('Агент дайджеста по темам удалён (письмо FORMARO_TOPIC_DIGEST оставлено)');
    }
}
