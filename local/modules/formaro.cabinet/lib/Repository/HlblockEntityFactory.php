<?php

namespace Formaro\Cabinet\Repository;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;

/** Резолвит ORM-класс (DataManager) HL-блока по имени — общий кусок для всех
 *  репозиториев, завязанных на HL-блоки (см. также миграцию импорта
 *  ProductColors, где используется тот же приём напрямую). */
class HlblockEntityFactory
{
    /** @var array<string, class-string> */
    private static array $cache = [];

    /** @return class-string */
    public static function getDataClass(string $hlblockName): string
    {
        if (isset(self::$cache[$hlblockName])) {
            return self::$cache[$hlblockName];
        }

        Loader::includeModule('highloadblock');
        $hlblock = HighloadBlockTable::getList(['filter' => ['NAME' => $hlblockName]])->fetch();
        if (!$hlblock) {
            throw new \RuntimeException('HL-блок "' . $hlblockName . '" не найден — прогнаны ли миграции?');
        }

        $dataClass = HighloadBlockTable::compileEntity($hlblock)->getDataClass();
        self::$cache[$hlblockName] = $dataClass;

        return $dataClass;
    }
}
