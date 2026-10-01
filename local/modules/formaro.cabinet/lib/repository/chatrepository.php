<?php

namespace Formaro\Cabinet\Repository;

use Bitrix\Main\Type\DateTime;
use Formaro\Cabinet\Upload\FileUploader;

/**
 * Чат с клиентами — HL-блоки CabinetChatThreads (диалог) + CabinetChatMessages
 * (переписка, отдельные строки — см. Version20260912130002, тот же приём, что
 * и у тикетов техподдержки). Формат совпадает с cabinet-html/data/chat.json
 * ({thread_id, client_name, order_id, product_name, messages: [{sender,
 * text, date, is_read, attachments}]}), чтобы chat-clients.js работал почти
 * без переделки.
 *
 * Партнёр диалоги не создаёт — их начинает покупатель на витрине
 * («Чаты и сообщения», /personal/messages/, BuyerChatService): диалог —
 * покупатель (UF_USER_ID, миграция Version20261001160001) × продавец × тема:
 * заказ (UF_ORDER_NUMBER), товар (UF_PRODUCT_ID, Version20261001170001) или
 * общий вопрос — на каждую тему отдельный диалог. Партнёр отвечает в существующих, помечает прочитанными, удаляет
 * свои сообщения — 1:1 с cabinet-html/assets/js/chat-clients.js.
 *
 * UF_IS_READ сообщения — прочитано ли оно получателем: сообщения клиента —
 * партнёром, сообщения партнёра — покупателем. UF_NOTIFIED — получателю
 * отправлено уведомление (через час непрочитанности, ChatNotificationService).
 */
class ChatRepository
{
    private const THREADS_HLBLOCK = 'CabinetChatThreads';
    private const MESSAGES_HLBLOCK = 'CabinetChatMessages';
    private const UPLOAD_SUBDIR = 'cabinet/chat';

