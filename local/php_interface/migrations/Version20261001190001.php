<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261001190001 extends Version
{
    protected $description = 'Контент сайта: инфоблоки «Контентные страницы», «Вопросы и ответы», «Отзывы», «Вакансии» (тип content)';

    /**
     * Контентные страницы разделов «Покупателям», «Партнерам», «О нас»,
     * «Поддержка» (formaro:content.page) — элементы content_pages: NAME —
     * заголовок, CODE — код страницы (его передаёт index.php папки),
     * DETAIL_TEXT — текст (HTML), SEO — вкладка «SEO» элемента. Свойство
     * EMBED (список, множественное) — блоки других инфоблоков под текстом:
     * значение = XML_ID = код инфоблока (content_faq / content_reviews /
     * content_vacancies).
     *
     * Вопросы и ответы (content_faq): NAME — вопрос, PREVIEW_TEXT — ответ.
     * Отзывы (content_reviews): NAME — автор, ACTIVE_FROM — дата,
     * PREVIEW_TEXT — текст. Вакансии (content_vacancies): NAME — должность,
     * SALARY — зарплата, PREVIEW_TEXT — описание, EMAIL — куда слать резюме.
     * Порядок — SORT.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $pagesId = $this->iblock('content_pages', 'Контентные страницы', 100);
        $faqId = $this->iblock('content_faq', 'Вопросы и ответы', 110);
        $reviewsId = $this->iblock('content_reviews', 'Отзывы', 120);
        $vacanciesId = $this->iblock('content_vacancies', 'Вакансии', 130);

        $helper->Iblock()->saveProperty($pagesId, [
            'NAME' => 'Встроенные блоки (под текстом)',
            'CODE' => 'EMBED',
            'PROPERTY_TYPE' => 'L',
            'LIST_TYPE' => 'C',
            'MULTIPLE' => 'Y',
            'SORT' => 100,
            'HINT' => 'Блоки других инфоблоков, которые выводятся на странице после текста',
            'VALUES' => [
                ['VALUE' => 'Вопросы и ответы', 'XML_ID' => 'content_faq', 'SORT' => 100],
                ['VALUE' => 'Отзывы', 'XML_ID' => 'content_reviews', 'SORT' => 200],
                ['VALUE' => 'Вакансии', 'XML_ID' => 'content_vacancies', 'SORT' => 300],
            ],
        ]);

        $helper->Iblock()->saveProperty($vacanciesId, [
            'NAME' => 'Зарплата', 'CODE' => 'SALARY', 'PROPERTY_TYPE' => 'S', 'SORT' => 100,
        ]);
        $helper->Iblock()->saveProperty($vacanciesId, [
            'NAME' => 'E-mail для резюме', 'CODE' => 'EMAIL', 'PROPERTY_TYPE' => 'S', 'SORT' => 200,
            'DEFAULT_VALUE' => 'kadr@formaro.ru',
        ]);

        $this->outSuccess('Инфоблоки контента готовы: страницы %d, вопросы %d, отзывы %d, вакансии %d', $pagesId, $faqId, $reviewsId, $vacanciesId);
    }

    /**
     * @throws HelperException
     */
    public function down()
    {
        $helper = $this->getHelperManager();
        foreach (['content_vacancies', 'content_reviews', 'content_faq', 'content_pages'] as $code) {
            $helper->Iblock()->deleteIblockIfExists($code, 'content');
        }
        $this->outSuccess('Инфоблоки контента удалены');
    }

    /** @throws HelperException */
    private function iblock(string $code, string $name, int $sort): int
    {
        $helper = $this->getHelperManager();
        $id = $helper->Iblock()->saveIblock([
            'NAME' => $name,
            'CODE' => $code,
            'IBLOCK_TYPE_ID' => 'content',
            'LID' => ['s1'],
            'VERSION' => 2,
            'GROUP_ID' => ['2' => 'R'],
            'LIST_PAGE_URL' => '',
            'DETAIL_PAGE_URL' => '',
            'SORT' => $sort,
        ]);
        $helper->Iblock()->saveIblockFields($id, [
            'CODE' => ['DEFAULT_VALUE' => ['TRANSLITERATION' => 'Y', 'UNIQUE' => 'Y']],
            'PREVIEW_TEXT_TYPE' => ['DEFAULT_VALUE' => 'html'],
            'DETAIL_TEXT_TYPE' => ['DEFAULT_VALUE' => 'html'],
        ]);

        return $id;
    }
}
