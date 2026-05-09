<?php

function page_info(string $title, string $body, array $items = []): void {
    ?>
    <div class="alert alert-info mb-20" role="alert">
        <h6 class="alert-heading mb-1"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h6>
        <p class="mb-0"><?= htmlspecialchars($body, ENT_QUOTES, 'UTF-8') ?></p>
        <?php if ($items): ?>
            <ul class="mb-0 mt-2 pl-3">
                <?php foreach ($items as $item): ?>
                    <li><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <?php
}
