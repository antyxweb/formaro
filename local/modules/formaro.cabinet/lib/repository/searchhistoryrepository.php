<?php

namespace Formaro\Cabinet\Repository;

use Bitrix\Main\Type\DateTime;

/**
 * История поиска по каталогу авторизованного пользователя — HL-блок
 * SearchHistory (миграция Version20260929120001). Один запрос — одна
 * строка на пользователя: повторный поиск того же (без учёта регистра)
 * поднимает его наверх, а не дублирует. Хранится не больше MAX_ITEMS
 * последних запросов.
 */
class SearchHistoryRepository
{
    private const HLBLOCK_NAME = 'SearchHistory';
    private const MAX_ITEMS = 20;
    private const MAX_QUERY_LENGTH = 200;

    /** @return string[] последние запросы, самый свежий первым */
    public function listForUser(int $userId, int $limit = 5): array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $rows = $dataClass::getList([
            'select' => ['UF_QUERY'],
            'filter' => ['=UF_USER_ID' => $userId],
            'order' => ['UF_SEARCHED_AT' => 'DESC', 'ID' => 'DESC'],
            'limit' => max(1, $limit),
        ])->fetchAll();

        return array_column($rows, 'UF_QUERY');
    }

    public function add(int $userId, string $query, ?DateTime $at = null): void
    {
        $query = $this->normalize($query);
        if ($query === '') {
            return;
        }

        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $at = $at ?? new DateTime();

        $existing = null;
        $rows = $dataClass::getList([
            'select' => ['ID', 'UF_QUERY'],
            'filter' => ['=UF_USER_ID' => $userId],
        ])->fetchAll();
        foreach ($rows as $row) {
            if (mb_strtolower($row['UF_QUERY']) === mb_strtolower($query)) {
                $existing = $row;
                break;
            }
        }

        if ($existing) {
            $dataClass::update((int)$existing['ID'], ['UF_QUERY' => $query, 'UF_SEARCHED_AT' => $at]);
        } else {
            $dataClass::add(['UF_USER_ID' => $userId, 'UF_QUERY' => $query, 'UF_SEARCHED_AT' => $at]);
        }

        $this->trim($userId);
    }

    /**
     * Перенос гостевой истории (localStorage) в аккаунт после входа.
     * $queries — от самого свежего к самому старому (как хранит script.js).
     * Гостевые запросы только что вводились в этом браузере, поэтому
     * встают наверх истории аккаунта в том же порядке — время ставим
     * "сейчас" с шагом в секунду.
     */
    public function merge(int $userId, array $queries): void
    {
        $queries = array_slice(array_values(array_filter(array_map([$this, 'normalize'], $queries))), 0, self::MAX_ITEMS);
        $now = time();
        foreach (array_reverse($queries) as $i => $query) {
            $this->add($userId, $query, DateTime::createFromTimestamp($now - count($queries) + $i + 1));
        }
    }

    public function clear(int $userId): void
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $rows = $dataClass::getList(['select' => ['ID'], 'filter' => ['=UF_USER_ID' => $userId]])->fetchAll();
        foreach ($rows as $row) {
            $dataClass::delete((int)$row['ID']);
        }
    }

    private function trim(int $userId): void
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $rows = $dataClass::getList([
            'select' => ['ID'],
            'filter' => ['=UF_USER_ID' => $userId],
            'order' => ['UF_SEARCHED_AT' => 'DESC', 'ID' => 'DESC'],
            'offset' => self::MAX_ITEMS,
            'limit' => 1000,
        ])->fetchAll();
        foreach ($rows as $row) {
            $dataClass::delete((int)$row['ID']);
        }
    }

    private function normalize($query): string
    {
        $query = trim(preg_replace('/\s+/u', ' ', (string)$query));

        return mb_substr($query, 0, self::MAX_QUERY_LENGTH);
    }
}
