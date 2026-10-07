<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261007100001 extends Version
{
    protected $description = 'Финансы, этап 1: подтверждение заказа поставщиком, выставление счёта менеджером, счёт действителен 3 рабочих дня';

    private const AGENT = '\\Formaro\\Cabinet\\Service\\OrderInvoiceFlowService::agent();';

    /** Новые поля заказа (CabinetOrders). */
    private const FIELDS = [
        ['UF_SUPPLIER_CONFIRMED_AT', 'datetime', 'Поставщик подтвердил наличие'],
        ['UF_SUPPLIER_LEAD_TIME', 'string', 'Срок отгрузки (от поставщика)'],
        ['UF_INVOICE_STATUS', 'string', 'Счёт: пусто — не выставлен, issued — выставлен, expired — аннулирован'],
        ['UF_INVOICE_DATE', 'datetime', 'Счёт выставлен'],
        ['UF_INVOICE_DUE', 'datetime', 'Счёт действителен до'],
    ];

    /** Шаги «Как оплатить заказ» на странице «Способ оплаты» (for-buyers/payment). */
    private const PAYMENT_STEPS = <<<'HTML'
<ol>
    <li>При оформлении заказа в разделе «Способ оплаты» выберите «По счёту (безнал)».</li>
    <li>Менеджер Formaro согласует заказ с поставщиком — наличие и срок отгрузки — и выставит счёт. Вы получите уведомление и письмо.</li>
    <li>Откройте раздел «<a href="/personal/orders/">Ваши заказы</a>» и нажмите «Скачать счёт» в колонке «Оплата» — счёт на оплату в формате PDF.</li>
    <li>Оплатите счёт в течение 3-х рабочих дней — через интернет-банк или в отделении банка. В назначении платежа укажите номер счёта. Неоплаченный в срок счёт аннулируется.</li>
    <li>Нажмите «Сообщить об оплате» у заказа — так мы быстрее найдём ваш платёж.</li>
    <li>Маркетплейс проверит поступление и подтвердит оплату — в заказе появится статус «Оплачен», а в разделе «<a href="/personal/notify/">Уведомления</a>» — сообщение.</li>
</ol>
<p><strong>Доставка</strong> в счёт не входит: её стоимость заказчик оплачивает транспортной компании при получении товара.</p>
HTML;

    /**
     * Порядок по ТЗ «Финансы»: поставщик подтверждает наличие и срок
     * отгрузки → менеджер выставляет счёт (клиенту — уведомление и письмо)
     * → счёт действителен 3 рабочих дня, затем агент аннулирует
     * неоплаченный и уведомляет клиента (OrderInvoiceFlowService).
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('CabinetOrders');
        $fields = [];
        foreach (self::FIELDS as $i => [$name, $type, $label]) {
            $fields[] = [
                'FIELD_NAME' => $name,
                'USER_TYPE_ID' => $type,
                'MANDATORY' => 'N',
                'SORT' => 300 + $i * 10,
                'SHOW_FILTER' => 'I',
                'EDIT_FORM_LABEL' => ['ru' => $label],
                'LIST_COLUMN_LABEL' => ['ru' => $label],
                'LIST_FILTER_LABEL' => ['ru' => $label],
            ];
        }
        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('HLBLOCK_' . $hlblockId, $fields);

        $vars = "#EMAIL# — e-mail покупателя\n#NAME# — имя покупателя\n#ORDER_NUMBER# — номер заказа (и счёта)\n#TOTAL# — сумма к оплате\n#DUE_DATE# — счёт действителен до\n#PARTNER# — поставщик\n#ORDERS_URL# — «Ваши заказы»";
        $helper->Event()->saveEventType('FORMARO_ORDER_INVOICE', [
            'LID' => 'ru', 'NAME' => 'Formaro: выставлен счёт на оплату заказа', 'DESCRIPTION' => $vars,
        ]);
        $helper->Event()->saveEventType('FORMARO_ORDER_INVOICE_EXPIRED', [
            'LID' => 'ru', 'NAME' => 'Formaro: счёт на оплату аннулирован (не оплачен в срок)', 'DESCRIPTION' => $vars,
        ]);
        $helper->Event()->saveEventMessage('FORMARO_ORDER_INVOICE', [
            'LID' => ['s1'],
            'ACTIVE' => 'Y',
            'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
            'EMAIL_TO' => '#EMAIL#',
            'SUBJECT' => 'Счёт на оплату заказа #ORDER_NUMBER#',
            'BODY_TYPE' => 'text',
            'MESSAGE' => "Здравствуйте, #NAME#!\n\nЗаказ #ORDER_NUMBER# у поставщика «#PARTNER#» согласован, мы выставили счёт на оплату на сумму #TOTAL# руб.\n\nСчёт действителен в течение 3-х рабочих дней — до #DUE_DATE#. Скачать его можно в разделе «Ваши заказы»: #ORDERS_URL#\n\nСтоимость доставки в счёт не входит: её оплачивает заказчик транспортной компании при получении товара.\n\nFormaro",
        ]);
        $helper->Event()->saveEventMessage('FORMARO_ORDER_INVOICE_EXPIRED', [
            'LID' => ['s1'],
            'ACTIVE' => 'Y',
            'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
            'EMAIL_TO' => '#EMAIL#',
            'SUBJECT' => 'Счёт № #ORDER_NUMBER# не действителен',
            'BODY_TYPE' => 'text',
            'MESSAGE' => "Здравствуйте, #NAME#!\n\nВ связи с неоплатой счёт № #ORDER_NUMBER# не действителен.\n\nЕсли заказ ещё актуален, ответьте на это письмо или напишите нам — менеджер выставит новый счёт.\n\nВаши заказы: #ORDERS_URL#\n\nFormaro",
        ]);

        if (!\CAgent::GetList([], ['NAME' => self::AGENT])->Fetch()) {
            \CAgent::AddAgent(self::AGENT, 'formaro.cabinet', 'N', 3600, '', 'Y', ConvertTimeStamp(time() + 600, 'FULL'));
        }

        $this->updatePaymentPage();

        $this->outSuccess('Поля заказа, письма и агент аннулирования счетов готовы');
    }

    /** @throws HelperException */
    public function down()
    {
        \CAgent::RemoveAgent(self::AGENT, 'formaro.cabinet');
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('CabinetOrders');
        $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists('HLBLOCK_' . $hlblockId, array_column(self::FIELDS, 0));
        $this->outInfo('Почтовые события и текст страницы «Способ оплаты» не откатываются');
    }

    private function updatePaymentPage(): void
    {
        \Bitrix\Main\Loader::includeModule('iblock');
        $iblockId = $this->getHelperManager()->Iblock()->getIblockIdIfExists('content_pages', 'content');
        $el = \CIBlockElement::GetList([], ['IBLOCK_ID' => $iblockId, '=CODE' => 'payment', 'CHECK_PERMISSIONS' => 'N'], false, ['nTopCount' => 1], ['ID', 'DETAIL_TEXT'])->Fetch();
        if (!$el || strpos($el['DETAIL_TEXT'], '3-х рабочих дней') !== false) {
            return;
        }
        $text = (string)$el['DETAIL_TEXT'];
        $start = strpos($text, '<ol>');
        $end = $start === false ? false : strpos($text, '</ol>', $start);
        if ($end === false) {
            $this->outWarning('На странице «Способ оплаты» не найден список шагов — обновите текст вручную');
            return;
        }
        $text = substr($text, 0, $start) . self::PAYMENT_STEPS . substr($text, $end + strlen('</ol>'));
        (new \CIBlockElement())->Update((int)$el['ID'], ['DETAIL_TEXT' => $text, 'DETAIL_TEXT_TYPE' => 'html']);
        \CIBlock::clearIblockTagCache($iblockId);
    }
}
