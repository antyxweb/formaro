<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261008120001 extends Version
{
    protected $description = 'Контент: возвраты по вине поставщика — за его счёт, претензии — официальным письмом (ТЗ «Финансы»)';

    private const EMAIL = 'info@formaro.ru';

    /**
     * Абзацы по страницам content_pages: [код страницы, перед каким фрагментом
     * вставить (не найден — в конец), HTML]. Метка id="supplier-returns" —
     * повторный запуск ничего не дублирует, остальной текст страницы
     * (мог быть изменён в админке) не трогается.
     */
    private static function blocks(): array
    {
        $mail = '<a href="mailto:' . self::EMAIL . '">' . self::EMAIL . '</a>';
        $partner = '<h3 id="supplier-returns">Возвраты и претензии</h3>'
            . "\n" . '<p>Возвраты по вине поставщика — брак, пересорт, недовложение — производятся за счёт поставщика. Споры и претензии рассматриваются только по официальной переписке с площадкой на e-mail ' . $mail . '.</p>' . "\n";

        return [
            ['sell-products', '<p><a href="/for-partners/become-a-partner/"', $partner],
            ['become-a-partner', '<p>Уже партнер?', $partner],
            ['returns', '', '<p id="supplier-returns">Если товар пришёл с браком, пересортом или недовложением, направьте официальную претензию на e-mail площадки ' . $mail
                . ' с номером заказа, описанием и фотографиями. Такие возвраты площадка решает с продавцом, расходы оплачивает продавец.</p>' . "\n"],
        ];
    }

    /** @throws HelperException */
    public function up()
    {
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists('content_pages', 'content');
        $updated = 0;
        foreach (self::blocks() as [$code, $before, $html]) {
            $id = (int)$helper->Iblock()->getElementId($iblockId, $code);
            if (!$id) {
                continue;
            }
            $row = \CIBlockElement::GetByID($id)->Fetch();
            $text = (string)$row['DETAIL_TEXT'];
            if (strpos($text, 'id="supplier-returns"') !== false) {
                continue;
            }
            $pos = $before !== '' ? strpos($text, $before) : false;
            $text = $pos === false ? rtrim($text) . "\n" . $html : substr($text, 0, $pos) . $html . substr($text, $pos);
            (new \CIBlockElement())->Update($id, ['DETAIL_TEXT' => $text, 'DETAIL_TEXT_TYPE' => 'html']);
            $updated++;
        }
        \CIBlock::clearIblockTagCache($iblockId);
        $this->outSuccess('Страниц дополнено: %d', $updated);
    }

    public function down()
    {
        $this->outInfo('Откат текстов страниц не выполняется');
    }
}
