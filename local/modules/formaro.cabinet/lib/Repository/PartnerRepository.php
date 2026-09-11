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

    private int $iblockId;

    public function __construct()
    {
        Loader::includeModule('iblock');
        $this->iblockId = $this->resolveIblockId();
    }

    public function get(int $partnerId): ?array
    {
        $el = CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $this->iblockId, 'ID' => $partnerId, 'CHECK_PERMISSIONS' => 'N'],
            false,
            false,
            ['*', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'PROPERTY_*']
        )->Fetch();

        return $el ? $this->toArray($el) : null;
    }

    public function save(int $partnerId, array $payload): array
    {
        $existing = $this->get($partnerId);
        if (!$existing) {
            throw new \RuntimeException('Партнёр не найден (ID=' . $partnerId . ')');
        }

        $fields = [
            'NAME' => (string)($payload['name_full'] ?? $existing['name_full']),
            'PREVIEW_TEXT' => (string)($payload['short_desc'] ?? ''),
            'DETAIL_TEXT' => (string)($payload['full_desc'] ?? ''),
        ];

        $this->applyImage($fields, 'PREVIEW_PICTURE', $payload['logo'] ?? null, $existing['logo'] ?? null);
        $this->applyImage($fields, 'DETAIL_PICTURE', $payload['image'] ?? null, $existing['image'] ?? null);

        $ok = (new CIBlockElement())->Update($partnerId, $fields);
        if (!$ok) {
            throw new \RuntimeException('Не удалось сохранить профиль партнёра');
        }

        $legal = (array)($payload['legal'] ?? []);
        $contacts = (array)($payload['contacts'] ?? []);

        CIBlockElement::SetPropertyValuesEx($partnerId, $this->iblockId, [
            'NAME_SHORT' => (string)($payload['name_short'] ?? ''),
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
        ]);

        return $this->get($partnerId);
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
            'verification_status' => $el['PROPERTY_VERIFICATION_STATUS_ENUM_ID']
                ? $this->enumXmlId((int)$el['PROPERTY_VERIFICATION_STATUS_ENUM_ID'])
                : 'pending',
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

        return $enum['XML_ID'] ?? 'pending';
    }

    private function applyImage(array &$fields, string $fieldCode, ?string $newValue, ?string $oldValue): void
    {
        if ($newValue === null || $newValue === $oldValue) {
            return;
        }
        if ($newValue === '') {
            $fields[$fieldCode] = false;
            return;
        }
        $fileId = FileUploader::saveFromDataUrl($newValue, self::UPLOAD_SUBDIR);
        if ($fileId) {
            $fields[$fieldCode] = $fileId;
        }
    }
}
