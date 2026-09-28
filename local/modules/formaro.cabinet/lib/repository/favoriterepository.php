<?php

namespace Formaro\Cabinet\Repository;

use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use CIBlockElement;

/**
 * Избранные товары авторизованного пользователя — HL-блок Favorites
 * (миграция Version20260929130001). Одна строка на пару
 * пользователь+товар. Наружу отдаются только товары, которые сейчас
 * активны в cabinet_catalog: удалённый/скрытый партнёром товар пропадает
 * из избранного и из счётчика в шапке.
 */
class FavoriteRepository
{
    private const HLBLOCK_NAME = 'Favorites';
    private const MAX_ITEMS = 500;

    /** @return int[] ID товаров, последние добавленные первыми */
    public function listIds(int $userId): array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $rows = $dataClass::getList([
            'select' => ['UF_PRODUCT_ID'],
            'filter' => ['=UF_USER_ID' => $userId],
            'order' => ['UF_ADDED_AT' => 'DESC', 'ID' => 'DESC'],
        ])->fetchAll();

        return $this->onlyActive(array_map('intval', array_column($rows, 'UF_PRODUCT_ID')));
    }

    public function count(int $userId): int
    {
        return count($this->listIds($userId));
    }

    public function add(int $userId, int $productId): void
    {
        if ($productId <= 0 || !$this->onlyActive([$productId])) {
            return;
        }

        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $exists = $dataClass::getList([
            'select' => ['ID'],
            'filter' => ['=UF_USER_ID' => $userId, '=UF_PRODUCT_ID' => $productId],
            'limit' => 1,
        ])->fetch();
        if ($exists) {
            return;
        }

        $dataClass::add(['UF_USER_ID' => $userId, 'UF_PRODUCT_ID' => $productId, 'UF_ADDED_AT' => new DateTime()]);
    }

    public function remove(int $userId, int $productId): void
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $rows = $dataClass::getList([
            'select' => ['ID'],
            'filter' => ['=UF_USER_ID' => $userId, '=UF_PRODUCT_ID' => $productId],
        ])->fetchAll();
        foreach ($rows as $row) {
            $dataClass::delete((int)$row['ID']);
        }
    }

    /** Перенос гостевого избранного (localStorage) в аккаунт после входа. */
    public function merge(int $userId, array $productIds): void
    {
        $productIds = array_slice(array_values(array_unique(array_filter(array_map('intval', $productIds)))), 0, self::MAX_ITEMS);
        // Гость хранит последние добавленные первыми — добавляем с конца,
        // чтобы порядок по дате сохранился.
        foreach (array_reverse($productIds) as $productId) {
            $this->add($userId, $productId);
        }
    }

    /** @param int[] $ids @return int[] только активные товары cabinet_catalog, порядок сохраняется */
    private function onlyActive(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));
        if (!$ids || !Loader::includeModule('iblock')) {
            return [];
        }

        $active = [];
        $res = CIBlockElement::GetList(
            [],
            ['IBLOCK_CODE' => 'cabinet_catalog', 'ID' => $ids, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
            false,
            false,
            ['ID']
        );
        while ($row = $res->Fetch()) {
            $active[(int)$row['ID']] = true;
        }

        return array_values(array_filter($ids, static fn(int $id) => isset($active[$id])));
    }
}
