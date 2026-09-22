<?php

namespace Formaro\Cabinet\Repository;

use Bitrix\Main\Type\DateTime;

/**
 * Уведомления партнёра — HL-блок CabinetNotifications (см. миграцию
 * Version20260912140001). В отличие от большинства сущностей кабинета,
 * это read-only список с точки зрения партнёра — он может только читать
 * и помечать прочитанным/прочитанными, не создавать/редактировать/удалять.
 *
 * Ничто в этой версии модуля автоматически не создаёт здесь записи при
 * реальных событиях (новый заказ, сообщение в чате, ответ поддержки и
 * т.д.) — см. комментарий в миграции. Формат совпадает с cabinet-html/
 * data/notifications.json, чтобы notifications.js работал без переделки.
 */
class NotificationRepository
{
    private const HLBLOCK_NAME = 'CabinetNotifications';

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
