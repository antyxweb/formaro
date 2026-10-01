<?php

namespace Formaro\Cabinet\Service;

use CUser;
use Formaro\Cabinet\Repository\ChatRepository;
use Formaro\Cabinet\Repository\NotificationRepository;
use Formaro\Cabinet\Repository\ProductRepository;
use Formaro\Cabinet\Repository\OrderRepository;

/**
 * Чат покупателя с продавцами — «Чаты и сообщения» (/personal/messages/,
 * formaro:personal.chat, /local/ajax/chat.php). Хранение — те же HL-блоки,
 * что у «Чата с клиентами» кабинета партнёра (ChatRepository): диалог —
 * покупатель × продавец, один на пару; создаётся первым сообщением
 * покупателя. Заказ/товар, из которого покупатель пришёл
 * (?order=F-…/?product=ID), запоминается в диалоге — продавец видит,
 * о чём речь.
 *
 * Уведомления: продавцу — о новом сообщении покупателя (в кабинет, ссылка
 * chat/?thread=ID), покупателю — об ответе продавца (/personal/notify/,
 * notifyBuyerOfReply() — из кабинета). Только на первое непрочитанное
 * сообщение: пока получатель не открыл диалог, новые уведомления не копятся.
 */
class BuyerChatService
{
    public const MAX_TEXT = 4000;
    public const MAX_FILES = 5;
    public const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /**
     * Диалоги покупателя для страницы: продавец (имя, ссылка, логотип),
     * сообщения, число непрочитанных; свежие (по последнему сообщению) сверху.
     */
    public static function threads(int $userId): array
    {
        $threads = (new ChatRepository())->listByUser($userId);
        $partners = CartCheckoutService::partners(array_column($threads, 'partner_id'));

        $result = array_map(static fn(array $t) => self::present($t, $partners), $threads);
        usort($result, static fn(array $a, array $b) => strcmp($b['last_date'], $a['last_date']));

        return $result;
    }

    public static function countUnread(int $userId): int
    {
        $count = 0;
        foreach ((new ChatRepository())->listByUser($userId) as $thread) {
            foreach ($thread['messages'] as $m) {
                $count += $m['sender'] === 'partner' && !$m['is_read'] ? 1 : 0;
            }
        }

        return $count;
    }

    /**
     * Продавец для нового диалога (?partner=ID): {id, name, url, logo} или
     * null, если такого нет или он не активен. Партнёр-покупатель может
     * написать и своему магазину — запрета нет (переписка сама с собой
     * ничего не ломает, а заказы у своего магазина бывают).
     */
    public static function partnerForNewThread(int $userId, int $partnerId): ?array
    {
        $partner = CartCheckoutService::partners([$partnerId])[$partnerId] ?? null;
        if (!$partner || !$partner['active']) {
            return null;
        }

        return $partner;
    }

    /**
     * Сообщение покупателя продавцу; диалога нет — создаётся.
     *
     * @param array $context {order: 'F-…', product: id} — откуда пришёл покупатель
     * @param array $attachments [{name, type, data: data-URL}]
     * @return array диалог (как в threads())
     * @throws \RuntimeException понятная покупателю ошибка
     */
    public static function send(int $userId, int $partnerId, string $text, array $attachments = [], array $context = []): array
    {
        $text = trim($text);
        if ($text === '' && !$attachments) {
            throw new \RuntimeException('Напишите сообщение');
        }
        if (mb_strlen($text) > self::MAX_TEXT) {
            throw new \RuntimeException('Сообщение длиннее ' . self::MAX_TEXT . ' символов');
        }
        self::checkAttachments($attachments);
        $partner = self::partnerForNewThread($userId, $partnerId);
        if (!$partner) {
            throw new \RuntimeException('Продавец не найден');
        }

        [$orderNumber, $productName] = self::context($userId, $partnerId, $context);
        $repo = new ChatRepository();
        $thread = $repo->findByUserAndPartner($userId, $partnerId);
        if ($thread) {
            $threadId = $thread['thread_id'];
            $repo->updateContext($threadId, $orderNumber, $productName);
        } else {
            $threadId = $repo->createThread($userId, $partnerId, self::clientName($userId), $orderNumber, $productName);
        }

        $notify = !$repo->hasUnreadFrom($threadId, 'client');
        $repo->addMessage($threadId, 'client', $text, $attachments);
        if ($notify) {
            (new NotificationRepository())->add(
                $partnerId,
                'chat',
                'Сообщение от покупателя ' . self::clientName($userId),
                self::preview($text, $attachments),
                'chat/?thread=' . $threadId
            );
        }

        return self::present($repo->get($threadId), [$partnerId => $partner]);
    }

