<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20260923120001 extends Version
{
    protected $description = 'formaro.cabinet: перенести картинки разделов каталога из боевого инфоблока "Каталог товаров" (catalog) в системные категории кабинета';

    /**
     * Дерево системных категорий кабинета (cabinet_catalog, см.
     * Version20260911193007) зеркалит структуру боевого каталога сайта
     * (инфоблок CODE=catalog, TYPE=catalog, тот самый, что отдаёт /catalog/)
     * один в один по названиям — категории сидировались из того же
     * cabinet-html/data/categories.json, но без картинок (см. докблок той
     * миграции: "плейсхолдеры из прототипа не переносим"). Здесь подтягиваем
     * настоящие картинки из боевого каталога вместо плейсхолдеров.
     *
     * Сопоставление разделов — по нормализованному CODE (без учёта "-"/"_"
     * и регистра): в боевом каталоге код транслитерируется с подчёркиваниями
     * (sredstva_individualnoy_zashchity), у нас — с дефисами
     * (sredstva-individualnoy-zashchity), при этом сам транслит из
     * одинаковых названий совпадает посимвольно, кроме разделителя.
     *
     * PICTURE — нативное поле раздела, не обычное File-свойство: если
     * передать голый ID файла в CIBlockSection::Update(), Bitrix ждёт файл,
     * специально зарегистрированный для ЭТОГО раздела через WF-механизм
     * (та же особенность, что и у PREVIEW_PICTURE элемента, см.
     * FileUploader::dataUrlToFileArray()) и падает с ошибкой. Здесь источник
     * — уже загруженный файл (существует физически), поэтому используем
     * штатный CFile::MakeFileArray($fileId) — он строит корректный
     * $_FILES-подобный массив по уже существующему файлу.
     *
     * Идемпотентно: раздел кабинета, у которого PICTURE уже заполнена
     * (повторный прогон или файл проставлен вручную), не трогаем.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $sourceIblockId = $helper->Iblock()->getIblockIdIfExists('catalog', 'catalog');
        if (!$sourceIblockId) {
            $this->outNotice('Боевой инфоблок "Каталог товаров" (catalog/catalog) не найден — перенос картинок пропущен');

            return;
        }

        $targetIblockId = $helper->Iblock()->getIblockIdIfExists('cabinet_catalog', 'catalog');
        if (!$targetIblockId) {
            throw new HelperException(
                'Инфоблок "cabinet_catalog" не найден — сначала должна отработать миграция Version20260911193002'
            );
        }

        $sourceByCode = [];
        $rsSource = \CIBlockSection::GetList([], ['IBLOCK_ID' => $sourceIblockId], false, ['ID', 'CODE', 'PICTURE']);
        while ($row = $rsSource->Fetch()) {
            if ($row['PICTURE']) {
                $sourceByCode[$this->normalizeCode((string)$row['CODE'])] = (int)$row['PICTURE'];
            }
        }

        $copied = 0;
        $skippedHasPicture = 0;
        $unmatched = 0;

        $rsTarget = \CIBlockSection::GetList([], ['IBLOCK_ID' => $targetIblockId], false, ['ID', 'CODE', 'NAME', 'PICTURE']);
        while ($row = $rsTarget->Fetch()) {
            if ($row['PICTURE']) {
                $skippedHasPicture++;
                continue;
            }

            $sourceFileId = $sourceByCode[$this->normalizeCode((string)$row['CODE'])] ?? null;
            if (!$sourceFileId) {
                $unmatched++;
                continue;
            }

            $helper->Iblock()->updateSection((int)$row['ID'], [
                'PICTURE' => \CFile::MakeFileArray($sourceFileId),
            ]);
            $copied++;
        }

        $this->outSuccess(
            'Картинок перенесено: %d (уже была картинка: %d, без пары в боевом каталоге: %d)',
            $copied,
            $skippedHasPicture,
            $unmatched
        );
    }

    public function down()
    {
        $this->outNotice('Откат не выполняется — картинки разделов могли быть изменены партнёрами вручную после переноса');
    }

    private function normalizeCode(string $code): string
    {
        return strtolower(str_replace(['-', '_'], '', $code));
    }
}
