<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20260923140001 extends Version
{
    protected $description = 'formaro.cabinet: перенести партнёров из боевого инфоблока "Партнеры" (IBLOCK_ID=2) в cabinet_partners';

    private const SOURCE_IBLOCK_ID = 2;
    private const TARGET_IBLOCK_CODE = 'cabinet_partners';
    private const TARGET_IBLOCK_TYPE = 'marketplace';
    private const XML_ID_PREFIX = 'legacy_partner_';

    /**
     * Боевой инфоблок "Партнеры" (ID=2, тот, что отдаёт /partners/ на
     * сайте) — элементы: NAME/CODE/ACTIVE/PREVIEW_TEXT/DETAIL_TEXT,
     * PREVIEW_PICTURE (своя обложка) и свойство LOGO (тип "Файл").
     *
     * Сопоставление с cabinet_partners: логотип (LOGO) — в наше поле
     * PREVIEW_PICTURE ("Логотип" в кабинете, см. PartnerRepository::
     * toArray()['logo']), а собственная PREVIEW_PICTURE источника — в
     * наше DETAIL_PICTURE ("Обложка магазина", ['image']). Короткого
     * названия, реквизитов и контактов у источника нет — остаются
     * пустыми, партнёр сам заполнит их в кабинете. Статус верификации —
     * сразу "Проверен":
     * это уже реальные, опубликованные на сайте партнёры, а не черновики.
     *
     * Идемпотентно: XML_ID целевого элемента = "legacy_partner_{ID
     * источника}" — при повторном прогоне пропускает уже перенесённые.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        \Bitrix\Main\Loader::includeModule('iblock');

        $targetIblockId = $helper->Iblock()->getIblockIdIfExists(self::TARGET_IBLOCK_CODE, self::TARGET_IBLOCK_TYPE);
        if (!$targetIblockId) {
            throw new HelperException(
                'Инфоблок "' . self::TARGET_IBLOCK_CODE . '" не найден — сначала должна отработать миграция Version20260911193001'
            );
        }

        $verifiedEnumId = $this->resolveEnumId($targetIblockId, 'VERIFICATION_STATUS', 'verified');

        $migrated = 0;
        $skipped = 0;

        $rs = \CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => self::SOURCE_IBLOCK_ID],
            false,
            false,
            ['ID', 'NAME', 'CODE', 'ACTIVE', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'PREVIEW_PICTURE', 'PROPERTY_LOGO']
        );

        while ($source = $rs->Fetch()) {
            $xmlId = self::XML_ID_PREFIX . $source['ID'];

            $existing = \CIBlockElement::GetList([], [
                'IBLOCK_ID' => $targetIblockId,
                'XML_ID' => $xmlId,
                'CHECK_PERMISSIONS' => 'N',
            ], false, false, ['ID'])->Fetch();
            if ($existing) {
                $skipped++;
                continue;
            }

            $fields = [
                'IBLOCK_ID' => $targetIblockId,
                'NAME' => $source['NAME'],
                'CODE' => $source['CODE'],
                'XML_ID' => $xmlId,
                'ACTIVE' => $source['ACTIVE'],
                'PREVIEW_TEXT' => $source['PREVIEW_TEXT'],
                'DETAIL_TEXT' => $source['DETAIL_TEXT'],
            ];

            // Лого источника -> наш "логотип" (PREVIEW_PICTURE).
            if ($source['PROPERTY_LOGO_VALUE']) {
                $fields['PREVIEW_PICTURE'] = \CFile::MakeFileArray((int)$source['PROPERTY_LOGO_VALUE']);
            }
            // Собственная превью-картинка источника -> наша "обложка" (DETAIL_PICTURE).
            if ($source['PREVIEW_PICTURE']) {
                $fields['DETAIL_PICTURE'] = \CFile::MakeFileArray((int)$source['PREVIEW_PICTURE']);
            }

            $el = new \CIBlockElement();
            $newId = $el->Add($fields);
            if (!$newId) {
                throw new HelperException('Не удалось перенести партнёра ID=' . $source['ID'] . ': ' . $el->LAST_ERROR);
            }

            \CIBlockElement::SetPropertyValuesEx($newId, $targetIblockId, [
                'VERIFICATION_STATUS' => $verifiedEnumId,
            ]);

            $migrated++;
        }

        $this->outSuccess('Партнёров перенесено: %d, уже перенесённых пропущено: %d', $migrated, $skipped);
    }

    public function down()
    {
        $helper = $this->getHelperManager();
        $targetIblockId = $helper->Iblock()->getIblockIdIfExists(self::TARGET_IBLOCK_CODE, self::TARGET_IBLOCK_TYPE);
        if (!$targetIblockId) {
            return;
        }

        $rs = \CIBlockElement::GetList([], [
            'IBLOCK_ID' => $targetIblockId,
            '%XML_ID' => self::XML_ID_PREFIX,
            'CHECK_PERMISSIONS' => 'N',
        ], false, false, ['ID']);

        $deleted = 0;
        while ($row = $rs->Fetch()) {
            if (\CIBlockElement::Delete((int)$row['ID'])) {
                $deleted++;
            }
        }

        $this->outSuccess('Перенесённых партнёров удалено: %d', $deleted);
    }

    private function resolveEnumId(int $iblockId, string $propertyCode, string $xmlId): int
    {
        $enum = \CIBlockPropertyEnum::GetList([], [
            'IBLOCK_ID' => $iblockId,
            'CODE' => $propertyCode,
            'XML_ID' => $xmlId,
        ])->Fetch();

        if (!$enum) {
            throw new HelperException(
                'Значение "' . $xmlId . '" свойства ' . $propertyCode . ' не найдено — прогнана ли миграция Version20260922190001?'
            );
        }

        return (int)$enum['ID'];
    }
}