    /** Покупатель открыл диалог — ответы продавца прочитаны. */
    public static function markRead(int $userId, int $threadId): void
    {
        $repo = new ChatRepository();
        $thread = $repo->get($threadId);
        if (!$thread || $thread['user_id'] !== $userId) {
            throw new \RuntimeException('Диалог не найден');
        }
        $repo->markReadFrom($threadId, 'partner');
    }

    /**
     * Продавец ответил в кабинете — уведомление покупателю, если это первое
     * непрочитанное сообщение продавца в диалоге. $thread — после отправки.
     */
    public static function notifyBuyerOfReply(array $thread): void
    {
        if (empty($thread['user_id'])) {
            return;
        }
        $unread = array_values(array_filter($thread['messages'], static fn($m) => $m['sender'] === 'partner' && !$m['is_read']));
        if (count($unread) !== 1) {
            return;
        }
        $partner = CartCheckoutService::partners([$thread['partner_id']])[$thread['partner_id']] ?? ['name' => 'Продавец'];
        $last = end($unread);
        NotificationRepository::forBuyer()->addForBuyer(
            $thread['user_id'],
            'chat',
            'Новое сообщение от продавца ' . $partner['name'],
            self::preview((string)$last['text'], $last['attachments']),
            '/personal/messages/?thread=' . $thread['thread_id']
        );
    }

    private static function present(array $thread, array $partners): array
    {
        $partner = $partners[$thread['partner_id']] ?? ['id' => $thread['partner_id'], 'name' => 'Продавец', 'url' => '', 'logo' => ''];
        $last = end($thread['messages']) ?: null;

        return [
            'id' => $thread['thread_id'],
            'partner' => ['id' => $thread['partner_id'], 'name' => $partner['name'], 'url' => $partner['url'], 'logo' => $partner['logo'] ?? ''],
            'order_number' => (string)$thread['order_id'],
            'product_name' => (string)$thread['product_name'],
            'unread' => count(array_filter($thread['messages'], static fn($m) => $m['sender'] === 'partner' && !$m['is_read'])),
            'last_date' => (string)($last['date'] ?? $thread['created_at']),
            'messages' => array_map(static fn($m) => [
                'id' => $m['id'],
                'mine' => $m['sender'] === 'client',
                'text' => (string)$m['text'],
                'date' => (string)$m['date'],
                'is_read' => $m['is_read'],
                'attachments' => $m['attachments'],
            ], $thread['messages']),
        ];
    }

    /** Заказ — только свой и этого продавца; товар — только этого продавца. */
    private static function context(int $userId, int $partnerId, array $context): array
    {
        $orderNumber = '';
        $order = trim((string)($context['order'] ?? ''));
        if ($order !== '') {
            foreach ((new OrderRepository())->listRecentByUser($userId, 200) as $row) {
                if ($row['order_number'] === $order && $row['partner_id'] === $partnerId) {
                    $orderNumber = $order;
                    break;
                }
            }
        }

        $productName = '';
        $productId = (int)($context['product'] ?? 0);
        if ($productId) {
            $product = (new ProductRepository())->findPublic(['ID' => $productId], ['ID' => 'ASC'], 1)[0] ?? null;
            if ($product && (int)($product['partner_id'] ?? 0) === $partnerId) {
                $productName = (string)$product['name'];
            }
        }

        return [$orderNumber, $productName];
    }

    private static function checkAttachments(array $attachments): void
    {
        if (count($attachments) > self::MAX_FILES) {
            throw new \RuntimeException('Не больше ' . self::MAX_FILES . ' файлов в сообщении');
        }
        foreach ($attachments as $att) {
            $data = (string)($att['data'] ?? '');
            if (!preg_match('#^data:[a-z0-9/+.\-]+;base64,#i', $data)) {
                throw new \RuntimeException('Не удалось прочитать файл ' . ($att['name'] ?? ''));
            }
            if (strlen($data) * 3 / 4 > self::MAX_FILE_SIZE) {
                throw new \RuntimeException('Файл ' . ($att['name'] ?? '') . ' больше 10 МБ');
            }
        }
    }

    private static function clientName(int $userId): string
    {
        $user = CUser::GetByID($userId)->Fetch() ?: [];
        $name = trim(($user['NAME'] ?? '') . ' ' . ($user['LAST_NAME'] ?? ''));

        return $name !== '' ? $name : (string)($user['EMAIL'] ?? 'Покупатель');
    }

    private static function preview(string $text, array $attachments): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        if ($text === '') {
            return $attachments ? 'Файл: ' . ($attachments[0]['name'] ?? '') : '';
        }

        return mb_strlen($text) > 120 ? mb_substr($text, 0, 117) . '…' : $text;
    }
}
