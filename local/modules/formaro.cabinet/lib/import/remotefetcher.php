<?php

namespace Formaro\Cabinet\Import;

/**
 * Скачивание по ссылке из файла партнёра (фид по URL, картинки товаров).
 * Адреса приходят от пользователя, поэтому защита от SSRF: только http/https,
 * хост должен резолвиться в публичный IP (не localhost/внутренняя сеть/
 * метаданные облака), соединение идёт ровно на проверенный IP (CURLOPT_RESOLVE,
 * чтобы DNS не подменили между проверкой и запросом), редиректы проходим
 * вручную с той же проверкой, размер ответа ограничен.
 */
class RemoteFetcher
{
    private const MAX_REDIRECTS = 3;

    /**
     * @return array{body: string, type: string}
     * @throws \RuntimeException
     */
    public static function fetch(string $url, int $maxBytes, int $timeout = 20): array
    {
        for ($i = 0; $i <= self::MAX_REDIRECTS; $i++) {
            [$host, $port, $ip] = self::resolve($url);

            $body = '';
            $tooBig = false;
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_RESOLVE => [$host . ':' . $port . ':' . $ip],
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_USERAGENT => 'ReppaImport/1.0',
                CURLOPT_ENCODING => '',
                CURLOPT_HEADER => false,
                CURLOPT_WRITEFUNCTION => static function ($ch, string $chunk) use (&$body, &$tooBig, $maxBytes) {
                    if (strlen($body) + strlen($chunk) > $maxBytes) {
                        $tooBig = true;

                        return 0;
                    }
                    $body .= $chunk;

                    return strlen($chunk);
                },
            ]);
            curl_exec($ch);
            $error = curl_error($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $type = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $location = (string)curl_getinfo($ch, CURLINFO_REDIRECT_URL);
            curl_close($ch);

            if ($tooBig) {
                throw new \RuntimeException('Файл по ссылке больше ' . round($maxBytes / 1048576) . ' МБ');
            }
            if ($error !== '') {
                throw new \RuntimeException('Не удалось скачать: ' . $error);
            }
            if ($code >= 300 && $code < 400 && $location !== '') {
                $url = $location;
                continue;
            }
            if ($code !== 200) {
                throw new \RuntimeException('Сервер ответил кодом ' . $code);
            }

            return ['body' => $body, 'type' => strtolower(trim(explode(';', $type)[0]))];
        }

        throw new \RuntimeException('Слишком много перенаправлений');
    }

    /** @return array{0: string, 1: int, 2: string} хост, порт, проверенный IP */
    private static function resolve(string $url): array
    {
        $parts = parse_url($url);
        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        $host = (string)($parts['host'] ?? '');
        if (!in_array($scheme, ['http', 'https'], true) || $host === '' || isset($parts['user'])) {
            throw new \RuntimeException('Нужна ссылка вида https://…');
        }
        $port = (int)($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
        $host = trim($host, '[]');

        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw new \RuntimeException('Адрес недоступен: ' . $host);
        }

        return [$host, $port, $ip];
    }
}
