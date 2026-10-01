<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261001220001 extends Version
{
    protected $description = 'Контентные страницы: в «Покупателям» пункт «Заказы» заменён страницей «Оптовикам»';

    private const TEXT = <<<'HTML'
<p class="lead">Добро пожаловать в программу партнерского маркетинга «Форма Партнер»!</p>
<p>Мы заинтересованы в сотрудничестве с оптовыми и постоянными покупателями. На сайте указаны розничные цены. Для оптовых покупателей и юридических лиц у нас имеются специальные условия и гибкая система скидок, которые обсуждаются индивидуально.</p>
<p>Для того, чтобы оформить заказ по оптовой цене, вам необходимо зарегистрироваться или авторизоваться. Пожалуйста, свяжитесь с нами по телефонам:</p>
<ul>
    <li><a href="tel:+79671265771">+7 967 126 57 71</a></li>
    <li><a href="tel:+74957927189">+7 495 792 71 89</a></li>
</ul>
HTML;

    /**
     * Пункт-ссылка «Заказы» (/personal/orders/) раздела «Покупателям»
     * становится страницей «Оптовикам» (/for-buyers/wholesale/, текст —
     * formaro.ru/partners/pokupat-tovary/) на том же месте в сортировке.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists('content_pages', 'content');
        if ($helper->Iblock()->getElementId($iblockId, 'wholesale')) {
            $this->outSuccess('Страница «Оптовикам» уже есть');
            return;
        }
        $id = $helper->Iblock()->getElementId($iblockId, 'orders');
        if (!$id) {
            throw new HelperException('Нет пункта «Заказы» (orders) — сначала Version20261001210001');
        }
        $el = new \CIBlockElement();
        if (!$el->Update($id, [
            'NAME' => 'Оптовикам',
            'CODE' => 'wholesale',
            'DETAIL_TEXT' => self::TEXT,
            'DETAIL_TEXT_TYPE' => 'html',
        ])) {
            throw new HelperException($el->LAST_ERROR);
        }
        \CIBlockElement::SetPropertyValuesEx($id, $iblockId, ['LINK' => '', 'ICON' => 'icon-shopping']);
        \CIBlock::clearIblockTagCache($iblockId);

        $this->outSuccess('«Заказы» заменён страницей «Оптовикам» (ID %d)', $id);
    }

    /** @throws HelperException */
    public function down()
    {
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists('content_pages', 'content');
        $id = $helper->Iblock()->getElementId($iblockId, 'wholesale');
        if ($id) {
            (new \CIBlockElement())->Update($id, ['NAME' => 'Заказы', 'CODE' => 'orders', 'DETAIL_TEXT' => '']);
            \CIBlockElement::SetPropertyValuesEx($id, $iblockId, ['LINK' => '/personal/orders/', 'ICON' => 'icon-clipboard-text']);
            \CIBlock::clearIblockTagCache($iblockId);
        }
        $this->outSuccess('Пункт «Заказы» возвращён');
    }
}
