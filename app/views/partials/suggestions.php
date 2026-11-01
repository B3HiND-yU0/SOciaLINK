<?php /** @var array $suggestions */ ?>
<?php if ($suggestions): ?>
<?php foreach ($suggestions as $s): ?>
<div class="person-row">
    <a href="<?= e(url('profile/show/' . rawurlencode($s['username']))) ?>" class="person-link">
        <?= avatar_html($s['profile_image'], $s['full_name'], (int)$s['id'], 40) ?>
        <span class="person-text"><strong><?= e($s['full_name']) ?></strong><small>@<?= e($s['username']) ?></small></span>
    </a>
    <button type="button" class="btn btn-outline-signal btn-sm" data-follow="<?= (int)$s['id'] ?>" data-follow-label="compact">Follow</button>
</div>
<?php endforeach; ?>
<?php else: ?>
<p class="text-muted small mb-0">You are following everyone here. Nice.</p>
<?php endif; ?>
