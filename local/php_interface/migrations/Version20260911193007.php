<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20260911193007 extends Version
{
    protected $description = 'formaro.cabinet: сид системных категорий каталога (201 шт. из cabinet-html/data/categories.json)';

    /**
     * Общие категории каталога, одинаковые для всех партнёров (UF_IS_SYSTEM=Y,
     * без UF_PARTNER_ID) — партнёр видит их, может выбирать при добавлении
     * товара, но не может редактировать/удалять (см. cabinet-html/CLAUDE.md,
     * "Мультитенантность", и CategoryRepository::canEdit()).
     *
     * Источник — тот же файл, на котором работает статический прототип
     * (cabinet-html/data/categories.json), чтобы дерево категорий в реальном
     * кабинете совпадало с тем, что согласовывали в прототипе. Картинки-
     * плейсхолдеры (placehold.co) из прототипа не переносим — это заглушки
     * для демо, а не настоящие файлы.
     *
     * Изначальные числовые id из JSON не сохраняем (разделы получают
     * собственные ID_SECTION), но обрабатываем строго в порядке возрастания
     * исходного id — в этом датасете parent_id всегда меньше id самой
     * категории, поэтому к моменту создания потомка родитель уже создан
     * и известна его новая привязка (см. $idMap).
     *
     * Идемпотентно на уровне каждой категории (addSectionIfNotExists по
     * CODE) — безопасно перезапускать.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $iblockId = $helper->Iblock()->getIblockIdIfExists('cabinet_catalog', 'catalog');
        if (!$iblockId) {
            throw new HelperException(
                'Инфоблок "cabinet_catalog" не найден — сначала должна отработать миграция Version20260911193002'
            );
        }

        $jsonPath = $_SERVER['DOCUMENT_ROOT'] . '/cabinet-html/data/categories.json';
        if (!is_file($jsonPath)) {
            throw new HelperException('Файл не найден: ' . $jsonPath);
        }

        $rows = json_decode(file_get_contents($jsonPath), true);
        if (!is_array($rows)) {
            throw new HelperException('Не удалось распарсить ' . $jsonPath);
        }

        $systemRows = array_filter($rows, static fn($r) => !empty($r['is_system']));
        usort($systemRows, static fn($a, $b) => $a['id'] <=> $b['id']);

        $idMap = []; // старый id из JSON => новый ID_SECTION
        $created = 0;
        foreach ($systemRows as $row) {
            $parentOldId = (int)($row['parent_id'] ?? 0);
            $parentNewId = $parentOldId ? ($idMap[$parentOldId] ?? false) : false;

            $sectionId = $helper->Iblock()->addSectionIfNotExists($iblockId, [
                'CODE' => $row['slug'],
                'NAME' => $row['name'],
                'IBLOCK_SECTION_ID' => $parentNewId,
                'SORT' => $row['sort_order'] ?? 500,
                'ACTIVE' => 'Y',
                'DESCRIPTION' => $row['short_desc'] ?? '',
                'DESCRIPTION_TYPE' => 'text',
                'UF_IS_SYSTEM' => 1,
            ]);

            if (!empty($row['full_desc'])) {
                $helper->Iblock()->updateSection($sectionId, ['UF_FULL_DESC' => $row['full_desc']]);
            }

            $idMap[$row['id']] = $sectionId;
            $created++;
        }

        $this->outSuccess('Системных категорий обработано: %d', $created);
    }

    public function down()
    {
        $this->outNotice('Откат сидирования категорий не выполняется — категории могли уже использоваться в товарах, удаление вручную');
    }
}
