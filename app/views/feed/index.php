<?php /** @var array $posts @var bool $hasMore @var array $suggestions @var array $stats @var array $currentUser */ ?>
<div class="shell">
    <aside class="side-left">
        <div class="side-sticky">
            <div class="mini-profile">
                <div class="mini-cover" style="<?= $currentUser['cover_image'] ? 'background-image:url(' . e(upload_url($currentUser['cover_image'], 'covers')) . ')' : '' ?>"></div>
                <a class="mini-avatar" href="<?= e(url('profile/show/' . rawurlencode($currentUser['username']))) ?>"><?= avatar_html($currentUser['profile_image'], $currentUser['full_name'], (int)$currentUser['id'], 72, 'ring') ?></a>
                <div class="mini-body">
                    <strong><?= e($currentUser['full_name']) ?></strong>
                    <span>@<?= e($currentUser['username']) ?></span>
                    <?php if (trim($currentUser['bio'] ?? '') !== ''): ?><p><?= e($currentUser['bio']) ?></p><?php endif; ?>
                </div>
                <div class="mini-stats">
                    <div><b><?= (int)$stats['posts'] ?></b><span>Posts</span></div>
                    <div><b><?= (int)$stats['followers'] ?></b><span>Followers</span></div>
                    <div><b><?= (int)$stats['following'] ?></b><span>Following</span></div>
                </div>
            </div>
            <nav class="side-nav" aria-label="Shortcuts">
                <a class="active" href="<?= e(url('feed/index')) ?>"><i class="bi bi-house-door-fill"></i> Home</a>
                <a href="<?= e(url('profile/show/' . rawurlencode($currentUser['username']))) ?>"><i class="bi bi-person-fill"></i> My profile</a>
                <a href="<?= e(url('search/index')) ?>"><i class="bi bi-search"></i> Find people</a>
                <a href="<?= e(url('profile/edit')) ?>"><i class="bi bi-gear-fill"></i> Edit profile</a>
            </nav>
        </div>
    </aside>

    <section class="feed" aria-label="Newsfeed">
        <?php include APP_PATH . '/views/partials/composer.php'; ?>

        <div class="suggest-strip d-xl-none">
            <h2 class="side-title">People to follow</h2>
            <?php include APP_PATH . '/views/partials/suggestions.php'; ?>
        </div>

        <div id="feedList" data-next-page="2">
            <?php if (!$posts): ?>
                <div class="empty-state card-surface">
                    <i class="bi bi-chat-square-heart"></i>
                    <h2>Your feed is waiting</h2>
                    <p>Nobody has posted yet. Share the first update above.</p>
                </div>
            <?php endif; ?>
            <?php foreach ($posts as $post) { include APP_PATH . '/views/partials/post.php'; } ?>
        </div>

        <?php if ($hasMore): ?>
            <div class="load-more-wrap"><button type="button" class="btn btn-outline-signal" id="loadMore"><i class="bi bi-arrow-clockwise"></i> Load more posts</button></div>
        <?php endif; ?>
    </section>

    <aside class="side-right d-none d-xl-block">
        <div class="side-sticky">
            <div class="card-surface">
                <h2 class="side-title">People to follow</h2>
                <?php include APP_PATH . '/views/partials/suggestions.php'; ?>
            </div>
            <p class="side-foot"><?= APP_NAME ?> &middot; <?= e(APP_TAGLINE) ?></p>
        </div>
    </aside>
</div>
