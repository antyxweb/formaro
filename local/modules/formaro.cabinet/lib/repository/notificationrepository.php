<?php

namespace Formaro\Cabinet\Repository;

use Bitrix\Main\Type\DateTime;
use Formaro\Cabinet\Mail\Mailer;

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
 *
 * Уведомления покупателя (/personal/notify/) — в том же HL-блоке
 * (миграция Version20261001150001): владелец — UF_USER_ID, UF_PARTNER_ID = 0.
 * Экземпляр forBuyer() читает/помечает/удаляет по UF_USER_ID, создаёт —
 * addForBuyer(); методы те же, вместо id партнёра — id пользователя.
 *
 * Каждая новая запись дублируется письмом (Mailer::notification(),
 * события FORMARO_BUYER_*, FORMARO_PARTNER_*, FORMARO_ADMIN_*);
 * $mail = false — когда у случая своё письмо (счёт, заказ оформлен).
 */
class NotificationRepository
{
    private const HLBLOCK_NAME = 'CabinetNotifications';
    /** Владелец уведомлений администраторов (UF_ADMIN), см. forAdmin(). */
    public const ADMIN_OWNER = 1;

    /** Поле владельца: UF_PARTNER_ID (кабинет партнёра) или UF_USER_ID (покупатель). */
    private string $ownerField;

    public function __construct(string $ownerField = 'UF_PARTNER_ID')
    {
        $this->ownerField = $ownerField;
    }

    /** Уведомления покупателя: вместо id партнёра — id пользователя. */
    public static function forBuyer(): self
    {
        return new self('UF_USER_ID');
    }

    /**
     * Общая лента администраторов площадки (кабинет /admin/, миграция
     * Version20261004100001): UF_ADMIN = ADMIN_OWNER — вместо id партнёра
     * во все методы передаётся ADMIN_OWNER.
     */
    public static function forAdmin(): self
    {
        return new self('UF_ADMIN');
    }

    /**
     * Уведомление администраторам; $type — moderation/finance/support.
     * $link — путь от корня кабинета администратора (partners/edit/5/).
     * Ошибка записи не мешает действию, из-за которого уведомляем.
     */
    public static function notifyAdmin(string $type, string $title, string $message, string $link = '', bool $mail = true): void
    {
        try {
            $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
            $dataClass::add([
                'UF_TYPE' => $type,
                'UF_TITLE' => $title,
                'UF_MESSAGE' => $message,
                'UF_IS_READ' => false,
                'UF_PARTNER_ID' => 0,
                'UF_ADMIN' => self::ADMIN_OWNER,
                'UF_CREATED_AT' => new DateTime(),
                'UF_LINK' => $link,
            ]);
        } catch (\Throwable $e) {
        }
        if ($mail) {
            Mailer::notification('admin', $type, self::ADMIN_OWNER, $title, $message, $link);
        }
    }

    /**
     * Новое уведомление партнёру; $type — order/chat/support/system/finance/product.
     * $link — куда ведёт клик в кабинете: путь от корня кабинета
     * (orders/edit/14/); пусто — в раздел по типу (notifications.js).
     */
    public function add(int $partnerId, string $type, string $title, string $message, string $link = '', bool $mail = true): void
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $dataClass::add([
            'UF_TYPE' => $type,
            'UF_TITLE' => $title,
            'UF_MESSAGE' => $message,
            'UF_IS_READ' => false,
            'UF_PARTNER_ID' => $partnerId,
            'UF_CREATED_AT' => new DateTime(),
            'UF_LINK' => $link,
        ]);
        if ($mail) {
            Mailer::notification('partner', $type, $partnerId, $title, $message, $link);
        }
    }

    /**
     * Уведомление покупателю; $type — order/system. $link — адрес на сайте
     * (/personal/orders/#order-14), пусто — без перехода.
     */
    public function addForBuyer(int $userId, string $type, string $title, string $message, string $link = '', bool $mail = true): void
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $dataClass::add([
            'UF_TYPE' => $type,
            'UF_TITLE' => $title,
            'UF_MESSAGE' => $message,
            'UF_IS_READ' => false,
            'UF_PARTNER_ID' => 0,
            'UF_USER_ID' => $userId,
            'UF_CREATED_AT' => new DateTime(),
            'UF_LINK' => $link,
        ]);
        if ($mail) {
            Mailer::notification('buyer', $type, $userId, $title, $message, $link);
        }
    }

    public function countUnread(int $ownerId): int
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);

        return (int)$dataClass::getCount(['=' . $this->ownerField => $ownerId, '=UF_IS_READ' => false]);
    }

    public function listOwn(int $partnerId): array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $rows = $dataClass::getList([
            'filter' => ['=' . $this->ownerField => $partnerId],
            'order' => ['UF_CREATED_AT' => 'DESC', 'ID' => 'DESC'],
        ])->fetchAll();

        return array_map([$this, 'toArray'], $rows);
    }

    /** @throws \Exception если уведомление чужое/не найдено */
    public function markRead(int $partnerId, int $id): array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK_NAME);
        $row = $dataClass::getById($id)->fetch();
        if (!$row || (int)$row[$this->ownerField] !== $partnerId) {
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
            'filter' => ['=' . $this->ownerField => $partnerId, '=UF_IS_READ' => false],
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
            'filter' => array_merge(['=' . $this->ownerField => $partnerId, '@ID' => $ids], $filter),
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
            'link' => (string)($row['UF_LINK'] ?? ''),
            'created_at' => $row['UF_CREATED_AT'] instanceof DateTime
                ? $row['UF_CREATED_AT']->format('c')
                : ($row['UF_CREATED_AT'] ? (string)$row['UF_CREATED_AT'] : null),
        ];
    }
}
