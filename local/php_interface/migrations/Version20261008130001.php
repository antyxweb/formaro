<?php

namespace Sprint\Migration;

use Bitrix\Main\Config\Option;
use Sprint\Migration\Exceptions\HelperException;

class Version20261008130001 extends Version
{
    protected $description = 'Почта: свои почтовые события FORMARO_* для всех уведомлений, шаблон писем mail_formaro (логотип, реквизиты)';

    private const SITE_TEMPLATE = 'mail_formaro';

    /** Поля, которые Formaro\Cabinet\Mail\Mailer добавляет в каждое письмо. */
    private const COMMON_FIELDS = "#EMAIL# — e-mail получателя\n#NAME# — имя получателя\n#GREETING# — «Здравствуйте, Имя!» (без имени — «Здравствуйте!»)\n#SITE_URL# — адрес сайта (https://…)\nВ HTML-тексте письма поля выводите как #HTML_ПОЛЕ# — например #HTML_NAME#: так текст экранируется.";

    private const NOTIFICATION_FIELDS = "#TITLE# — заголовок уведомления\n#MESSAGE# — текст уведомления\n#LINK_URL# — ссылка на страницу\n#LINK_TEXT# — подпись кнопки";

    /**
     * Все письма — HTML в шаблоне сайта mail_formaro (шапка с логотипом,
     * подвал с реквизитами), отправитель — «E-mail адрес по умолчанию».
     * Отправка — Formaro\Cabinet\Mail\Mailer: уведомления кабинетов
     * (FORMARO_BUYER_*, FORMARO_PARTNER_*, FORMARO_ADMIN_*) и отдельные
     * письма (регистрация, пароль, заказ, счёт).
     *
     * @throws HelperException
     */
    public function up()
    {
        \Bitrix\Main\Loader::includeModule('formaro.cabinet');

        foreach (\Formaro\Cabinet\Mail\Mailer::NOTIFICATIONS as $audience => $types) {
            $whom = \Formaro\Cabinet\Mail\Mailer::AUDIENCES[$audience];
            foreach ($types as $type => $label) {
                $this->save(
                    \Formaro\Cabinet\Mail\Mailer::notificationEvent($audience, $type),
                    'Reppa: уведомление ' . $whom . ' — ' . mb_strtolower($label),
                    self::NOTIFICATION_FIELDS,
                    '#TITLE#',
                    self::body('#HTML_TITLE#', '<p style="margin:0 0 12px;">#HTML_GREETING#</p><p style="margin:0;">#HTML_MESSAGE#</p>', '#LINK_URL#', '#HTML_LINK_TEXT#')
                );
            }
        }

        foreach (self::letters() as $event => [$name, $fields, $subject, $body]) {
            $this->save($event, $name, $fields, $subject, $body);
        }

        // Официальный e-mail площадки (страница «Контакты») — в подвале писем.
        if (trim((string)Option::get('formaro.cabinet', 'requisites_email', '')) === '') {
            Option::set('formaro.cabinet', 'requisites_email', 'info@formaro.ru');
        }

        $this->outSuccess('Почтовые события и шаблоны писем готовы');
    }

    public function down()
    {
        $this->outInfo('Почтовые события не удаляются — письма перестанут уходить. Удалите их вручную, если нужно.');
    }

    /** @throws HelperException */
    private function save(string $event, string $name, string $fields, string $subject, string $body): void
    {
        $helper = $this->getHelperManager();
        $helper->Event()->saveEventType($event, [
            'LID' => 'ru',
            'NAME' => $name,
            'DESCRIPTION' => $fields . "\n" . self::COMMON_FIELDS,
        ]);
        $helper->Event()->saveEventMessage($event, [
            'LID' => ['s1'],
            'ACTIVE' => 'Y',
            'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
            'EMAIL_TO' => '#EMAIL#',
            'SUBJECT' => $subject,
            'BODY_TYPE' => 'html',
            'SITE_TEMPLATE_ID' => self::SITE_TEMPLATE,
            'MESSAGE' => $body,
        ]);
    }

