<?php

namespace Formaro\Cabinet\Repository;

use Bitrix\Main\Loader;
use CIBlock;
use CIBlockElement;
use Formaro\Cabinet\Upload\FileUploader;

/**
 * Профиль партнёра = элемент инфоблока cabinet_partners. Создание нового
 * партнёра (при онбординге) в фазе 1 не реализуем — это делает администратор
 * площадки вручную (заводит элемент + прописывает UF_CABINET_PARTNER_ID
 * пользователю), самостоятельная регистрация партнёра — отдельная задача.
 * Поэтому здесь только get()/save() существующего элемента.
 *
 * login/email — это поля пользователя Bitrix (b_user), не элемента; их
 * подмешивает action-шаблон профиля (см. templates/.default/actions/
 * profile.php), а не этот репозиторий.
 */
class PartnerRepository
{
    private const IBLOCK_CODE = 'cabinet_partners';
    private const IBLOCK_TYPE = 'marketplace';
    private const UPLOAD_SUBDIR = 'cabinet/partners';
    private const STATUS_PROPERTY_CODE = 'VERIFICATION_STATUS';
    private const STATUS_NOT_VERIFIED = 'not_verified';
    private const STATUS_PENDING = 'pending';
    private const STATUS_VERIFIED = 'verified';
    private const PENDING_PROPERTY_CODE = 'PENDING_CHANGES';

    /** См. ProductRepository::SELECT_FIELDS — 'PROPERTY_*' отдаёт значения
     *  по ID свойства, а не по коду, который читает toArray(). */
    private const SELECT_FIELDS = [
        '*', 'PREVIEW_TEXT', 'DETAIL_TEXT',
        'PROPERTY_NAME_SHORT', 'PROPERTY_VERIFICATION_STATUS', 'PROPERTY_PENDING_CHANGES',
        'PROPERTY_LEGAL_INN', 'PROPERTY_LEGAL_OGRN', 'PROPERTY_LEGAL_ADDRESS',
        'PROPERTY_LEGAL_BANK_NAME', 'PROPERTY_LEGAL_BIK', 'PROPERTY_LEGAL_ACCOUNT',
        'PROPERTY_LEGAL_CORR_ACCOUNT', 'PROPERTY_LEGAL_CEO_NAME',
        'PROPERTY_CONTACT_PHONE', 'PROPERTY_CONTACT_EMAIL',
        'PROPERTY_CONTACT_PERSON', 'PROPERTY_CONTACT_POSITION',
    ];

    private int $iblockId;

    public function __construct()
    {
        Loader::includeModule('iblock');
        $this->iblockId = $this->resolveIblockId();
    }

    /**
     * Любое сохранение вкладки "Основное" из кабинета партнёра (см. save())
     * всегда лежит черновиком в свойстве PENDING_CHANGES, а не в боевых
     * полях — независимо от текущего статуса верификации. Здесь, при
     * каждом чтении: если статус СЕЙЧАС "Проверен" и черновик есть —
     * значит, площадка (пере)подтвердила партнёра (статус меняют не через
     * этот репозиторий, а вручную в админке битрикса) — переносим черновик
     * в боевые поля и очищаем PENDING_CHANGES (commitPending()). Иначе
     * (статус ещё не "Проверен") — просто подмешиваем черновик поверх
     * боевых данных, чтобы партнёр видел на форме то, что сам последним
     * отправил, а не то, что видно на публичной витрине до одобрения.
     */
    public function get(int $partnerId): ?array
    {
        $el = $this->fetchElement($partnerId);
        if (!$el) {
            return null;
        }

        $status = $this->resolveStatus($el);
        $pending = $this->decodePending($el);

        if ($status === self::STATUS_VERIFIED && $pending) {
            $this->commitPending($partnerId, $pending);
            $el = $this->fetchElement($partnerId);
            $pending = [];
        }

        $data = $this->toArray($el);

        return $pending ? $this->mergePending($data, $pending) : $data;
    }

