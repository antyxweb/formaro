<?php

namespace Formaro\Cabinet\Service;

/**
 * Проверка сообщений чата покупателя с партнёром — чтобы стороны не
 * договаривались о сделке в обход площадки. Ищет в тексте:
 *   - контакты: телефон, e-mail, мессенджеры и соцсети, ссылки;
 *   - оплату и деньги: «оплата», «перевод», «на карту», «наличные», банки, СБП…;
 *   - цену и торг: «сколько стоит», «цена», «дешевле», «скидку»…;
 *   - сделку напрямую: «напрямую», «без сайта», «в обход», «мимо площадки»…
 * check() возвращает найденные группы с фрагментами — ChatRepository
 * помечает ими сообщение (UF_ALERT) и шлёт уведомление администраторам.
 *
 * Текст сначала нормализуется (регистр, ё→е), для телефонов цифры
 * собираются вместе с пробелами, точками, скобками и дефисами между ними —
 * «8 (900) 123-45-67», «+7.900.123.45.67».
 */
final class ChatModerationService
{
    /** Группа => регулярные выражения (по нормализованному тексту). */
    private const PATTERNS = [
        'Мессенджеры и соцсети' => [
            '/\b(telegram|телеграм\w*|телеге|тг\b|tg\b|t\.me|whats ?app|ватсап\w*|вотсап\w*|вацап\w*|viber|вайбер\w*|вибер\w*|wa\.me|vk\.com|вконтакт\w*|\bвк\b|instagram|инстаграм\w*|инст[ауе]\b|max\.ru|мессенджер\w*|личк\w*|в лс\b)/u',
            '/(^|\s)@[a-z0-9_]{4,}/u',
        ],
        'Ссылка' => [
            '/\b(https?:\/\/|www\.)\S+/u',
            '/\b[a-z0-9-]{2,}\.(ru|com|рф|net|org|info|su|me|io)\b(?!@)/u',
        ],
        'Оплата и деньги' => [
            '/\b(пред)?опла(т|ч)\w*/u',
            '/\b(перевед\w*|перевод\w*|перевести|переведу|скин(ь|у|ешь|ете)\w*|кин(ь|у|ешь|ете) на|закин\w*)/u',
            '/\b(деньг\w*|денежк\w*|наличк\w*|наличн\w*|нал\b|кэш\w*|cash)/u',
            '/\bна (карт\w*|счет|номер|сбер\w*|тинькофф?|т-банк)/u',
            '/\b(сбер\w*|тинькофф?\w*|т-банк\w*|альфа-?банк\w*|втб|сбп|qiwi|киви|юмани|yoomoney|криптовалют\w*|usdt|биткоин\w*|реквизит\w*|номер карты)/u',
            '/\b(платеж\w*|заплач\w*|заплат\w*|расплат\w*|рассчита\w*|расчет\w*)/u',
        ],
        'Цена и торг' => [
            '/\b(сколько (стоит|стоят|будет|выйдет|возьмете|отдадите)|за сколько|какая цена|по чем|почем)/u',
            '/\b(цен[аеуы]\w*|стоимост\w*|дешевле|подешевле|скидк\w*|уступ\w*|торг\w*|прайс\w*)/u',
        ],
        'Сделка напрямую' => [
            '/\b(напрямую|на прямую|в обход|мимо (сайта|площадки|маркетплейса)|без (сайта|площадки|маркетплейса|комиссии|посредник\w*)|встрет\w*|встреч\w*|самовывоз\w*|созвон\w*|позвон\w*|звонит\w*|наберит\w*|наберу|перезвон\w*)/u',
        ],
    ];

    /**
     * @return array<string, string[]> группа => найденные фрагменты; пусто — чисто
     */
    public static function check(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }
        $norm = str_replace('ё', 'е', mb_strtolower($text));
        $found = [];

        if (preg_match_all('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/u', $norm, $m)) {
            $found['E-mail'] = array_values(array_unique($m[0]));
        }
        $phones = self::phones($norm);
        if ($phones) {
            $found['Телефон'] = $phones;
        }
        foreach (self::PATTERNS as $group => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $norm, $m)) {
                    foreach ($m[0] as $hit) {
                        $hit = trim($hit);
                        if ($hit !== '' && !self::isEmailPart($hit, $found)) {
                            $found[$group][] = $hit;
                        }
                    }
                }
            }
            if (isset($found[$group])) {
                $found[$group] = array_values(array_unique($found[$group]));
            }
        }

        return $found;
    }

    /** Кратко для пометки сообщения: «Телефон; Оплата и деньги». */
    public static function summary(array $found): string
    {
        return implode('; ', array_keys($found));
    }

    /** Подробно для уведомления: «Телефон: 89001234567; Оплата и деньги: перевод, на карту». */
    public static function details(array $found): string
    {
        $parts = [];
        foreach ($found as $group => $hits) {
            $parts[] = $group . ': ' . implode(', ', array_slice($hits, 0, 5));
        }

        return implode('; ', $parts);
    }

    /**
     * Телефоны: от 10 до 12 цифр подряд, между цифрами допустимы пробелы,
     * точки, дефисы, скобки, «+» в начале. Номера заказов (F-100219) и
     * артикулы короче — не попадают.
     */
    private static function phones(string $norm): array
    {
        $phones = [];
        if (preg_match_all('/\+?\d[\d\s().-]{8,20}\d/u', $norm, $m)) {
            foreach ($m[0] as $raw) {
                $digits = preg_replace('/\D/', '', $raw);
                if (strlen($digits) >= 10 && strlen($digits) <= 12) {
                    $phones[] = trim($raw);
                }
            }
        }

        return array_values(array_unique($phones));
    }

    /** Домен из e-mail — не отдельная «ссылка». */
    private static function isEmailPart(string $hit, array $found): bool
    {
        foreach ($found['E-mail'] ?? [] as $email) {
            if (str_contains($email, $hit)) {
                return true;
            }
        }

        return false;
    }
}
