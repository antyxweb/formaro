<?php

namespace Formaro\Cabinet\Repository;

use Bitrix\Main\Type\DateTime;

/**
 * Уведомления партнёра — HL-блок CabinetNotifications (см. миграцию
 * Version20260912140001). Партнёр не создаёт и не редактирует
 * уведомления — только читает, помечает прочитанными и удаляет (по одному
 * и группой, markReadMany()/deleteMany()).
 *
 * Записи создаёт сайт при событиях (add()): пока — новый заказ с витрины
 * (CartCheckoutService); сообщения чата, ответы поддержки и т.д. — ещё
 * нет, см. комментарий в миграции. Формат совпадает с cabinet-html/
 * data/notifications.json, чтобы notifications.js работал без переделки.
 */
class NotificationRepository
{
    private const HLBLOCK_NAME = 'CabinetNotifications';

    /** Новое уведомление партнёру; $type — order/chat/support/system/finance/product. */
    public function add(int $partnerId, string $type, string $title, string $message): void
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $dataClass::add([
            'UF_TYPE' => $type,
            'UF_TITLE' => $title,
            'UF_MESSAGE' => $message,
            'UF_IS_READ' => false,
            'UF_PARTNER_ID' => $partnerId,
            'UF_CREATED_AT' => new DateTime(),
        ]);
    }

    public function listOwn(int $partnerId): array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $rows = $dataClass::getList([
            'filter' => ['=UF_PARTNER_ID' => $partnerId],
            'order' => ['UF_CREATED_AT' => 'DESC', 'ID' => 'DESC'],
        ])->fetchAll();

        return array_map([$this, 'toArray'], $rows);
    }

    /** @throws \Exception если уведомление чужое/не найдено */
    public function markRead(int $partnerId, int $id): array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $row = $dataClass::getById($id)->fetch();
        if (!$row || (int)$row['UF_PARTNER_ID'] !== $partnerId) {
            throw new \RuntimeException('Уведомление не найдено');
        }

        if (empty($row['UF_IS_READ'])) {
            $dataClass::update($id, ['UF_IS_READ' => true]);
            $row['UF_IS_READ'] = true;
        }

        return $this->toArray($row);
    }

    /** @return int сколько уведомлений было помечено прочитанными */
    public function markAllRead(int $partnerId): int
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $unread = $dataClass::getList([
            'select' => ['ID'],
            'filter' => ['=UF_PARTNER_ID' => $partnerId, '=UF_IS_READ' => false],
        ])->fetchAll();

        foreach ($unread as $row) {
            $dataClass::update((int)$row['ID'], ['UF_IS_READ' => true]);
        }

        return count($unread);
    }

    /** Свои из $ids — прочитанными. @return int сколько помечено */
    public function markReadMany(int $partnerId, array $ids): int
    {
        $rows = $this->ownRows($partnerId, $ids, ['=UF_IS_READ' => false]);
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        foreach ($rows as $row) {
            $dataClass::update((int)$row['ID'], ['UF_IS_READ' => true]);
        }

        return count($rows);
    }

    /** Удалить свои из $ids (чужие id молча пропускаются). @return int[] удалённые id */
    public function deleteMany(int $partnerId, array $ids): array
    {
        $deleted = [];
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        foreach ($this->ownRows($partnerId, $ids) as $row) {
            if ($dataClass::delete((int)$row['ID'])->isSuccess()) {
                $deleted[] = (int)$row['ID'];
            }
        }

        return $deleted;
    }

    private function ownRows(int $partnerId, array $ids, array $filter = []): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) {
            return [];
        }
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);

        return $dataClass::getList([
            'select' => ['ID'],
            'filter' => array_merge(['=UF_PARTNER_ID' => $partnerId, '@ID' => $ids], $filter),
        ])->fetchAll();
    }

    private function toArray(array $row): array
    {
        return [
            'id' => (int)$row['ID'],
            'type' => $row['UF_TYPE'],
            'title' => $row['UF_TITLE'],
            'message' => $row['UF_MESSAGE'],
            'is_read' => !empty($row['UF_IS_READ']),
            'created_at' => $row['UF_CREATED_AT'] instanceof DateTime
                ? $row['UF_CREATED_AT']->format('c')
                : ($row['UF_CREATED_AT'] ? (string)$row['UF_CREATED_AT'] : null),
        ];
    }
}
