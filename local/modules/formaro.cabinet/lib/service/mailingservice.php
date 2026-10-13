<?php

namespace Formaro\Cabinet\Service;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use Formaro\Cabinet\Mail\Mailer;
use Formaro\Cabinet\Repository\HlblockEntityFactory;

/**
 * Управление рассылками по e-mail.
 *
 * Категории писем — типы уведомлений кабинетов (Mailer::NOTIFICATIONS) для
 * покупателя и поставщика: «buyer.order», «partner.chat» и т.д. По умолчанию
 * все включены. Отказ от категории — запись в HL-блоке FormaroMailOptOut
 * (миграция Version20261013100001). Mailer::notification() такие письма не
 * шлёт. Служебные письма (регистрация, пароль, заказ, счёт) отключить
 * нельзя. Уведомления администраторам площадки сюда не входят.
 *
 * Подписки на новости из форм сайта — SubscriptionService. Здесь они
 * только собираются в общий список.
 *
 * Ссылка «Настроить рассылки» в письмах ведёт на /subscriptions/ с e-mail
 * и подписью (manageUrl()), поэтому управлять можно без входа на сайт.
 */
final class MailingService
{
    public const AUDIENCES = ['buyer', 'partner'];
    public const PAGE = '/subscriptions/';

    private const HLBLOCK = 'FormaroMailOptOut';
    private const MODULE_ID = 'formaro.cabinet';

    public const GROUP_TITLES = [
        'buyer' => 'Уведомления покупателя',
        'partner' => 'Уведомления кабинета партнёра',
    ];

    /** Пояснения к категориям — под названием в списке. */
    private const HINTS = [
        'buyer' => [
            'order' => 'Оформление, счета, оплата и статусы ваших заказов',
            'chat' => 'Новые сообщения от поставщиков в чате',
            'system' => 'Изменения профиля и другие сообщения площадки',
        ],
        'partner' => [
            'order' => 'Новые заказы, счета и изменения статусов',
            'chat' => 'Новые сообщения от покупателей в чате',
            'support' => 'Ответы техподдержки на ваши обращения',
            'finance' => 'Выплаты по заказам',
            'product' => 'Проверка ваших категорий площадкой',
            'system' => 'Проверка данных компании и другие сообщения площадки',
        ],
    ];

    /** Канал: «buyer.order». */
    public static function channel(string $audience, string $type): string
    {
        return $audience . '.' . $type;
    }

    /** @return array<string, string> канал => название, для аудитории */
    public static function channels(string $audience): array
    {
        $channels = [];
        foreach (Mailer::NOTIFICATIONS[$audience] ?? [] as $type => $label) {
            $channels[self::channel($audience, $type)] = $label;
        }

        return $channels;
    }

    /**
     * Отключённые каналы по адресам — для отбора получателей письма.
     *
     * @param string[] $emails
     * @return array<string, string[]> e-mail (в нижнем регистре) => каналы
     */
    public static function disabledFor(array $emails): array
    {
        $emails = array_values(array_unique(array_filter(array_map([self::class, 'normalize'], $emails))));
        if (!$emails) {
            return [];
        }
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK);
        $rows = $dataClass::getList(['select' => ['UF_EMAIL', 'UF_CHANNEL'], 'filter' => ['=UF_EMAIL' => $emails]]);
        $disabled = [];
        while ($row = $rows->fetch()) {
            $disabled[(string)$row['UF_EMAIL']][] = (string)$row['UF_CHANNEL'];
        }

