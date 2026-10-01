<?php
/** @var array $items Отзывы (content_reviews): NAME — автор, DATE, TEXT */
/** @var callable $e */
?>
<div class="reviews">
    <?php foreach ($items as $item): ?>
    <div class="review">
        <div class="review__head">
            <span class="review__author"><?= $e($item['NAME']) ?></span>
            <?php if ($item['DATE'] !== ''): ?><small class="text-muted"><?= $e($item['DATE']) ?></small><?php endif; ?>
        </div>
        <?= $item['TEXT'] ?>
    </div>
    <?php endforeach; ?>
</div>
