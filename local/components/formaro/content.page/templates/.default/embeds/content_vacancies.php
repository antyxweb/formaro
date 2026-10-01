<?php
/** @var array $items Вакансии (content_vacancies): NAME, SALARY, TEXT, EMAIL */
/** @var callable $e */
?>
<div class="text-page__cards">
    <?php foreach ($items as $item): ?>
    <div class="text-page__card">
        <h3><?= $e($item['NAME']) ?></h3>
        <?php if ($item['SALARY'] !== ''): ?><p>Зарплата: <?= $e($item['SALARY']) ?></p><?php endif; ?>
        <?= $item['TEXT'] ?>
        <?php if ($item['EMAIL'] !== ''): ?>
        <p><a href="mailto:<?= $e($item['EMAIL']) ?>?subject=<?= rawurlencode('Вакансия: ' . $item['NAME']) ?>">Отправить резюме</a></p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
