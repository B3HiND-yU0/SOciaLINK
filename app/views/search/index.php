<?php /** @var string $q @var string $type @var array $users @var array $posts @var bool $tooShort */ ?>
<main class="page narrow">
    <h1 class="page-title">Search</h1>
    <form class="search-hero card-surface" action="<?= e(url()) ?>" method="get">
        <input type="hidden" name="r" value="search/index">
        <input type="hidden" name="type" value="<?= e($type) ?>">
        <i class="bi bi-search"></i>
        <input type="search" name="q" value="<?= e($q) ?>" class="form-control" placeholder="<?= $type === 'users' ? 'Search by name or username' : 'Search posts by keyword or #topic' ?>" maxlength="60" aria-label="Search" autofocus>
        <button class="btn btn-signal" type="submit"><i class="bi bi-search"></i> Search</button>
    </form>

    <div class="tabs" role="tablist">
        <a role="tab" aria-selected="<?= $type === 'users' ? 'true' : 'false' ?>" class="<?= $type === 'users' ? 'active' : '' ?>" href="<?= e(url('search/index', ['q' => $q, 'type' => 'users'])) ?>"><i class="bi bi-people"></i> People</a>
        <a role="tab" aria-selected="<?= $type === 'posts' ? 'true' : 'false' ?>" class="<?= $type === 'posts' ? 'active' : '' ?>" href="<?= e(url('search/index', ['q' => $q, 'type' => 'posts'])) ?>"><i class="bi bi-chat-square-text"></i> Posts</a>
    </div>

    <?php if ($q === ''): ?>
        <div class="empty-state card-surface"><i class="bi bi-search-heart"></i><h2>Find your people</h2><p>Type a name, a username or a #topic to get started.</p></div>
    <?php elseif ($tooShort): ?>
        <div class="empty-state card-surface"><i class="bi bi-keyboard"></i><h2>Keep typing</h2><p>Search needs at least 2 characters.</p></div>
    <?php elseif ($type === 'users'): ?>
        <?php if (!$users): ?>
            <div class="empty-state card-surface"><i class="bi bi-emoji-neutral"></i><h2>No people found</h2><p>Nobody matches &ldquo;<?= e($q) ?>&rdquo;. Check the spelling or try a shorter name.</p></div>
        <?php else: ?>
            <p class="result-count"><?= count($users) ?> <?= count($users) === 1 ? 'person' : 'people' ?> found</p>
            <div class="card-surface people-list">
                <?php foreach ($users as $u): ?>
                <a class="person-result" href="<?= e(url('profile/show/' . rawurlencode($u['username']))) ?>">
                    <?= avatar_html($u['profile_image'], $u['full_name'], (int)$u['id'], 52) ?>
                    <span class="person-text"><strong><?= e($u['full_name']) ?></strong><small>@<?= e($u['username']) ?></small>
                    <?php if (trim($u['bio'] ?? '') !== ''): ?><em><?= e(mb_strimwidth($u['bio'], 0, 90, '…')) ?></em><?php endif; ?></span>
                    <i class="bi bi-chevron-right"></i>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <?php if (!$posts): ?>
            <div class="empty-state card-surface"><i class="bi bi-emoji-neutral"></i><h2>No posts found</h2><p>No post mentions &ldquo;<?= e($q) ?>&rdquo; yet.</p></div>
        <?php else: ?>
            <p class="result-count"><?= count($posts) ?> <?= count($posts) === 1 ? 'post' : 'posts' ?> found</p>
            <div id="feedList">
                <?php foreach ($posts as $post) { include APP_PATH . '/views/partials/post.php'; } ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>
