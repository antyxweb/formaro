<?php

namespace Formaro\Cabinet\Repository;

use Bitrix\Main\Type\DateTime;

/**
 * Заказы — HL-блок CabinetOrders (см. Version20260912120001). Формат
 * возвращаемых/принимаемых массивов совпадает с cabinet-html/data/
 * orders.json, чтобы orders.js/order-detail.js работали почти без
 * переделки. В отличие от прототипа (где владение выводилось из
 * items[].product_id) здесь UF_PARTNER_ID хранится явно.
 *
 * Заказы с витрины (CartCheckoutService) — с UF_USER_ID покупателя
 * (задаётся только при создании; у заказов, созданных партнёром вручную,
 * — 0).
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

    /** Все заказы всех партнёров — кабинет администратора. */
    public function listAll(): array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $rows = $dataClass::getList(['order' => ['ID' => 'DESC']])->fetchAll();

        return array_map([$this, 'toArray'], $rows);
    }

    public const PAYMENT_STATUSES = ['awaiting' => 'Ожидает оплаты', 'paid' => 'Оплачен', 'refunded' => 'Возврат'];

    /**
     * Статус оплаты из кабинета администратора (оплату подтверждает
     * площадка, партнёр его не меняет): запись в историю заказа —
     * уведомления покупателю и партнёру отправляет вызывающий
     * (кабинет администратора). @return array [до, после]
     */
    public function setPaymentStatus(int $id, string $status): array
    {
        if (!isset(self::PAYMENT_STATUSES[$status])) {
            throw new \RuntimeException('Неизвестный статус оплаты');
        }
        $before = $this->get($id);
        if (!$before) {
            throw new \RuntimeException('Заказ не найден');
        }
        if ($before['payment_status'] === $status) {
            return [$before, $before];
        }
        $text = match ($status) {
            'paid' => 'Оплата подтверждена площадкой',
            'refunded' => 'Площадка оформила возврат оплаты',
            default => 'Отметка об оплате снята площадкой',
        };
        $history = $before['history'];
        $history[] = ['date' => (new \DateTime())->format('c'), 'text' => $text, 'author' => 'Площадка'];

        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $result = $dataClass::update($id, [
            'UF_PAYMENT_STATUS' => $status,
            'UF_HISTORY' => json_encode($history, JSON_UNESCAPED_UNICODE),
        ]);
        if (!$result->isSuccess()) {
            throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
        }

        return [$before, $this->get($id)];
    }

    /** Комментарий в историю заказа (кабинет администратора, автор «Площадка»). */
    public function addHistory(int $id, string $text, string $author): array
    {
        $text = trim($text);
        if ($text === '') {
            throw new \RuntimeException('Введите комментарий');
        }
        $order = $this->get($id);
        if (!$order) {
            throw new \RuntimeException('Заказ не найден');
        }
        $history = $order['history'];
        $history[] = ['date' => (new \DateTime())->format('c'), 'text' => $text, 'author' => $author];
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $dataClass::update($id, ['UF_HISTORY' => json_encode($history, JSON_UNESCAPED_UNICODE)]);

        return $this->get($id);
    }

    /** Число заказов покупателя с витрины — счётчик в шапке сайта и меню
     *  личного кабинета. */
    public function countByUser(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);

        return (int)$dataClass::getCount(['=UF_USER_ID' => $userId]);
    }

    /** Последние заказы покупателя с витрины, новые первыми — для
     *  предзаполнения формы оформления и «Ваших заказов» (/personal/orders/). */
    public function listRecentByUser(int $userId, int $limit): array
    {
        if ($userId <= 0) {
            return [];
        }
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $rows = $dataClass::getList([
            'filter' => ['=UF_USER_ID' => $userId],
            'order' => ['ID' => 'DESC'],
            'limit' => $limit,
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
     *                       coupon_code/discount_amount/history[]/
     *                       user_id (только при создании)
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
            // Покупатель привязывается один раз: к ещё не привязанному заказу.
            if (!$existing['user_id'] && (int)($payload['user_id'] ?? 0) > 0) {
                $fields['UF_USER_ID'] = (int)$payload['user_id'];
            }
            $result = $dataClass::update($id, $fields);
        } else {
            // order_number считает сервер по настоящему id (в прототипе это
            // делал клиент по временному id из dsNextId — с реальной БД он
            // не совпал бы с фактически присвоенным).
            $fields['UF_ORDER_NUMBER'] = 'F-' . (100200 + $this->nextSequence());
            $fields['UF_CREATED_AT'] = new DateTime();
            $fields['UF_USER_ID'] = (int)($payload['user_id'] ?? 0);
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
        $order = [
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
            'user_id' => (int)($row['UF_USER_ID'] ?? 0),
            // Согласование и счёт (OrderInvoiceFlowService, миграция Version20261007100001).
            'supplier_confirmed_at' => self::dateIso($row['UF_SUPPLIER_CONFIRMED_AT'] ?? null),
            'supplier_lead_time' => (string)($row['UF_SUPPLIER_LEAD_TIME'] ?? ''),
            'invoice_status' => (string)($row['UF_INVOICE_STATUS'] ?? ''),
            'invoice_date' => self::dateIso($row['UF_INVOICE_DATE'] ?? null),
            'invoice_due' => self::dateIso($row['UF_INVOICE_DUE'] ?? null),
        ];
        $order['supplier_total'] = self::supplierTotal($order);

        return $order;
    }

    /**
     * Наценка площадки (MarkupService): у позиции заказа price — цена для
     * покупателя, supplier_price — цена поставщика (у старых заказов нет —
     * считается равной price). Скидка заказа одна для обоих: итог поставщика
     * = его подытог − скидка; разница с итогом покупателя — комиссия.
     */
    public static function supplierSubtotal(array $order): float
    {
        return round(array_sum(array_map(
            static fn($it) => (float)($it['supplier_price'] ?? $it['price'] ?? 0) * (int)($it['qty'] ?? 0),
            (array)($order['items'] ?? [])
        )), 2);
    }

    public static function supplierTotal(array $order): float
    {
        return max(0.0, round(self::supplierSubtotal($order) - (float)($order['discount_amount'] ?? 0), 2));
    }

    /**
     * Заказ глазами поставщика (кабинет партнёра): его цены и суммы, без
     * наценки площадки. Обратно (сохранение из кабинета партнёра) —
     * fromPartnerView().
     */
    public static function partnerView(array $order): array
    {
        $order['items'] = array_map(static function (array $it) {
            $it['price'] = (float)($it['supplier_price'] ?? $it['price'] ?? 0);
            unset($it['supplier_price']);

            return $it;
        }, (array)$order['items']);
        $order['subtotal'] = self::supplierSubtotal($order);
        $order['total'] = max(0.0, round($order['subtotal'] - (float)$order['discount_amount'], 2));

        return $order;
    }

    /**
     * Заказ из кабинета партнёра (цены — поставщика) → как хранится: у
     * позиций supplier_price = присланная цена, price — цена для
     * покупателя. Позиция с той же ценой поставщика, что была, сохраняет
     * прежнюю цену покупателя (в ней могла быть скидка); новая или
     * изменённая — цена поставщика с наценкой (MarkupService::retail()).
     */
    public static function fromPartnerView(array $row, ?array $before): array
    {
        $old = [];
        foreach ((array)($before['items'] ?? []) as $it) {
            $key = (int)($it['product_id'] ?? 0) . '|' . (float)($it['supplier_price'] ?? $it['price'] ?? 0);
            $old[$key] = (float)($it['price'] ?? 0);
        }
        $row['items'] = array_map(static function (array $it) use ($old) {
            $supplier = round((float)($it['price'] ?? 0), 2);
            $key = (int)($it['product_id'] ?? 0) . '|' . $supplier;
            $it['supplier_price'] = $supplier;
            $it['price'] = $old[$key] ?? \Formaro\Cabinet\Service\MarkupService::retail($supplier);

            return $it;
        }, array_values((array)($row['items'] ?? [])));
        $row['subtotal'] = round(array_sum(array_map(static fn($it) => $it['price'] * (int)$it['qty'], $row['items'])), 2);
        $row['total'] = max(0.0, round($row['subtotal'] - (float)($row['discount_amount'] ?? 0), 2));

        return $row;
    }

    private static function dateIso($value): ?string
    {
        return $value instanceof DateTime ? $value->format('c') : null;
    }

    /**
     * Точечная запись полей заказа (UF_*) — согласование и счёт
     * (OrderInvoiceFlowService); история — массивом, как в toArray().
     */
    public function updateFields(int $id, array $fields): array
    {
        if (isset($fields['UF_HISTORY']) && is_array($fields['UF_HISTORY'])) {
            $fields['UF_HISTORY'] = json_encode(array_values($fields['UF_HISTORY']), JSON_UNESCAPED_UNICODE);
        }
        foreach (['UF_ITEMS', 'UF_CUSTOMER'] as $json) {
            if (isset($fields[$json]) && is_array($fields[$json])) {
                $fields[$json] = json_encode($fields[$json], JSON_UNESCAPED_UNICODE);
            }
        }
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $result = $dataClass::update($id, $fields);
        if (!$result->isSuccess()) {
            throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
        }

        return $this->get($id);
    }

    /** Выставленные и неоплаченные счета со сроком раньше $before — для аннулирования. */
    public function listOverdueInvoices(DateTime $before): array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $rows = $dataClass::getList([
            'filter' => [
                '=UF_INVOICE_STATUS' => 'issued',
                '<UF_INVOICE_DUE' => $before,
                '!=UF_PAYMENT_STATUS' => 'paid',
                '!=UF_STATUS' => 'cancelled',
            ],
        ])->fetchAll();

        return array_map([$this, 'toArray'], $rows);
    }
}