    private function fetchElement(int $partnerId): ?array
    {
        $el = CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $this->iblockId, 'ID' => $partnerId, 'CHECK_PERMISSIONS' => 'N'],
            false,
            false,
            self::SELECT_FIELDS
        )->Fetch();

        return $el ?: null;
    }

    private function resolveStatus(array $el): string
    {
        return $el['PROPERTY_VERIFICATION_STATUS_ENUM_ID']
            ? $this->enumXmlId((int)$el['PROPERTY_VERIFICATION_STATUS_ENUM_ID'])
            : self::STATUS_NOT_VERIFIED;
    }

    private function decodePending(array $el): array
    {
        $json = $el['PROPERTY_PENDING_CHANGES_VALUE'] ?? '';
        if ($json === '' || $json === null) {
            return [];
        }
        $decoded = json_decode((string)$json, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Кнопка на форме — не нейтральный "Сохранить", а "Отправить на
     * проверку": любое сохранение вкладки "Основное", независимо от
     * текущего статуса, складывается черновиком в PENDING_CHANGES, а не
     * применяется к боевым полям напрямую (saveAsPending()) — переводя
     * статус в "На проверке" (см. profile.php). В боевые поля черновик
     * попадает только когда партнёра проверят/перепроверят (статус снова
     * станет "Проверен") — см. get()/commitPending().
     */
    public function save(int $partnerId, array $payload): array
    {
        $existing = $this->get($partnerId);
        if (!$existing) {
            throw new \RuntimeException('Партнёр не найден (ID=' . $partnerId . ')');
        }

        $legal = (array)($payload['legal'] ?? []);
        $contacts = (array)($payload['contacts'] ?? []);

        $this->saveAsPending($partnerId, $payload, $existing, $legal, $contacts);

        return $this->get($partnerId);
    }

    /** Складывает вкладку "Основное" в PENDING_CHANGES вместо боевых полей
     *  (см. save()). Картинки — сразу реальные файлы (CFile), а не base64
     *  в JSON: храним только ID, применяем при commitPending(). */
    private function saveAsPending(int $partnerId, array $payload, array $existing, array $legal, array $contacts): void
    {
        $pending = [
            'name_full' => (string)($payload['name_full'] ?? $existing['name_full']),
            'name_short' => (string)($payload['name_short'] ?? ''),
            'short_desc' => (string)($payload['short_desc'] ?? ''),
            'full_desc' => (string)($payload['full_desc'] ?? ''),
            'legal' => [
                'inn' => (string)($legal['inn'] ?? ''),
                'ogrn' => (string)($legal['ogrn'] ?? ''),
                'legal_address' => (string)($legal['legal_address'] ?? ''),
                'bank_name' => (string)($legal['bank_name'] ?? ''),
                'bik' => (string)($legal['bik'] ?? ''),
                'account' => (string)($legal['account'] ?? ''),
                'corr_account' => (string)($legal['corr_account'] ?? ''),
                'ceo_name' => (string)($legal['ceo_name'] ?? ''),
            ],
            'contacts' => [
                'phone' => (string)($contacts['phone'] ?? ''),
                'email' => (string)($contacts['email'] ?? ''),
                'contact_person' => (string)($contacts['contact_person'] ?? ''),
                'contact_position' => (string)($contacts['contact_position'] ?? ''),
            ],
        ];

        $this->applyPendingImage($pending, 'logo_file_id', $payload['logo'] ?? null, $existing['logo'] ?? null);
        $this->applyPendingImage($pending, 'image_file_id', $payload['image'] ?? null, $existing['image'] ?? null);

        CIBlockElement::SetPropertyValuesEx($partnerId, $this->iblockId, [
            self::STATUS_PROPERTY_CODE => $this->resolveEnumIdByXmlId(self::STATUS_PENDING),
            self::PENDING_PROPERTY_CODE => json_encode($pending, JSON_UNESCAPED_UNICODE),
        ]);
    }

    /** Только если картинка реально поменялась — заливаем файл сразу (иначе
     *  на каждое сохранение черновика плодили бы копию неизменной картинки),
     *  ключ в $pending не пишем вовсе, если менять нечего (см. commitPending()
     *  и mergePending() — array_key_exists отличает "не менялось" от "снято"). */
    private function applyPendingImage(array &$pending, string $key, ?string $newValue, ?string $oldValue): void
    {
        if ($newValue === null || $newValue === $oldValue) {
            return;
        }
        if ($newValue === '') {
            $pending[$key] = 0;

            return;
        }
        $fileId = FileUploader::saveFromDataUrl($newValue, self::UPLOAD_SUBDIR);
        if ($fileId) {
            $pending[$key] = $fileId;
        }
    }

    /** Переносит черновик PENDING_CHANGES в боевые поля/свойства и чистит
     *  сам черновик — см. get(). */
    private function commitPending(int $partnerId, array $pending): void
    {
        $fields = [
            'NAME' => (string)($pending['name_full'] ?? ''),
            'PREVIEW_TEXT' => (string)($pending['short_desc'] ?? ''),
            'DETAIL_TEXT' => (string)($pending['full_desc'] ?? ''),
        ];
        if (array_key_exists('logo_file_id', $pending)) {
            $fileId = (int)$pending['logo_file_id'];
            $fields['PREVIEW_PICTURE'] = $fileId > 0 ? \CFile::MakeFileArray($fileId) : false;
        }
        if (array_key_exists('image_file_id', $pending)) {
            $fileId = (int)$pending['image_file_id'];
            $fields['DETAIL_PICTURE'] = $fileId > 0 ? \CFile::MakeFileArray($fileId) : false;
        }
        (new CIBlockElement())->Update($partnerId, $fields);

        $legal = (array)($pending['legal'] ?? []);
        $contacts = (array)($pending['contacts'] ?? []);

        CIBlockElement::SetPropertyValuesEx($partnerId, $this->iblockId, [
            'NAME_SHORT' => (string)($pending['name_short'] ?? ''),
            'LEGAL_INN' => (string)($legal['inn'] ?? ''),
            'LEGAL_OGRN' => (string)($legal['ogrn'] ?? ''),
            'LEGAL_ADDRESS' => (string)($legal['legal_address'] ?? ''),
            'LEGAL_BANK_NAME' => (string)($legal['bank_name'] ?? ''),
            'LEGAL_BIK' => (string)($legal['bik'] ?? ''),
            'LEGAL_ACCOUNT' => (string)($legal['account'] ?? ''),
            'LEGAL_CORR_ACCOUNT' => (string)($legal['corr_account'] ?? ''),
            'LEGAL_CEO_NAME' => (string)($legal['ceo_name'] ?? ''),
            'CONTACT_PHONE' => (string)($contacts['phone'] ?? ''),
            'CONTACT_EMAIL' => (string)($contacts['email'] ?? ''),
            'CONTACT_PERSON' => (string)($contacts['contact_person'] ?? ''),
            'CONTACT_POSITION' => (string)($contacts['contact_position'] ?? ''),
            self::PENDING_PROPERTY_CODE => '',
        ]);
    }

    /** Подмешивает черновик поверх боевых данных для отображения в форме,
     *  пока партнёра не проверили заново (см. get()). */
    private function mergePending(array $data, array $pending): array
    {
        foreach (['name_full', 'name_short', 'short_desc', 'full_desc'] as $key) {
            if (array_key_exists($key, $pending)) {
                $data[$key] = $pending[$key];
            }
        }
        if (isset($pending['legal'])) {
            $data['legal'] = array_merge($data['legal'], (array)$pending['legal']);
        }
        if (isset($pending['contacts'])) {
            $data['contacts'] = array_merge($data['contacts'], (array)$pending['contacts']);
        }
        if (array_key_exists('logo_file_id', $pending)) {
            $fileId = (int)$pending['logo_file_id'];
            $data['logo'] = $fileId > 0 ? FileUploader::getPath($fileId) : '';
        }
        if (array_key_exists('image_file_id', $pending)) {
            $fileId = (int)$pending['image_file_id'];
            $data['image'] = $fileId > 0 ? FileUploader::getPath($fileId) : '';
        }

        return $data;
    }

    private function resolveIblockId(): int
    {
        $iblock = CIBlock::GetList([], [
            'CODE' => self::IBLOCK_CODE,
            'TYPE' => self::IBLOCK_TYPE,
            'CHECK_PERMISSIONS' => 'N',
        ])->Fetch();

        if (!$iblock) {
            throw new \RuntimeException(
                'Инфоблок "' . self::IBLOCK_CODE . '" не найден — прогнаны ли миграции модуля formaro.cabinet?'
            );
        }

        return (int)$iblock['ID'];
    }

    private function toArray(array $el): array
    {
        return [
            'id' => (int)$el['ID'],
            'verification_status' => $this->resolveStatus($el),
            'name_full' => $el['NAME'],
            'name_short' => $el['PROPERTY_NAME_SHORT_VALUE'] ?? '',
            'logo' => FileUploader::getPath($el['PREVIEW_PICTURE'] ?: null),
            'image' => FileUploader::getPath($el['DETAIL_PICTURE'] ?: null),
            'short_desc' => $el['PREVIEW_TEXT'] ?? '',
            'full_desc' => $el['DETAIL_TEXT'] ?? '',
            'legal' => [
                'inn' => $el['PROPERTY_LEGAL_INN_VALUE'] ?? '',
                'ogrn' => $el['PROPERTY_LEGAL_OGRN_VALUE'] ?? '',
                'legal_address' => $el['PROPERTY_LEGAL_ADDRESS_VALUE'] ?? '',
                'bank_name' => $el['PROPERTY_LEGAL_BANK_NAME_VALUE'] ?? '',
                'bik' => $el['PROPERTY_LEGAL_BIK_VALUE'] ?? '',
                'account' => $el['PROPERTY_LEGAL_ACCOUNT_VALUE'] ?? '',
                'corr_account' => $el['PROPERTY_LEGAL_CORR_ACCOUNT_VALUE'] ?? '',
                'ceo_name' => $el['PROPERTY_LEGAL_CEO_NAME_VALUE'] ?? '',
            ],
            'contacts' => [
                'phone' => $el['PROPERTY_CONTACT_PHONE_VALUE'] ?? '',
                'email' => $el['PROPERTY_CONTACT_EMAIL_VALUE'] ?? '',
                'contact_person' => $el['PROPERTY_CONTACT_PERSON_VALUE'] ?? '',
                'contact_position' => $el['PROPERTY_CONTACT_POSITION_VALUE'] ?? '',
            ],
        ];
    }

    private function enumXmlId(int $enumId): string
    {
        $enum = \CIBlockPropertyEnum::GetList([], ['ID' => $enumId])->Fetch();

        return $enum['XML_ID'] ?? self::STATUS_NOT_VERIFIED;
    }

    private function resolveEnumIdByXmlId(string $xmlId): int
    {
        $enum = \CIBlockPropertyEnum::GetList([], [
            'IBLOCK_ID' => $this->iblockId,
            'CODE' => self::STATUS_PROPERTY_CODE,
            'XML_ID' => $xmlId,
        ])->Fetch();

        if (!$enum) {
            throw new \RuntimeException(
                'Значение "' . $xmlId . '" свойства ' . self::STATUS_PROPERTY_CODE . ' не найдено — прогнана ли миграция Version20260922190001?'
            );
        }

        return (int)$enum['ID'];
    }
}
