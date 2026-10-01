<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261001210001 extends Version
{
    protected $description = 'Контентные страницы: свойства «Ссылка» и «Иконка», пункты-ссылки разделов, порядок как в меню';

    /**
     * Разделы сайта «Покупателям», «Партнерам», «О нас», «Поддержка» теперь
     * целиком из инфоблока «Контентные страницы» (formaro:content.section):
     * плитки на главной раздела, меню справа и меню в подвале
     * (.left.menu_ext.php) — элементы раздела инфоблока по сортировке.
     *
     * - LINK — элемент-ссылка (не страница): пункт меню/плитка ведёт по
     *   ссылке (Кабинет покупателя, Заказы, Кабинет партнёра, Список
     *   партнеров);
     * - ICON — иконка плитки (id символа спрайта, например icon-wallet).
     *
     * Адрес страницы — /<код раздела>/<код элемента>/; элемент с кодом,
     * равным коду раздела, — главная раздела (/about/).
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists('content_pages', 'content');
        $helper->Iblock()->updateIblock($iblockId, [
            'SECTION_PAGE_URL' => '#SITE_DIR#/#SECTION_CODE#/',
            'DETAIL_PAGE_URL' => '#SITE_DIR#/#SECTION_CODE#/#ELEMENT_CODE#/',
        ]);

        $helper->Iblock()->saveProperty($iblockId, [
            'NAME' => 'Ссылка (вместо страницы)', 'CODE' => 'LINK', 'PROPERTY_TYPE' => 'S', 'SORT' => 200,
            'HINT' => 'Если заполнено — пункт меню и плитка ведут по этой ссылке, текст страницы не выводится',
        ]);
        $helper->Iblock()->saveProperty($iblockId, [
            'NAME' => 'Иконка плитки', 'CODE' => 'ICON', 'PROPERTY_TYPE' => 'S', 'SORT' => 300,
            'HINT' => 'Код иконки из спрайта шаблона, например icon-wallet',
        ]);

        // Раздел => [код => [название, ссылка, иконка]] в порядке меню.
        $items = [
            'for-buyers' => [
                'personal' => ['Кабинет покупателя', '/personal/', 'icon-profile'],
                'orders' => ['Заказы', '/personal/orders/', 'icon-clipboard-text'],
                'make-order' => [null, '', 'icon-cart'],
                'payment' => [null, '', 'icon-wallet'],
                'delivery' => [null, '', 'icon-delivery'],
                'returns' => [null, '', 'icon-exchange'],
            ],
            'for-partners' => [
                'cabinet' => ['Кабинет партнёра', '/cabinet/', 'icon-building'],
                'become-a-partner' => [null, '', 'icon-hand-heart'],
                'sell-products' => [null, '', 'icon-sale'],
                'partners-list' => ['Список партнеров', '/partners/', 'icon-shopping'],
            ],
            'about' => [
                'about' => [null, '', 'icon-info'],
                'details' => [null, '', 'icon-bill'],
                'contacts' => [null, '', 'icon-map-pin'],
                'vacancies' => [null, '', 'icon-profile'],
            ],
            'support' => [
                'faq' => [null, '', 'icon-warning-circle'],
                'reviews' => [null, '', 'icon-mail'],
                'policy' => [null, '', 'icon-shield-keyhole'],
                'cookie' => [null, '', 'icon-info'],
            ],
        ];

        $el = new \CIBlockElement();
        foreach ($items as $sectionCode => $list) {
            $section = \CIBlockSection::GetList([], ['IBLOCK_ID' => $iblockId, '=CODE' => $sectionCode], false, ['ID'])->Fetch();
            if (!$section) {
                throw new HelperException('Нет раздела ' . $sectionCode . ' — сначала Version20261001200001');
            }
            $sort = 0;
            foreach ($list as $code => [$name, $link, $icon]) {
                $sort += 10;
                $id = $helper->Iblock()->getElementId($iblockId, $code);
                if (!$id && $name !== null) {
                    $id = $helper->Iblock()->addElement($iblockId, [
                        'NAME' => $name, 'CODE' => $code, 'ACTIVE' => 'Y', 'SORT' => $sort,
                        'IBLOCK_SECTION_ID' => (int)$section['ID'],
                    ], ['LINK' => $link, 'ICON' => $icon]);
                    continue;
                }
                if ($id) {
                    $el->Update($id, ['SORT' => $sort]);
                    \CIBlockElement::SetPropertyValuesEx($id, $iblockId, ['ICON' => $icon]);
                }
            }
        }
        \CIBlock::clearIblockTagCache($iblockId);

        $this->outSuccess('Свойства LINK/ICON и пункты разделов готовы');
    }

    /** @throws HelperException */
    public function down()
    {
        $helper = $this->getHelperManager();
        $iblockId = $helper->Iblock()->getIblockIdIfExists('content_pages', 'content');
        foreach (['personal', 'orders', 'cabinet', 'partners-list'] as $code) {
            $helper->Iblock()->deleteElementIfExists($iblockId, $code);
        }
        $helper->Iblock()->deletePropertyIfExists($iblockId, 'LINK');
        $helper->Iblock()->deletePropertyIfExists($iblockId, 'ICON');
        $this->outSuccess('Пункты-ссылки и свойства LINK/ICON удалены');
    }
}
