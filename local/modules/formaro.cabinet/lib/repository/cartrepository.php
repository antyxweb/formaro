<?php

namespace Formaro\Cabinet\Repository;

use Bitrix\Main\Type\DateTime;

/**
 * Корзина авторизованного пользователя — HL-блок CartItems (миграция
 * Version20260929160001): строка на пару пользователь+товар с количеством.
 * Наружу — только товары, которые сейчас можно купить (normalize()):
 * активные и в наличии или под заказ; количество не больше остатка
 * (у товара под заказ — без ограничения).
 */
class CartRepository
{
    private const HLBLOCK_NAME = 'CartItems';
    public const MAX_ITEMS = 200;
    public const MAX_QTY = 9999;

    /** @return array<int, array{id: int, qty: int}> в порядке добавления */
    public function listItems(int $userId): array
    {
        $rows = $this->rows($userId);

        return self::normalize(array_map(
            static fn(array $r) => ['id' => (int)$r['UF_PRODUCT_ID'], 'qty' => (int)$r['UF_QUANTITY']],
            $rows
        ));
    }

    /** Положить товар / поменять количество; qty <= 0 — убрать. */
    public function set(int $userId, int $productId, int $qty): void
    {
        if ($qty <= 0) {
            $this->remove($userId, [$productId]);
            return;
        }
        $item = self::normalize([['id' => $productId, 'qty' => $qty]])[0] ?? null;
        if (!$item) {
            return;
        }

        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $exists = $dataClass::getList([
            'select' => ['ID'],
            'filter' => ['=UF_USER_ID' => $userId, '=UF_PRODUCT_ID' => $item['id']],
            'limit' => 1,
        ])->fetch();
        if ($exists) {
            $dataClass::update((int)$exists['ID'], ['UF_QUANTITY' => $item['qty']]);
        } elseif (count($this->rows($userId)) < self::MAX_ITEMS) {
            $dataClass::add(['UF_USER_ID' => $userId, 'UF_PRODUCT_ID' => $item['id'], 'UF_QUANTITY' => $item['qty'], 'UF_ADDED_AT' => new DateTime()]);
        }
    }

    /** @param int[] $productIds */
    public function remove(int $userId, array $productIds): void
    {
        $productIds = array_values(array_filter(array_map('intval', $productIds)));
        if (!$productIds) {
            return;
        }
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $rows = $dataClass::getList([
            'select' => ['ID'],
            'filter' => ['=UF_USER_ID' => $userId, '@UF_PRODUCT_ID' => $productIds],
        ])->fetchAll();
        foreach ($rows as $row) {
            $dataClass::delete((int)$row['ID']);
        }
    }

    /** Перенос гостевой корзины (localStorage) после входа: чего нет — добавляем,
     *  что уже есть — берём большее количество. */
    public function merge(int $userId, array $items): void
    {
        $current = [];
        foreach ($this->rows($userId) as $row) {
            $current[(int)$row['UF_PRODUCT_ID']] = (int)$row['UF_QUANTITY'];
        }
        foreach (array_slice($items, 0, self::MAX_ITEMS) as $item) {
            $id = (int)($item['id'] ?? 0);
            $qty = (int)($item['qty'] ?? 0);
            if ($id > 0 && $qty > 0) {
                $this->set($userId, $id, max($qty, $current[$id] ?? 0));
            }
        }
    }

    /**
     * Только то, что можно купить, с количеством в допустимых пределах;
     * повторы товара схлопываются, порядок сохраняется.
     *
     * @param array<array{id: int, qty: int}> $items
     * @return array<int, array{id: int, qty: int}>
     */
    public static function normalize(array $items): array
    {
        $qtyById = [];
        foreach ($items as $item) {
            $id = (int)($item['id'] ?? 0);
            $qty = (int)($item['qty'] ?? 0);
            if ($id > 0 && $qty > 0 && !isset($qtyById[$id])) {
                $qtyById[$id] = min($qty, self::MAX_QTY);
            }
        }
        if (!$qtyById) {
            return [];
        }

        $products = [];
        foreach ((new ProductRepository())->findPublic(['ID' => array_keys($qtyById)], ['ID' => 'ASC'], count($qtyById)) as $p) {
            $products[$p['id']] = $p;
        }

        $result = [];
        foreach ($qtyById as $id => $qty) {
            $p = $products[$id] ?? null;
            if (!$p || ($p['stock'] <= 0 && !$p['is_preorder'])) {
                continue;
            }
            if ($p['stock'] > 0 && !$p['is_preorder']) {
                $qty = min($qty, $p['stock']);
            }
            $result[] = ['id' => $id, 'qty' => $qty];
        }

        return $result;
    }

    private function rows(int $userId): array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);

        return $dataClass::getList([
            'select' => ['ID', 'UF_PRODUCT_ID', 'UF_QUANTITY'],
            'filter' => ['=UF_USER_ID' => $userId],
            'order' => ['UF_ADDED_AT' => 'ASC', 'ID' => 'ASC'],
        ])->fetchAll();
    }
}
