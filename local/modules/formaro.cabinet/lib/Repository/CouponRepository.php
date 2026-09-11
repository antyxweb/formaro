<?php

namespace Formaro\Cabinet\Repository;

use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

/** Купоны — HL-блок CabinetCoupons (см. Version20260912120002). */
class CouponRepository
{
    private const HLBLOCK_NAME = 'CabinetCoupons';

    public function listOwn(int $partnerId): array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $rows = $dataClass::getList([
            'filter' => ['=UF_PARTNER_ID' => $partnerId],
            'order' => ['ID' => 'DESC'],
        ])->fetchAll();

        return array_map([$this, 'toArray'], $rows);
    }

    public function get(int $id): ?array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $row = $dataClass::getById($id)->fetch();

        return $row ? $this->toArray($row) : null;
    }

    public function canEdit(int $partnerId, array $row): bool
    {
        return $row['partner_id'] === $partnerId;
    }

    /** @throws \RuntimeException если код уже занят другим купоном (любого партнёра — код глобально уникален) */
    public function save(int $partnerId, array $payload): array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);

        $id = (int)($payload['id'] ?? 0);
        $existing = $id ? $this->get($id) : null;
        if ($existing && !$this->canEdit($partnerId, $existing)) {
            $existing = null;
            $id = 0;
        }

        $code = strtoupper(trim((string)($payload['code'] ?? '')));
        $duplicate = $dataClass::getList(['filter' => ['=UF_CODE' => $code], 'select' => ['ID']])->fetchAll();
        foreach ($duplicate as $d) {
            if ((int)$d['ID'] !== $id) {
                throw new \RuntimeException('Такой код купона уже существует');
            }
        }

        $fields = [
            'UF_CODE' => $code,
            'UF_DISCOUNT_TYPE' => (string)($payload['discount_type'] ?? 'percent'),
            'UF_VALUE' => (float)($payload['value'] ?? 0),
            'UF_USAGE_TYPE' => (string)($payload['usage_type'] ?? 'multiple'),
            'UF_DATE_FROM' => $this->toDate($payload['date_from'] ?? null),
            'UF_DATE_TO' => $this->toDate($payload['date_to'] ?? null),
            'UF_STATUS' => (string)($payload['status'] ?? 'active'),
            'UF_PARTNER_ID' => $partnerId,
        ];

        if ($existing) {
            $result = $dataClass::update($id, $fields);
        } else {
            $fields['UF_USED_COUNT'] = 0;
            $fields['UF_CREATED_AT'] = new DateTime();
            $result = $dataClass::add($fields);
        }

        if (!$result->isSuccess()) {
            throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
        }

        return $this->get($existing ? $id : $result->getId());
    }

    /** @throws \Exception если купон чужой/не найден */
    public function delete(int $partnerId, int $id): bool
    {
        $row = $this->get($id);
        if (!$row || !$this->canEdit($partnerId, $row)) {
            throw new \RuntimeException('Купон не найден или недоступен для удаления');
        }

        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $result = $dataClass::delete($id);

        return $result->isSuccess();
    }

    private function toDate(?string $iso): ?Date
    {
        return $iso ? new Date($iso, 'Y-m-d') : null;
    }

    private function toArray(array $row): array
    {
        return [
            'id' => (int)$row['ID'],
            'code' => $row['UF_CODE'],
            'discount_type' => $row['UF_DISCOUNT_TYPE'] ?: 'percent',
            'value' => (float)$row['UF_VALUE'],
            'usage_type' => $row['UF_USAGE_TYPE'] ?: 'multiple',
            'used_count' => (int)$row['UF_USED_COUNT'],
            'date_from' => $row['UF_DATE_FROM'] instanceof Date ? $row['UF_DATE_FROM']->format('Y-m-d') : null,
            'date_to' => $row['UF_DATE_TO'] instanceof Date ? $row['UF_DATE_TO']->format('Y-m-d') : null,
            'status' => $row['UF_STATUS'] ?: 'active',
            'partner_id' => (int)$row['UF_PARTNER_ID'],
            'created_at' => $row['UF_CREATED_AT'] instanceof DateTime ? $row['UF_CREATED_AT']->format('c') : null,
        ];
    }
}
