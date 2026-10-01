<?php

namespace Formaro\Cabinet\Service;

use CUser;
use Formaro\Cabinet\Repository\ChatRepository;
use Formaro\Cabinet\Repository\NotificationRepository;
use Formaro\Cabinet\Repository\OrderRepository;
use Formaro\Cabinet\Repository\ProductRepository;

/**
 * Чат покупателя с продавцами — «Чаты и сообщения» (/personal/messages/,
 * formaro:personal.chat, /local/ajax/chat.php). Хранение — те же HL-блоки,
 * что у «Чата с клиентами» кабинета партнёра (ChatRepository).
 *
 * Диалог — покупатель × продавец × тема; на каждую тему отдельный диалог:
 *   - заказ   (?partner=ID&order=F-…   — «Чат с продавцом» в «Ваших заказах»);
 *   - товар   (?partner=ID&product=ID  — в карточке товара);
 *   - общий вопрос (?partner=ID        — на странице/в карточке продавца).
 * Тема проверяется: заказ — свой и этого продавца, товар — этого продавца;
 * не прошла проверку — общий вопрос. Диалог создаётся первым сообщением.
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
     * Диалоги покупателя для страницы: продавец (имя, ссылка, логотип), тема
     * (subject: type order|product|general, label, url), сообщения, число
     * непрочитанных; свежие (по последнему сообщению) сверху.
     */
    public static function threads(int $userId): array
    {
        $threads = (new ChatRepository())->listByUser($userId);
        $partners = CartCheckoutService::partners(array_column($threads, 'partner_id'));
        $links = self::subjectLinks($userId, $threads);

        $result = array_map(static fn(array $t) => self::present($t, $partners, $links), $threads);
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
     * Продавец для нового диалога (?partner=ID): {id, name, url, logo, active}
     * или null, если такого нет или он не активен. Партнёр-покупатель может
     * написать и своему магазину — запрета нет (переписка сама с собой
     * ничего не ломает, а заказы у своего магазина бывают).
     */
    public static function partner(int $partnerId): ?array
    {
        $partner = CartCheckoutService::partners([$partnerId])[$partnerId] ?? null;

        return $partner && $partner['active'] ? $partner : null;
    }

    /**
     * Тема нового диалога из адреса/формы — проверенная.
     *
     * @param array $context {order: 'F-…', product: id}
     * @return array {order, product_id, product_name, subject: {type, label, url}}
     */
    public static function subject(int $userId, int $partnerId, array $context): array
    {
        $order = trim((string)($context['order'] ?? ''));
        if ($order !== '') {
            foreach ((new OrderRepository())->listRecentByUser($userId, 500) as $row) {
                if ($row['order_number'] === $order && $row['partner_id'] === $partnerId) {
                    return [
                        'order' => $order, 'product_id' => 0, 'product_name' => '',
                        'subject' => self::subjectOf($order, 0, '', ['orders' => [$order => $row['id']]]),
                    ];
                }
            }
        }

        $productId = (int)($context['product'] ?? 0);
        if ($productId) {
            $product = (new ProductRepository())->findPublic(['ID' => $productId], ['ID' => 'ASC'], 1)[0] ?? null;
            if ($product && (int)$product['partner_id'] === $partnerId) {
                return [
                    'order' => '', 'product_id' => $productId, 'product_name' => (string)$product['name'],
                    'subject' => self::subjectOf('', $productId, (string)$product['name'], ['products' => [$productId => (string)$product['public_url']]]),
                ];
            }
        }

        return ['order' => '', 'product_id' => 0, 'product_name' => '', 'subject' => self::subjectOf('', 0, '', [])];
    }

    /** Есть ли уже диалог с продавцом по этой теме. @return int id или 0 */
    public static function findThread(int $userId, int $partnerId, array $subject): int
    {
        $thread = (new ChatRepository())->findBuyerThread($userId, $partnerId, $subject['order'], $subject['product_id']);

        return $thread ? $thread['thread_id'] : 0;
    }

    /**
     * Сообщение покупателя продавцу: в существующий диалог ($threadId) или
     * в диалог по теме ($partnerId + $context) — нет такого, создаётся.
     *
     * @param array $attachments [{name, type, data: data-URL}]
     * @return array диалог (как в threads())
     * @throws \RuntimeException понятная покупателю ошибка
     */
    public static function send(int $userId, int $threadId, int $partnerId, string $text, array $attachments = [], array $context = []): array
    {
        $text = trim($text);
        if ($text === '' && !$attachments) {
            throw new \RuntimeException('Напишите сообщение');
        }
        if (mb_strlen($text) > self::MAX_TEXT) {
            throw new \RuntimeException('Сообщение длиннее ' . self::MAX_TEXT . ' символов');
        }
        self::checkAttachments($attachments);

        $repo = new ChatRepository();
        if ($threadId) {
            $thread = $repo->get($threadId);
            if (!$thread || $thread['user_id'] !== $userId) {
                throw new \RuntimeException('Диалог не найден');
            }
            $partnerId = $thread['partner_id'];
        }
        $partner = self::partner($partnerId);
        if (!$partner) {
            throw new \RuntimeException('Продавец не найден');
        }

        if (!$threadId) {
            $subject = self::subject($userId, $partnerId, $context);
            $threadId = self::findThread($userId, $partnerId, $subject)
                ?: $repo->createThread($userId, $partnerId, self::clientName($userId), $subject['order'], $subject['product_id'], $subject['product_name']);
        }

        $notify = !$repo->hasUnreadFrom($threadId, 'client');
        $repo->addMessage($threadId, 'client', $text, $attachments);
        $thread = $repo->get($threadId);
        if ($notify) {
            $about = $thread['order_id'] !== '' ? ' по заказу ' . $thread['order_id']
                : ($thread['product_name'] !== '' ? ' по товару «' . $thread['product_name'] . '»' : '');
            (new NotificationRepository())->add(
                $partnerId,
                'chat',
                'Сообщение от покупателя ' . self::clientName($userId) . $about,
                self::preview($text, $attachments),
                'chat/?thread=' . $threadId
            );
        }

        return self::present($thread, [$partnerId => $partner], self::subjectLinks($userId, [$thread]));
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
        $about = $thread['order_id'] !== '' ? ' по заказу ' . $thread['order_id']
            : ($thread['product_name'] !== '' ? ' по товару «' . $thread['product_name'] . '»' : '');
        NotificationRepository::forBuyer()->addForBuyer(
            $thread['user_id'],
            'chat',
            'Новое сообщение от продавца ' . $partner['name'] . $about,
            self::preview((string)$unread[0]['text'], $unread[0]['attachments']),
            '/personal/messages/?thread=' . $thread['thread_id']
        );
    }

    private static function present(array $thread, array $partners, array $links): array
    {
        $partner = $partners[$thread['partner_id']] ?? ['id' => $thread['partner_id'], 'name' => 'Продавец', 'url' => '', 'logo' => ''];
        $last = end($thread['messages']) ?: null;

        return [
            'id' => $thread['thread_id'],
            'partner' => ['id' => $thread['partner_id'], 'name' => $partner['name'], 'url' => $partner['url'], 'logo' => $partner['logo'] ?? ''],
            'subject' => self::subjectOf((string)$thread['order_id'], $thread['product_id'], (string)$thread['product_name'], $links),
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

    /** Тема диалога для покупателя: {type, label, url}. */
    private static function subjectOf(string $orderNumber, int $productId, string $productName, array $links): array
    {
        if ($orderNumber !== '') {
            $orderId = $links['orders'][$orderNumber] ?? 0;

            return ['type' => 'order', 'label' => 'Заказ ' . $orderNumber, 'url' => $orderId ? '/personal/orders/#order-' . $orderId : ''];
        }
        if ($productId || $productName !== '') {
            return ['type' => 'product', 'label' => $productName !== '' ? $productName : 'Товар', 'url' => (string)($links['products'][$productId] ?? '')];
        }

        return ['type' => 'general', 'label' => 'Общий вопрос', 'url' => ''];
    }

    /** Ссылки тем: заказ — в «Ваших заказах», товар — карточка (если ещё на сайте). */
    private static function subjectLinks(int $userId, array $threads): array
    {
        $links = ['orders' => [], 'products' => []];
        if (array_filter(array_column($threads, 'order_id'))) {
            foreach ((new OrderRepository())->listRecentByUser($userId, 500) as $order) {
                $links['orders'][$order['order_number']] = $order['id'];
            }
        }
        $productIds = array_values(array_unique(array_filter(array_column($threads, 'product_id'))));
        if ($productIds) {
            foreach ((new ProductRepository())->findPublic(['ID' => $productIds], ['ID' => 'ASC'], count($productIds)) as $p) {
                $links['products'][$p['id']] = (string)$p['public_url'];
            }
        }

        return $links;
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
