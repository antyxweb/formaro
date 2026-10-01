<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Version20261001190002 extends Version
{
    protected $description = 'Контент сайта: наполнение контентных страниц, вопросов и ответов, отзывов и вакансий (data/content_seed.json)';

    /**
     * Тексты страниц разделов «Покупателям», «Партнерам», «О нас»,
     * «Поддержка» (раньше лежали в index.php папок), вопросы и ответы,
     * отзывы и вакансии — из data/content_seed.json. Только добавление:
     * элемент с таким CODE уже есть (например, поправлен в админке) — не
     * трогаем.
     *
     * @throws HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $data = json_decode((string)file_get_contents(__DIR__ . '/data/content_seed.json'), true);
        if (!$data) {
            throw new HelperException('Не прочитан data/content_seed.json');
        }

        $pagesId = $this->iblockId('content_pages');
        $embedIds = [];
        $enums = \CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => $pagesId, 'CODE' => 'EMBED']);
        while ($enum = $enums->Fetch()) {
            $embedIds[$enum['XML_ID']] = (int)$enum['ID'];
        }
        $map = ['faq' => 'content_faq', 'reviews' => 'content_reviews', 'vacancies' => 'content_vacancies'];

        $added = 0;
        foreach ($data['pages'] as $page) {
            $embed = array_values(array_filter(array_map(static fn($e) => $embedIds[$map[$e] ?? ''] ?? 0, $page['embed'])));
            $added += $this->add($pagesId, [
                'NAME' => $page['name'],
                'CODE' => $page['code'],
                'SORT' => $page['sort'],
                'DETAIL_TEXT' => $page['text'],
                'DETAIL_TEXT_TYPE' => 'html',
            ], $embed ? ['EMBED' => $embed] : []);
        }

        $faqId = $this->iblockId('content_faq');
        foreach ($data['faq'] as $i => $item) {
            $added += $this->add($faqId, [
                'NAME' => $item['name'],
                'CODE' => 'faq-' . ($i + 1),
                'SORT' => ($i + 1) * 10,
                'PREVIEW_TEXT' => $item['text'],
                'PREVIEW_TEXT_TYPE' => 'html',
            ]);
        }

        $reviewsId = $this->iblockId('content_reviews');
        foreach ($data['reviews'] as $i => $item) {
            $added += $this->add($reviewsId, [
                'NAME' => $item['name'],
                'CODE' => 'review-' . ($i + 1),
                'SORT' => ($i + 1) * 10,
                'ACTIVE_FROM' => $item['date'],
                'PREVIEW_TEXT' => $item['text'],
                'PREVIEW_TEXT_TYPE' => 'html',
            ]);
        }

        $vacanciesId = $this->iblockId('content_vacancies');
        foreach ($data['vacancies'] as $i => $item) {
            $added += $this->add($vacanciesId, [
                'NAME' => $item['name'],
                'CODE' => 'vacancy-' . ($i + 1),
                'SORT' => ($i + 1) * 10,
            ], ['SALARY' => $item['salary'], 'EMAIL' => 'kadr@formaro.ru']);
        }

        $this->outSuccess('Контент: добавлено элементов — %d', $added);
    }

    /**
     * Наполнение не удаляем: элементы могли поправить в админке. Инфоблоки
     * целиком удаляет откат Version20261001190001.
     */
    public function down()
    {
        $this->outSuccess('Откат наполнения не выполняется — удалите инфоблоки откатом Version20261001190001');
    }

    /** @throws HelperException */
    private function iblockId(string $code): int
    {
        $id = $this->getHelperManager()->Iblock()->getIblockIdIfExists($code, 'content');
        if (!$id) {
            throw new HelperException('Инфоблок ' . $code . ' не найден — сначала Version20261001190001');
        }

        return $id;
    }

    /** @return int 1 — добавлен, 0 — уже был */
    private function add(int $iblockId, array $fields, array $props = []): int
    {
        $helper = $this->getHelperManager();
        if ($helper->Iblock()->getElementId($iblockId, $fields['CODE'])) {
            return 0;
        }
        $helper->Iblock()->addElement($iblockId, $fields + ['ACTIVE' => 'Y'], $props);

        return 1;
    }
}