    public function listOwn(int $partnerId): array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::THREADS_HLBLOCK);
        $rows = $dataClass::getList([
            'filter' => ['=UF_PARTNER_ID' => $partnerId],
            'order' => ['ID' => 'DESC'],
        ])->fetchAll();

        return array_map(fn(array $row) => $this->toArray($row), $rows);
    }

    public function get(int $id): ?array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::THREADS_HLBLOCK);
        $row = $dataClass::getById($id)->fetch();

        return $row ? $this->toArray($row) : null;
    }

    public function canEdit(int $partnerId, array $thread): bool
    {
        return $thread['partner_id'] === $partnerId;
    }

    /** @param array $attachments [{name, type, data}] */
    public function sendMessage(int $partnerId, int $threadId, string $text, array $attachments = []): array
    {
        $thread = $this->get($threadId);
        if (!$thread || !$this->canEdit($partnerId, $thread)) {
            throw new \RuntimeException('Диалог не найден или недоступен');
        }

        $this->addMessage($threadId, 'partner', $text, $attachments);

        return $this->get($threadId);
    }

    /**
     * Сообщение в диалог; непрочитанное получателем (UF_IS_READ — см. выше).
     *
     * @param string $sender client|partner
     * @param array $attachments [{name, type, data}]
     */
    public function addMessage(int $threadId, string $sender, string $text, array $attachments = []): void
    {
        $messagesClass = HlblockEntityFactory::getDataClass(self::MESSAGES_HLBLOCK);
        $result = $messagesClass::add([
            'UF_THREAD_ID' => $threadId,
            'UF_SENDER' => $sender,
            'UF_TEXT' => $text,
            'UF_DATE' => new DateTime(),
            'UF_IS_READ' => false,
            'UF_NOTIFIED' => false,
            'UF_ATTACHMENTS' => $this->saveAttachments($attachments),
        ]);
        if (!$result->isSuccess()) {
            throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
        }
    }

    /**
     * Непрочитанные сообщения не позже $before, о которых ещё не уведомляли
     * (ChatNotificationService): [{id, thread_id, sender, text, attachments}].
     */
    public function listUnnotifiedUnread(DateTime $before): array
    {
        $messagesClass = HlblockEntityFactory::getDataClass(self::MESSAGES_HLBLOCK);
        $rows = $messagesClass::getList([
            'filter' => [
                '=UF_IS_READ' => false,
                '<=UF_DATE' => $before,
                ['LOGIC' => 'OR', ['=UF_NOTIFIED' => false], ['=UF_NOTIFIED' => null]],
            ],
            'order' => ['UF_DATE' => 'ASC', 'ID' => 'ASC'],
        ])->fetchAll();

        return array_map(static fn(array $m) => [
            'id' => (int)$m['ID'],
            'thread_id' => (int)$m['UF_THREAD_ID'],
            'sender' => (string)$m['UF_SENDER'],
            'text' => (string)$m['UF_TEXT'],
            'attachments' => array_values(array_filter(array_map(
                static fn($fid) => FileUploader::getAttachment((int)$fid),
                (array)($m['UF_ATTACHMENTS'] ?? [])
            ))),
        ], $rows);
    }

    /** Все непрочитанные сообщения $sender в диалоге — уведомление о них отправлено. */
    public function markNotified(int $threadId, string $sender): void
    {
        $messagesClass = HlblockEntityFactory::getDataClass(self::MESSAGES_HLBLOCK);
        $rows = $messagesClass::getList([
            'select' => ['ID'],
            'filter' => ['=UF_THREAD_ID' => $threadId, '=UF_SENDER' => $sender, '=UF_IS_READ' => false],
        ])->fetchAll();
        foreach ($rows as $row) {
            $messagesClass::update((int)$row['ID'], ['UF_NOTIFIED' => true]);
        }
    }

    /** Все сообщения от $sender в диалоге — прочитанными (получатель открыл диалог). */
    public function markReadFrom(int $threadId, string $sender): void
    {
        $messagesClass = HlblockEntityFactory::getDataClass(self::MESSAGES_HLBLOCK);
        $unread = $messagesClass::getList([
            'select' => ['ID'],
            'filter' => ['=UF_THREAD_ID' => $threadId, '=UF_SENDER' => $sender, '=UF_IS_READ' => false],
        ])->fetchAll();
        foreach ($unread as $row) {
            $messagesClass::update((int)$row['ID'], ['UF_IS_READ' => true]);
        }
    }

    /** Диалоги покупателя, свежие сверху (по последнему сообщению — сортирует BuyerChatService). */
    public function listByUser(int $userId): array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::THREADS_HLBLOCK);
        $rows = $dataClass::getList([
            'filter' => ['=UF_USER_ID' => $userId],
            'order' => ['ID' => 'DESC'],
        ])->fetchAll();

        return array_map(fn(array $row) => $this->toArray($row), $rows);
    }

    /**
     * Диалог покупателя с продавцом по теме: заказ, товар или общий вопрос
     * (оба пустые) — точное совпадение темы.
     */
    public function findBuyerThread(int $userId, int $partnerId, string $orderNumber, int $productId): ?array
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::THREADS_HLBLOCK);
        $rows = $dataClass::getList([
            'filter' => ['=UF_USER_ID' => $userId, '=UF_PARTNER_ID' => $partnerId],
            'order' => ['ID' => 'ASC'],
        ])->fetchAll();
        foreach ($rows as $row) {
            if ((string)$row['UF_ORDER_NUMBER'] === $orderNumber && (int)$row['UF_PRODUCT_ID'] === $productId) {
                return $this->toArray($row);
            }
        }

        return null;
    }

    /** Новый диалог покупателя с продавцом по теме. @return int id диалога */
    public function createThread(int $userId, int $partnerId, string $clientName, string $orderNumber = '', int $productId = 0, string $productName = ''): int
    {
        $dataClass = HlblockEntityFactory::getDataClass(self::THREADS_HLBLOCK);
        $result = $dataClass::add([
            'UF_USER_ID' => $userId,
            'UF_PARTNER_ID' => $partnerId,
            'UF_CLIENT_NAME' => $clientName,
            'UF_ORDER_NUMBER' => $orderNumber,
            'UF_PRODUCT_ID' => $productId,
            'UF_PRODUCT_NAME' => $productName,
            'UF_CREATED_AT' => new DateTime(),
        ]);
        if (!$result->isSuccess()) {
            throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
        }

        return (int)$result->getId();
    }

    /** Помечает все непрочитанные сообщения клиента в диалоге прочитанными (открытие диалога партнёром) */
    public function markRead(int $partnerId, int $threadId): array
    {
        $thread = $this->get($threadId);
        if (!$thread || !$this->canEdit($partnerId, $thread)) {
            throw new \RuntimeException('Диалог не найден или недоступен');
        }

        $this->markReadFrom($threadId, 'client');

        return $this->get($threadId);
    }

    /** @throws \Exception если диалог чужой/не найден, или сообщение из другого диалога */
    public function deleteMessage(int $partnerId, int $threadId, int $messageId): array
    {
        $thread = $this->get($threadId);
        if (!$thread || !$this->canEdit($partnerId, $thread)) {
            throw new \RuntimeException('Диалог не найден или недоступен');
        }

        $messagesClass = HlblockEntityFactory::getDataClass(self::MESSAGES_HLBLOCK);
        $row = $messagesClass::getList(['filter' => ['=ID' => $messageId, '=UF_THREAD_ID' => $threadId]])->fetch();
        if (!$row) {
            throw new \RuntimeException('Сообщение не найдено');
        }

        foreach ((array)($row['UF_ATTACHMENTS'] ?? []) as $fileId) {
            FileUploader::delete((int)$fileId);
        }
        $messagesClass::delete($messageId);

        return $this->get($threadId);
    }

    /** @return array[] файловые массивы для UF_ATTACHMENTS (см. FileUploader::fileArraysFromDataUrls()) */
    private function saveAttachments(array $attachments): array
    {
        return FileUploader::fileArraysFromDataUrls($attachments, self::UPLOAD_SUBDIR);
    }

    private function toArray(array $thread): array
    {
        $messagesClass = HlblockEntityFactory::getDataClass(self::MESSAGES_HLBLOCK);
        $messageRows = $messagesClass::getList([
            'filter' => ['=UF_THREAD_ID' => $thread['ID']],
            'order' => ['UF_DATE' => 'ASC', 'ID' => 'ASC'],
        ])->fetchAll();

        return [
            'thread_id' => (int)$thread['ID'],
            'client_name' => $thread['UF_CLIENT_NAME'],
            'order_id' => $thread['UF_ORDER_NUMBER'] ?? '',
            'product_name' => $thread['UF_PRODUCT_NAME'] ?? '',
            'product_id' => (int)($thread['UF_PRODUCT_ID'] ?? 0),
            'partner_id' => (int)$thread['UF_PARTNER_ID'],
            'user_id' => (int)($thread['UF_USER_ID'] ?? 0),
            'created_at' => $this->dateToString($thread['UF_CREATED_AT']),
            'messages' => array_map(function (array $m) {
                return [
                    'id' => (int)$m['ID'],
                    'sender' => $m['UF_SENDER'],
                    'text' => $m['UF_TEXT'],
                    'date' => $this->dateToString($m['UF_DATE']),
                    'is_read' => !empty($m['UF_IS_READ']),
                    'attachments' => array_values(array_filter(array_map(
                        static fn($fid) => FileUploader::getAttachment((int)$fid),
                        (array)($m['UF_ATTACHMENTS'] ?? [])
                    ))),
                ];
            }, $messageRows),
        ];
    }

    private function dateToString($value): ?string
    {
        return $value instanceof DateTime ? $value->format('c') : ($value ? (string)$value : null);
    }
}
