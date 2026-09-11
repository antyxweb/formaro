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
 * В прототипе новые диалоги не создаются партнёром — они появляются от
 * действий клиента на витрине маркетплейса (её ещё нет в этом проекте), тут
 * репозиторий тоже не даёт создавать диалоги, только отвечать в
 * существующих/помечать прочитанными/удалять свои сообщения — 1:1 с
 * cabinet-html/assets/js/chat-clients.js, где кнопки "новый диалог" тоже нет.
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

        $messagesClass = HlblockEntityFactory::getDataClass(self::MESSAGES_HLBLOCK);
        $result = $messagesClass::add([
            'UF_THREAD_ID' => $threadId,
            'UF_SENDER' => 'partner',
            'UF_TEXT' => $text,
            'UF_DATE' => new DateTime(),
            'UF_IS_READ' => true,
            'UF_ATTACHMENTS' => $this->saveAttachments($attachments),
        ]);
        if (!$result->isSuccess()) {
            throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
        }

        return $this->get($threadId);
    }

    /** Помечает все непрочитанные сообщения клиента в диалоге прочитанными (открытие диалога партнёром) */
    public function markRead(int $partnerId, int $threadId): array
    {
        $thread = $this->get($threadId);
        if (!$thread || !$this->canEdit($partnerId, $thread)) {
            throw new \RuntimeException('Диалог не найден или недоступен');
        }

        $messagesClass = HlblockEntityFactory::getDataClass(self::MESSAGES_HLBLOCK);
        $unread = $messagesClass::getList([
            'select' => ['ID'],
            'filter' => ['=UF_THREAD_ID' => $threadId, '=UF_SENDER' => 'client', '=UF_IS_READ' => false],
        ])->fetchAll();
        foreach ($unread as $row) {
            $messagesClass::update((int)$row['ID'], ['UF_IS_READ' => true]);
        }

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

    /** @return int[] */
    private function saveAttachments(array $attachments): array
    {
        $fileIds = [];
        foreach ($attachments as $att) {
            $dataUrl = (string)($att['data'] ?? '');
            if ($dataUrl === '') {
                continue;
            }
            $fileId = FileUploader::saveFromDataUrl($dataUrl, self::UPLOAD_SUBDIR, (string)($att['name'] ?? ''));
            if ($fileId) {
                $fileIds[] = $fileId;
            }
        }

        return $fileIds;
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
            'partner_id' => (int)$thread['UF_PARTNER_ID'],
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
