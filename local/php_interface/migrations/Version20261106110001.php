<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261106110001 extends Version
{
    protected $description = 'Один заказ и один счёт покупателю: части по поставщикам с общим UF_GROUP_ID, письма без названия поставщика, гайд';

    private const GUIDE = ['cart', 'buyer-orders', 'admin-operations'];

    private const MAIL = [
        'FORMARO_ORDER_CREATED' => ['Продавец #HTML_PARTNER# получил его. Мы согласуем с ним наличие' => 'Мы согласуем с поставщиками наличие'],
        'FORMARO_ORDER_INVOICE' => ['Заказ #HTML_ORDER_NUMBER# у поставщика #HTML_PARTNER# согласован' => 'Заказ #HTML_ORDER_NUMBER# согласован'],
    ];

    /** @throws HelperException */
    public function up()
    {
        $helper = $this->getHelperManager();

        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('CabinetOrders');
        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists('HLBLOCK_' . $hlblockId, [[
            'FIELD_NAME' => 'UF_GROUP_ID',
            'USER_TYPE_ID' => 'integer',
            'MANDATORY' => 'N',
            'SORT' => 500,
            'SHOW_FILTER' => 'I',
            'EDIT_FORM_LABEL' => ['ru' => 'Заказ покупателя (ID первой части)'],
            'LIST_COLUMN_LABEL' => ['ru' => 'Заказ покупателя'],
            'LIST_FILTER_LABEL' => ['ru' => 'Заказ покупателя'],
        ]]);

        foreach (self::MAIL as $event => $replace) {
            $messages = \CEventMessage::GetList('id', 'asc', ['TYPE_ID' => $event]);
            while ($message = $messages->Fetch()) {
                $body = strtr($message['MESSAGE'], $replace);
                if ($body !== $message['MESSAGE']) {
                    (new \CEventMessage())->Update($message['ID'], ['MESSAGE' => $body]);
                    $this->outSuccess('Письмо %s обновлено', $event);
                }
            }
        }

        $iblockId = $helper->Iblock()->getIblockIdIfExists('content_pages', 'content');
        $dir = __DIR__ . '/data/guide/';
        foreach (json_decode((string)file_get_contents($dir . 'chapters.json'), true) as $chapter) {
            if (in_array($chapter['code'], self::GUIDE, true) && ($id = (int)$helper->Iblock()->getElementId($iblockId, $chapter['code']))) {
                (new \CIBlockElement())->Update($id, ['DETAIL_TEXT' => (string)file_get_contents($dir . $chapter['file']), 'DETAIL_TEXT_TYPE' => 'html']);
            }
        }
        \CIBlock::clearIblockTagCache($iblockId);
    }

    public function down()
    {
        $this->outInfo('Поле UF_GROUP_ID и тексты не откатываются: в поле уже могут быть связи заказов');
    }
}
