<?php /** @var int $page @var int $pages @var string $url */
if ($pages <= 1) {
    return;
}
$link = static fn (int $p): string => $url . (str_contains($url, '?') ? '&' : '?') . 'page=' . $p;
$window = array_unique(array_filter([1, $page - 1, $page, $page + 1, $pages], static fn ($p) => $p >= 1 && $p <= $pages));
sort($window);
?>
<nav class="pager" aria-label="<?= e(__('Pages')) ?>">
    <a class="pager-item <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= $page > 1 ? e($link($page - 1)) : '#' ?>" aria-label="<?= e(__('Previous')) ?>">‹</a>
    <?php $prev = 0; foreach ($window as $p): ?>
        <?php if ($p - $prev > 1): ?><span class="pager-gap">…</span><?php endif ?>
        <a class="pager-item <?= $p === $page ? 'active' : '' ?>" href="<?= e($link($p)) ?>" <?= $p === $page ? 'aria-current="page"' : '' ?>><?= $p ?></a>
    <?php $prev = $p; endforeach ?>
    <a class="pager-item <?= $page >= $pages ? 'disabled' : '' ?>" href="<?= $page < $pages ? e($link($page + 1)) : '#' ?>" aria-label="<?= e(__('Next')) ?>">›</a>
</nav>
