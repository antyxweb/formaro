<?php

namespace Sprint\Migration;

use Bitrix\Main\Mail\Internal\EventMessageTable;
use Bitrix\Main\Mail\Internal\EventTypeTable;
use Sprint\Migration\Exceptions\HelperException;

class Version20261013100001 extends Version
{
    protected $description = 'Управление рассылками: отписки по категориям писем (HL-блок FormaroMailOptOut), письмо со ссылкой на отписку, заголовок List-Unsubscribe';

    private const FIELDS = [
        'UF_EMAIL' => ['string', 'E-mail', 'Y'],
        'UF_CHANNEL' => ['string', 'Категория писем (buyer.order, partner.chat…)', 'Y'],
        'UF_CREATED_AT' => ['datetime', 'Дата отписки', 'Y'],
    ];

    private const UNSUBSCRIBE_FIELD = '#UNSUBSCRIBE_URL# — «Управление рассылками»: ссылка в подвале письма и заголовок List-Unsubscribe';

    /**
     * Категории — типы уведомлений покупателя и поставщика (Mailer::NOTIFICATIONS).
     * По умолчанию все включены, в HL-блоке хранятся только отказы.
     * См. Formaro\Cabinet\Service\MailingService.
     *
     * @throws HelperException
     */
    public function up()
    {
        \Bitrix\Main\Loader::includeModule('formaro.cabinet');
        $helper = $this->getHelperManager();

        $hlblockId = $helper->Hlblock()->saveHlblock([
            'NAME' => 'FormaroMailOptOut',
            'TABLE_NAME' => 'formaro_mail_opt_out',
            'LANG' => ['ru' => ['NAME' => 'Отписки от рассылок'], 'en' => ['NAME' => 'Mailing opt-outs']],
        ]);
        $fields = [];
        $sort = 100;
        foreach (self::FIELDS as $code => [$type, $label, $mandatory]) {
            $fields[] = [
                'FIELD_NAME' => $code,
                'USER_TYPE_ID' => $type,
                'MANDATORY' => $mandatory,
                'SORT' => $sort += 10,
                'SHOW_FILTER' => 'I',
                'EDIT_FORM_LABEL' => ['ru' => $label],
                'LIST_COLUMN_LABEL' => ['ru' => $label],
                'LIST_FILTER_LABEL' => ['ru' => $label],
            ];
        }
        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('HLBLOCK_' . $hlblockId, $fields);

        // Ссылка «Отписаться» из формы «Будьте в курсе» для гостя.
        $helper->Event()->saveEventType('FORMARO_SUBSCRIPTION_MANAGE', [
            'LID' => 'ru',
            'NAME' => 'Reppa: ссылка на управление рассылками (отписка из формы «Будьте в курсе»)',
            'DESCRIPTION' => "#MANAGE_URL# — страница «Управление рассылками» для этого адреса\n#EMAIL# — e-mail получателя\n#GREETING# — «Здравствуйте!»\n#SITE_URL# — адрес сайта",
        ]);
        $helper->Event()->saveEventMessage('FORMARO_SUBSCRIPTION_MANAGE', [
            'LID' => ['s1'],
            'ACTIVE' => 'Y',
            'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
            'EMAIL_TO' => '#EMAIL#',
            'SUBJECT' => 'Отписка от рассылки Reppa',
            'BODY_TYPE' => 'html',
            'SITE_TEMPLATE_ID' => 'mail_formaro',
            'MESSAGE' => '<h1 style="margin:0 0 20px;font-size:22px;line-height:28px;font-weight:bold;color:#111111;">Отписка от рассылки</h1>' . "\n"
                . '<p style="margin:0 0 12px;">#HTML_GREETING#</p>'
                . '<p style="margin:0;">Кто-то, возможно вы, попросил отписать адрес #HTML_EMAIL# от рассылки Reppa. Перейдите по ссылке, чтобы выбрать, от каких писем отписаться.</p>' . "\n"
                . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0 0;"><tr><td style="border-radius:6px;background:#1976d2;">'
                . '<a href="#MANAGE_URL#" target="_blank" style="display:inline-block;padding:12px 24px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:6px;">Управление рассылками</a>'
                . '</td></tr></table>' . "\n"
                . '<p style="margin:16px 0 0;font-size:13px;line-height:20px;color:#6b7280;">Если вы ничего не запрашивали, просто проигнорируйте это письмо: подписка останется.</p>',
        ]);

        // Уведомления покупателю и поставщику: поле ссылки в описании и заголовок List-Unsubscribe.
        foreach (\Formaro\Cabinet\Service\MailingService::AUDIENCES as $audience) {
            foreach (array_keys(\Formaro\Cabinet\Mail\Mailer::NOTIFICATIONS[$audience]) as $type) {
                $event = \Formaro\Cabinet\Mail\Mailer::notificationEvent($audience, $type);
                $types = EventTypeTable::getList(['select' => ['ID', 'DESCRIPTION'], 'filter' => ['=EVENT_NAME' => $event]]);
                while ($row = $types->fetch()) {
                    if (!str_contains((string)$row['DESCRIPTION'], '#UNSUBSCRIBE_URL#')) {
                        EventTypeTable::update($row['ID'], ['DESCRIPTION' => rtrim((string)$row['DESCRIPTION']) . "\n" . self::UNSUBSCRIBE_FIELD]);
                    }
                }
                $messages = EventMessageTable::getList(['select' => ['ID', 'ADDITIONAL_FIELD'], 'filter' => ['=EVENT_NAME' => $event]]);
                while ($row = $messages->fetch()) {
                    $headers = array_values(array_filter(
                        (array)$row['ADDITIONAL_FIELD'],
                        static fn($field) => is_array($field) && strcasecmp((string)($field['NAME'] ?? ''), 'List-Unsubscribe') !== 0
                    ));
                    $headers[] = ['NAME' => 'List-Unsubscribe', 'VALUE' => '<#UNSUBSCRIBE_URL#>'];
                    EventMessageTable::update($row['ID'], ['ADDITIONAL_FIELD' => $headers]);
                }
            }
        }

        $this->outSuccess('Управление рассылками готово (HL-блок ID=%d)', $hlblockId);
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        \Bitrix\Main\Loader::includeModule('formaro.cabinet');
        $helper = $this->getHelperManager();
        foreach (\Formaro\Cabinet\Service\MailingService::AUDIENCES as $audience) {
            foreach (array_keys(\Formaro\Cabinet\Mail\Mailer::NOTIFICATIONS[$audience]) as $type) {
                $messages = EventMessageTable::getList([
                    'select' => ['ID', 'ADDITIONAL_FIELD'],
                    'filter' => ['=EVENT_NAME' => \Formaro\Cabinet\Mail\Mailer::notificationEvent($audience, $type)],
                ]);
                while ($row = $messages->fetch()) {
                    EventMessageTable::update($row['ID'], ['ADDITIONAL_FIELD' => array_values(array_filter(
                        (array)$row['ADDITIONAL_FIELD'],
                        static fn($field) => is_array($field) && strcasecmp((string)($field['NAME'] ?? ''), 'List-Unsubscribe') !== 0
                    ))]);
                }
            }
        }
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('FormaroMailOptOut');
        if ($hlblockId) {
            $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists('HLBLOCK_' . $hlblockId, array_keys(self::FIELDS));
            $helper->Hlblock()->deleteHlblock($hlblockId);
        }
        $this->outSuccess('HL-блок "FormaroMailOptOut" удалён, заголовок List-Unsubscribe снят');
    }
}
