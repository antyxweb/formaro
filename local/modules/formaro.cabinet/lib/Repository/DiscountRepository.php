<?php

namespace Formaro\Cabinet\Repository;

use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

/**
 * Скидки — HL-блок CabinetDiscounts (см. Version20260912120002), отдельная
 * от прототипа сущность (там discounts.json хранил скидки и купоны одним
 * объектом только из-за localStorage). target_ids — реальные ELEMENT_ID
 * товаров / SECTION_ID категорий инфоблока cabinet_catalog.
 */
class DiscountRepository
{
    private const HLBLOCK_NAME = 'CabinetDiscounts';

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

    public function save(int $partnerId, array $payload): array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);

        $id = (int)($payload['id'] ?? 0);
        $existing = $id ? $this->get($id) : null;
        if ($existing && !$this->canEdit($partnerId, $existing)) {
            $existing = null;
            $id = 0;
        }

        $fields = [
            'UF_NAME' => (string)($payload['name'] ?? ''),
            'UF_TARGET_TYPE' => (string)($payload['target_type'] ?? 'all'),
            'UF_TARGET_IDS' => json_encode(array_map('intval', (array)($payload['target_ids'] ?? []))),
            'UF_DISCOUNT_TYPE' => (string)($payload['discount_type'] ?? 'percent'),
            'UF_VALUE' => (float)($payload['value'] ?? 0),
            'UF_MIN_QTY' => (int)($payload['min_qty'] ?? 0),
            'UF_MIN_AMOUNT' => (float)($payload['min_amount'] ?? 0),
            'UF_DATE_FROM' => $this->toDate($payload['date_from'] ?? null),
            'UF_DATE_TO' => $this->toDate($payload['date_to'] ?? null),
            'UF_STATUS' => (string)($payload['status'] ?? 'active'),
            'UF_PARTNER_ID' => $partnerId,
        ];

        if ($existing) {
            $result = $dataClass::update($id, $fields);
        } else {
            $fields['UF_CREATED_AT'] = new DateTime();
            $result = $dataClass::add($fields);
        }

        if (!$result->isSuccess()) {
            throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
        }

        return $this->get($existing ? $id : $result->getId());
    }

    /** @throws \Exception если скидка чужая/не найдена */
    public function delete(int $partnerId, int $id): bool
    {
        $row = $this->get($id);
        if (!$row || !$this->canEdit($partnerId, $row)) {
            throw new \RuntimeException('Скидка не найдена или недоступна для удаления');
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
            'name' => $row['UF_NAME'],
            'target_type' => $row['UF_TARGET_TYPE'] ?: 'all',
            'target_ids' => json_decode((string)$row['UF_TARGET_IDS'], true) ?: [],
            'discount_type' => $row['UF_DISCOUNT_TYPE'] ?: 'percent',
            'value' => (float)$row['UF_VALUE'],
            'min_qty' => (int)$row['UF_MIN_QTY'],
            'min_amount' => (float)$row['UF_MIN_AMOUNT'],
            'date_from' => $row['UF_DATE_FROM'] instanceof Date ? $row['UF_DATE_FROM']->format('Y-m-d') : null,
            'date_to' => $row['UF_DATE_TO'] instanceof Date ? $row['UF_DATE_TO']->format('Y-m-d') : null,
            'status' => $row['UF_STATUS'] ?: 'active',
            'partner_id' => (int)$row['UF_PARTNER_ID'],
            'created_at' => $row['UF_CREATED_AT'] instanceof DateTime ? $row['UF_CREATED_AT']->format('c') : null,
        ];
    }
}
