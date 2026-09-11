<?php

namespace Formaro\Cabinet\Repository;

use CFile;
use Formaro\Cabinet\Upload\FileUploader;

/**
 * Документы партнёра — HL-блок CabinetPartnerDocuments. В отличие от
 * прототипа (только метаданные — имя/размер/дата, см. cabinet-html/CLAUDE.md)
 * здесь UF_FILE — настоящий загруженный файл.
 */
class PartnerDocumentRepository
{
    private const HLBLOCK_NAME = 'CabinetPartnerDocuments';

    public function listByPartner(int $partnerId): array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $rows = $dataClass::getList([
            'filter' => ['=UF_PARTNER_ID' => $partnerId],
            'order' => ['UF_UPLOADED_AT' => 'DESC'],
        ])->fetchAll();

        return array_map([$this, 'toArray'], $rows);
    }

    public function add(int $partnerId, string $name, string $dataUrl): array
    {
        $fileId = FileUploader::saveFromDataUrl($dataUrl, 'cabinet/partner_documents');
        if (!$fileId) {
            throw new \RuntimeException('Не удалось загрузить файл');
        }

        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $result = $dataClass::add([
            'UF_PARTNER_ID' => $partnerId,
            'UF_NAME' => $name,
            'UF_FILE' => $fileId,
            'UF_UPLOADED_AT' => new \Bitrix\Main\Type\DateTime(),
        ]);

        if (!$result->isSuccess()) {
            FileUploader::delete($fileId);
            throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
        }

        return $this->toArray($dataClass::getById($result->getId())->fetch());
    }

    /** @throws \Exception если документ чужой/не найден */
    public function delete(int $partnerId, int $id): bool
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $row = $dataClass::getById($id)->fetch();
        if (!$row || (int)$row['UF_PARTNER_ID'] !== $partnerId) {
            throw new \RuntimeException('Документ не найден или недоступен для удаления');
        }

        FileUploader::delete($row['UF_FILE'] ?: null);
        $result = $dataClass::delete($id);

        return $result->isSuccess();
    }

    private function toArray(array $row): array
    {
        $fileArray = $row['UF_FILE'] ? CFile::GetFileArray($row['UF_FILE']) : null;

        return [
            'id' => (int)$row['ID'],
            'name' => $row['UF_NAME'],
            'size' => $fileArray['FILE_SIZE'] ?? 0,
            'url' => $fileArray ? FileUploader::getPath((int)$row['UF_FILE']) : null,
            'uploaded_at' => $row['UF_UPLOADED_AT'] instanceof \Bitrix\Main\Type\DateTime
                ? $row['UF_UPLOADED_AT']->format('c')
                : (string)$row['UF_UPLOADED_AT'],
        ];
    }
}
