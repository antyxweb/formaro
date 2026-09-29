<?php

namespace Formaro\Cabinet\Repository;

use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use CIBlockElement;

/**
 * Просмотренные товары авторизованного пользователя — HL-блок
 * ViewedProducts (миграция Version20260929150001). Одна строка на пару
 * пользователь+товар; повторный просмотр поднимает товар наверх. Хранятся
 * последние MAX_ITEMS, более старые удаляются. Наружу — только товары,
 * активные сейчас в cabinet_catalog.
 */
class ViewedRepository
{
    private const HLBLOCK_NAME = 'ViewedProducts';
    public const MAX_ITEMS = 20;

    /** @return int[] ID товаров, последние просмотренные первыми */
    public function listIds(int $userId): array
    {
        return $this->onlyActive(array_map('intval', array_column($this->rows($userId), 'UF_PRODUCT_ID')));
    }

    public function add(int $userId, int $productId, ?DateTime $viewedAt = null): void
    {
        if ($productId <= 0 || !$this->onlyActive([$productId])) {
            return;
        }

        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $viewedAt = $viewedAt ?? new DateTime();
        $exists = $dataClass::getList([
            'select' => ['ID'],
            'filter' => ['=UF_USER_ID' => $userId, '=UF_PRODUCT_ID' => $productId],
            'limit' => 1,
        ])->fetch();
        if ($exists) {
            $dataClass::update((int)$exists['ID'], ['UF_VIEWED_AT' => $viewedAt]);
        } else {
            $dataClass::add(['UF_USER_ID' => $userId, 'UF_PRODUCT_ID' => $productId, 'UF_VIEWED_AT' => $viewedAt]);
        }

        $this->trim($userId);
    }

    /** Перенос гостевого списка (localStorage, последние первыми) после входа:
     *  гостевые просмотры — самые свежие, поэтому встают в начало. */
    public function merge(int $userId, array $productIds): void
    {
        $productIds = array_slice(array_values(array_unique(array_filter(array_map('intval', $productIds)))), 0, self::MAX_ITEMS);
        $now = time();
        foreach (array_reverse($productIds) as $i => $productId) {
            $this->add($userId, $productId, DateTime::createFromTimestamp($now - count($productIds) + $i + 1));
        }
    }

    /** Оставляем последние MAX_ITEMS. */
    private function trim(int $userId): void
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        foreach (array_slice($this->rows($userId), self::MAX_ITEMS) as $row) {
            $dataClass::delete((int)$row['ID']);
        }
    }

    private function rows(int $userId): array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);

        return $dataClass::getList([
            'select' => ['ID', 'UF_PRODUCT_ID'],
            'filter' => ['=UF_USER_ID' => $userId],
            'order' => ['UF_VIEWED_AT' => 'DESC', 'ID' => 'DESC'],
        ])->fetchAll();
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