        return $disabled;
    }

    public static function isEnabled(string $email, string $audience, string $type): bool
    {
        $email = self::normalize($email);

        return !in_array(self::channel($audience, $type), self::disabledFor([$email])[$email] ?? [], true);
    }

    /**
     * Включённые каналы аудитории = $enabled, остальные её каналы отключить.
     *
     * @param string[] $enabled
     */
    public static function setChannels(string $email, string $audience, array $enabled): void
    {
        $email = self::normalize($email);
        if ($email === '' || !in_array($audience, self::AUDIENCES, true)) {
            throw new \RuntimeException('Не удалось сохранить настройки рассылок');
        }
        $all = array_keys(self::channels($audience));
        $enabled = array_intersect($all, array_map('strval', $enabled));
        $disabled = self::disabledFor([$email])[$email] ?? [];
        $dataClass = HlblockEntityFactory::getDataClass(self::HLBLOCK);

        foreach ($all as $channel) {
            $isOff = in_array($channel, $disabled, true);
            if (in_array($channel, $enabled, true) && $isOff) {
                $rows = $dataClass::getList(['select' => ['ID'], 'filter' => ['=UF_EMAIL' => $email, '=UF_CHANNEL' => $channel]]);
                while ($row = $rows->fetch()) {
                    $dataClass::delete($row['ID']);
                }
            } elseif (!in_array($channel, $enabled, true) && !$isOff) {
                $dataClass::add(['UF_EMAIL' => $email, 'UF_CHANNEL' => $channel, 'UF_CREATED_AT' => new DateTime()]);
            }
        }
    }

    /** Отписка от всего: все категории уведомлений и все подписки на новости. */
    public static function unsubscribeAll(string $email): void
    {
        foreach (self::audiencesFor($email) as $audience) {
            self::setChannels($email, $audience, []);
        }
        SubscriptionService::unsubscribeAll($email);
    }

    /**
     * Каким аудиториям уходят письма на этот адрес: покупатель — есть
     * пользователь с таким e-mail, поставщик — он привязан к партнёру.
     *
     * @return string[]
     */
    public static function audiencesFor(string $email): array
    {
        $email = self::normalize($email);
        if ($email === '') {
            return [];
        }
        $rows = \Bitrix\Main\UserTable::getList([
            'select' => ['ID', 'UF_CABINET_PARTNER_ID'],
            'filter' => ['=EMAIL' => $email, '=ACTIVE' => 'Y'],
        ]);
        $audiences = [];
        while ($row = $rows->fetch()) {
            $audiences['buyer'] = true;
            if ((int)$row['UF_CABINET_PARTNER_ID'] > 0) {
                $audiences['partner'] = true;
            }
        }

        return array_keys($audiences);
    }

    /**
     * Настройки для интерфейса: группы категорий с флагом «включено» и
     * подписки на новости.
     *
     * @param string[]|null $audiences null — все, что относятся к адресу
     */
    public static function state(string $email, ?array $audiences = null): array
    {
        $email = self::normalize($email);
        $audiences = $audiences ?? self::audiencesFor($email);
        $disabled = self::disabledFor([$email])[$email] ?? [];

        $groups = [];
        foreach (array_intersect(self::AUDIENCES, $audiences) as $audience) {
            $items = [];
            foreach (Mailer::NOTIFICATIONS[$audience] as $type => $label) {
                $channel = self::channel($audience, $type);
                $items[] = [
                    'channel' => $channel,
                    'label' => $label,
                    'hint' => self::HINTS[$audience][$type] ?? '',
                    'enabled' => !in_array($channel, $disabled, true),
                ];
            }
            $groups[] = ['audience' => $audience, 'title' => self::GROUP_TITLES[$audience], 'items' => $items];
        }

        return [
            'email' => $email,
            'groups' => $groups,
            'newsletters' => SubscriptionService::listFor($email),
        ];
    }

    /** Адрес страницы управления рассылками для письма (абсолютный). */
    public static function manageUrl(string $email, string $channel = ''): string
    {
        $email = self::normalize($email);
        $query = ['u' => self::base64url($email), 't' => self::sign($email)];
        if ($channel !== '') {
            $query['c'] = $channel;
        }

        return Mailer::url(self::PAGE . '?' . http_build_query($query));
    }

    /** E-mail из ссылки письма; null — ссылка испорчена или подделана. */
    public static function emailFromLink(string $u, string $t): ?string
    {
        $email = self::normalize((string)base64_decode(strtr($u, '-_', '+/'), true));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return hash_equals(self::sign($email), $t) ? $email : null;
    }

    public static function normalize(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private static function sign(string $email): string
    {
        return substr(hash_hmac('sha256', 'mailing|' . $email, self::secret()), 0, 32);
    }

    /** Ключ подписи ссылок — создаётся при первом обращении. */
    private static function secret(): string
    {
        $secret = (string)Option::get(self::MODULE_ID, 'mailing_secret', '');
        if ($secret === '') {
            $secret = bin2hex(random_bytes(32));
            Option::set(self::MODULE_ID, 'mailing_secret', $secret);
        }

        return $secret;
    }

    private static function base64url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
