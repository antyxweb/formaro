<?php

namespace Formaro\Cabinet\Repository;

use Bitrix\Main\Type\DateTime;

/**
 * Заказы — HL-блок CabinetOrders (см. Version20260912120001). Формат
 * возвращаемых/принимаемых массивов совпадает с cabinet-html/data/
 * orders.json, чтобы orders.js/order-detail.js работали почти без
 * переделки. В отличие от прототипа (где владение выводилось из
 * items[].product_id) здесь UF_PARTNER_ID хранится явно.
 */
class OrderRepository
{
    private const HLBLOCK_NAME = 'CabinetOrders';

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

    /**
     * @param array $payload id?/status/delivery_method/payment_method/
     *                       payment_status/customer{}/customer_comment/
     *                       items[]/subtotal/total/discount_id/discount_name/
     *                       coupon_code/discount_amount/history[]
     */
    public function save(int $partnerId, array $payload): array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);

        $id = (int)($payload['id'] ?? 0);
        $existing = $id ? $this->get($id) : null;

        // См. CategoryRepository::save() — не найдено/не моё => это создание,
        // а не обновление чужого заказа.
        if ($existing && !$this->canEdit($partnerId, $existing)) {
            $existing = null;
            $id = 0;
        }

        $fields = [
            'UF_STATUS' => (string)($payload['status'] ?? 'new'),
            'UF_CUSTOMER' => json_encode($payload['customer'] ?? [], JSON_UNESCAPED_UNICODE),
            'UF_ITEMS' => json_encode($payload['items'] ?? [], JSON_UNESCAPED_UNICODE),
            'UF_SUBTOTAL' => (float)($payload['subtotal'] ?? 0),
            'UF_TOTAL' => (float)($payload['total'] ?? 0),
            'UF_DELIVERY_METHOD' => (string)($payload['delivery_method'] ?? ''),
            'UF_PAYMENT_METHOD' => (string)($payload['payment_method'] ?? ''),
            'UF_PAYMENT_STATUS' => (string)($payload['payment_status'] ?? 'awaiting'),
            'UF_CUSTOMER_COMMENT' => (string)($payload['customer_comment'] ?? ''),
            'UF_HISTORY' => json_encode($payload['history'] ?? [], JSON_UNESCAPED_UNICODE),
            'UF_DISCOUNT_ID' => (int)($payload['discount_id'] ?? 0),
            'UF_DISCOUNT_NAME' => (string)($payload['discount_name'] ?? ''),
            'UF_COUPON_CODE' => (string)($payload['coupon_code'] ?? ''),
            'UF_DISCOUNT_AMOUNT' => (float)($payload['discount_amount'] ?? 0),
            'UF_PARTNER_ID' => $partnerId,
        ];

        if ($existing) {
            $result = $dataClass::update($id, $fields);
        } else {
            // order_number считает сервер по настоящему id (в прототипе это
            // делал клиент по временному id из dsNextId — с реальной БД он
            // не совпал бы с фактически присвоенным).
            $fields['UF_ORDER_NUMBER'] = 'F-' . (100200 + $this->nextSequence());
            $fields['UF_CREATED_AT'] = new DateTime();
            $result = $dataClass::add($fields);
        }

        if (!$result->isSuccess()) {
            throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
        }

        return $this->get($existing ? $id : $result->getId());
    }

    /** @throws \Exception если заказ чужой/не найден */
    public function delete(int $partnerId, int $id): bool
    {
        $row = $this->get($id);
        if (!$row || !$this->canEdit($partnerId, $row)) {
            throw new \RuntimeException('Заказ не найден или недоступен для удаления');
        }

        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $result = $dataClass::delete($id);

        return $result->isSuccess();
    }

    /** Простой инкремент на основе текущего количества строк — только для
        человекочитаемого номера заказа, не первичный ключ. */
    private function nextSequence(): int
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $last = $dataClass::getList(['select' => ['ID'], 'order' => ['ID' => 'DESC'], 'limit' => 1])->fetch();

        return $last ? (int)$last['ID'] : 0;
    }

    private function toArray(array $row): array
    {
        return [
            'id' => (int)$row['ID'],
            'order_number' => $row['UF_ORDER_NUMBER'],
            'status' => $row['UF_STATUS'] ?: 'new',
            'created_at' => $row['UF_CREATED_AT'] instanceof DateTime ? $row['UF_CREATED_AT']->format('c') : (string)$row['UF_CREATED_AT'],
            'customer' => json_decode((string)$row['UF_CUSTOMER'], true) ?: ['name' => '', 'phone' => '', 'email' => '', 'address' => ''],
            'items' => json_decode((string)$row['UF_ITEMS'], true) ?: [],
            'subtotal' => (float)$row['UF_SUBTOTAL'],
            'total' => (float)$row['UF_TOTAL'],
            'delivery_method' => $row['UF_DELIVERY_METHOD'],
            'payment_method' => $row['UF_PAYMENT_METHOD'],
            'payment_status' => $row['UF_PAYMENT_STATUS'] ?: 'awaiting',
            'customer_comment' => $row['UF_CUSTOMER_COMMENT'],
            'history' => json_decode((string)$row['UF_HISTORY'], true) ?: [],
            'discount_id' => $row['UF_DISCOUNT_ID'] ? (int)$row['UF_DISCOUNT_ID'] : null,
            'discount_name' => $row['UF_DISCOUNT_NAME'],
            'coupon_code' => $row['UF_COUPON_CODE'] ?: null,
            'discount_amount' => (float)$row['UF_DISCOUNT_AMOUNT'],
            'partner_id' => (int)$row['UF_PARTNER_ID'],
        ];
    }
}
