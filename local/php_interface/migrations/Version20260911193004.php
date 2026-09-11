<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20260911193004 extends Version
{
    protected $description = 'formaro.cabinet: HL-блок CabinetPartnerDocuments (документы партнёра)';

    /**
     * Список загруженных партнёром документов. UF_PARTNER_ID — ELEMENT_ID
     * элемента в инфоблоке cabinet_partners (простое целое число, не
     * "привязка к элементам" — этот тип UF-полей у HL-блоков предназначен
     * для связи HL-блок→HL-блок, а не HL-блок→инфоблок).
     *
     * Апгрейд относительно прототипа: там документы партнёра хранились
     * только как метаданные (имя/размер/дата) — реального файла не было,
     * т.к. localStorage физически не тянет файлы (см. cabinet-html/CLAUDE.md).
     * Здесь UF_FILE — настоящий аплоад через CFile.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $hlblockId = $helper->Hlblock()->saveHlblock([
            'NAME' => 'CabinetPartnerDocuments',
            'TABLE_NAME' => 'cabinet_partner_documents',
            'LANG' => [
                'ru' => ['NAME' => 'Документы партнёра (кабинет)'],
                'en' => ['NAME' => 'Cabinet partner documents'],
            ],
        ]);

        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists(
            'HLBLOCK_' . $hlblockId,
            [
                [
                    'FIELD_NAME' => 'UF_PARTNER_ID',
                    'USER_TYPE_ID' => 'integer',
                    'MANDATORY' => 'Y',
                    'EDIT_FORM_LABEL' => ['ru' => 'Партнёр (ID элемента в cabinet_partners)'],
                ],
                [
                    'FIELD_NAME' => 'UF_NAME',
                    'USER_TYPE_ID' => 'string',
                    'MANDATORY' => 'Y',
                    'EDIT_FORM_LABEL' => ['ru' => 'Название документа'],
                ],
                [
                    'FIELD_NAME' => 'UF_FILE',
                    'USER_TYPE_ID' => 'file',
                    'EDIT_FORM_LABEL' => ['ru' => 'Файл'],
                ],
                [
                    'FIELD_NAME' => 'UF_UPLOADED_AT',
                    'USER_TYPE_ID' => 'datetime',
                    'EDIT_FORM_LABEL' => ['ru' => 'Дата загрузки'],
                ],
            ]
        );

        $this->outSuccess('HL-блок "CabinetPartnerDocuments" готов (ID=%d)', $hlblockId);
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('CabinetPartnerDocuments');

        if ($hlblockId) {
            $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists(
                'HLBLOCK_' . $hlblockId,
                ['UF_PARTNER_ID', 'UF_NAME', 'UF_FILE', 'UF_UPLOADED_AT']
            );
            $helper->Hlblock()->deleteHlblock($hlblockId);
            $this->outSuccess('HL-блок "CabinetPartnerDocuments" удалён');
        } else {
            $this->outError('HL-блок "CabinetPartnerDocuments" не найден');
        }
    }
}
