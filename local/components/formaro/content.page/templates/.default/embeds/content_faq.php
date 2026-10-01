<?php
/** @var array $items Вопросы и ответы (content_faq): NAME — вопрос, TEXT — ответ */
/** @var callable $e */
?>
<div class="faq">
    <?php foreach ($items as $item): ?>
    <details>
        <summary><?= $e($item['NAME']) ?></summary>
        <div><?= $item['TEXT'] ?></div>
    </details>
    <?php endforeach; ?>
</div>