    /** Письма со своим текстом: событие => [название, поля, тема, HTML]. */
    private static function letters(): array
    {
        $muted = 'margin:16px 0 0;font-size:13px;line-height:20px;color:#6b7280;';

        return [
            'FORMARO_USER_REGISTER' => [
                'Reppa: регистрация покупателя на сайте',
                "#LOGIN# — логин (e-mail)\n#PROFILE_URL# — личный кабинет",
                'Добро пожаловать в Reppa',
                self::body(
                    'Вы зарегистрировались на Reppa',
                    '<p style="margin:0 0 12px;">#HTML_GREETING#</p>'
                    . '<p style="margin:0 0 12px;">Спасибо за регистрацию на маркетплейсе Reppa. Для входа на сайт используйте e-mail <strong>#HTML_LOGIN#</strong> и пароль, указанный при регистрации.</p>'
                    . '<p style="margin:0;">В личном кабинете — ваши заказы, счета на оплату, переписка с продавцами и уведомления.</p>',
                    '#PROFILE_URL#',
                    'Открыть личный кабинет',
                    '<p style="' . $muted . '">Если вы не регистрировались на сайте, просто проигнорируйте это письмо.</p>'
                ),
            ],
            'FORMARO_USER_PASS_REQUEST' => [
                'Reppa: восстановление пароля',
                "#LOGIN# — логин\n#CHANGE_URL# — ссылка на смену пароля",
                'Восстановление пароля на Reppa',
                self::body(
                    'Восстановление пароля',
                    '<p style="margin:0 0 12px;">#HTML_GREETING#</p>'
                    . '<p style="margin:0;">Мы получили запрос на смену пароля для учётной записи <strong>#HTML_LOGIN#</strong>. Чтобы задать новый пароль, нажмите кнопку ниже.</p>',
                    '#CHANGE_URL#',
                    'Задать новый пароль',
                    '<p style="' . $muted . '">Ссылка действует ограниченное время. Если вы не запрашивали смену пароля, проигнорируйте это письмо — пароль останется прежним.</p>'
                ),
            ],
            'FORMARO_PARTNER_REGISTER' => [
                'Reppa: создан кабинет партнёра',
                "#COMPANY# — название организации\n#LOGIN# — логин (e-mail)\n#CABINET_URL# — кабинет партнёра",
                'Кабинет партнёра Reppa создан',
                self::body(
                    'Кабинет партнёра создан',
                    '<p style="margin:0 0 12px;">#HTML_GREETING#</p>'
                    . '<p style="margin:0 0 12px;">Кабинет партнёра для #HTML_COMPANY# на маркетплейсе Reppa создан. Вход — по e-mail <strong>#HTML_LOGIN#</strong> и вашему паролю.</p>'
                    . '<p style="margin:0;">Заполните профиль компании и реквизиты, загрузите документы и отправьте профиль на проверку. Магазин появится на витрине после проверки маркетплейсом — о результате мы сообщим письмом.</p>',
                    '#CABINET_URL#',
                    'Перейти в кабинет партнёра'
                ),
            ],
            'FORMARO_ORDER_CREATED' => [
                'Reppa: заказ оформлен (покупателю)',
                "#ORDER_NUMBER# — номер заказа\n#PARTNER# — продавец\n#TOTAL# — сумма заказа\n#ITEMS# — состав заказа (HTML-таблица)\n#DELIVERY# — способ доставки\n#ORDERS_URL# — «Ваши заказы»",
                'Заказ #ORDER_NUMBER# оформлен',
                self::body(
                    'Заказ #HTML_ORDER_NUMBER# оформлен',
                    '<p style="margin:0 0 12px;">#HTML_GREETING#</p>'
                    . '<p style="margin:0 0 20px;">Спасибо за заказ! Продавец #HTML_PARTNER# получил его. Мы согласуем с ним наличие и срок отгрузки и выставим счёт на оплату — о счёте сообщим письмом.</p>'
                    . '#ITEMS#'
                    . '<p style="margin:20px 0 0;">Доставка: #HTML_DELIVERY#. Стоимость доставки в счёт не входит — её оплачивает заказчик транспортной компании при получении товара.</p>',
                    '#ORDERS_URL#',
                    'Ваши заказы'
                ),
            ],
            'FORMARO_ORDER_INVOICE' => [
                'Reppa: выставлен счёт на оплату заказа',
                "#ORDER_NUMBER# — номер заказа (и счёта)\n#TOTAL# — сумма к оплате\n#DUE_DATE# — счёт действителен до\n#PARTNER# — поставщик\n#ORDERS_URL# — «Ваши заказы»",
                'Счёт на оплату заказа #ORDER_NUMBER#',
                self::body(
                    'Счёт на оплату заказа #HTML_ORDER_NUMBER#',
                    '<p style="margin:0 0 12px;">#HTML_GREETING#</p>'
                    . '<p style="margin:0 0 12px;">Заказ #HTML_ORDER_NUMBER# у поставщика #HTML_PARTNER# согласован — мы выставили счёт на оплату на сумму <strong>#HTML_TOTAL# руб.</strong></p>'
                    . '<p style="margin:0 0 12px;">Счёт действителен в течение 3-х рабочих дней — до <strong>#HTML_DUE_DATE#</strong>. Скачать его можно в разделе «Ваши заказы». После оплаты нажмите у заказа «Сообщить об оплате» — так мы быстрее найдём платёж.</p>'
                    . '<p style="margin:0;">Стоимость доставки в счёт не входит: её оплачивает заказчик транспортной компании при получении товара.</p>',
                    '#ORDERS_URL#',
                    'Скачать счёт'
                ),
            ],
            'FORMARO_ORDER_INVOICE_EXPIRED' => [
                'Reppa: счёт на оплату аннулирован (не оплачен в срок)',
                "#ORDER_NUMBER# — номер заказа (и счёта)\n#TOTAL# — сумма счёта\n#PARTNER# — поставщик\n#ORDERS_URL# — «Ваши заказы»",
                'Счёт № #ORDER_NUMBER# не действителен',
                self::body(
                    'Счёт № #HTML_ORDER_NUMBER# не действителен',
                    '<p style="margin:0 0 12px;">#HTML_GREETING#</p>'
                    . '<p style="margin:0 0 12px;">В связи с неоплатой счёт № #HTML_ORDER_NUMBER# не действителен.</p>'
                    . '<p style="margin:0;">Если заказ ещё актуален, напишите нам — менеджер выставит новый счёт.</p>',
                    '#ORDERS_URL#',
                    'Ваши заказы'
                ),
            ],
        ];
    }

    /** Текст письма: заголовок, абзацы, кнопка и необязательная приписка под ней. */
    private static function body(string $title, string $html, string $url, string $button, string $after = ''): string
    {
        return '<h1 style="margin:0 0 20px;font-size:22px;line-height:28px;font-weight:bold;color:#111111;">' . $title . '</h1>' . "\n"
            . $html . "\n"
            . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0 0;"><tr><td style="border-radius:6px;background:#1976d2;">'
            . '<a href="' . $url . '" target="_blank" style="display:inline-block;padding:12px 24px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:6px;">' . $button . '</a>'
            . '</td></tr></table>' . "\n"
            . $after;
    }
}
