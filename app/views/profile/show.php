<?php /** @var array $profile @var array $posts @var array $stats @var bool $isMe @var bool $isFollowing @var array $currentUser */ ?>
<main class="page">
    <section class="profile-card card-surface">
        <div class="profile-cover" style="<?= $profile['cover_image'] ? 'background-image:url(' . e(upload_url($profile['cover_image'], 'covers')) . ')' : '' ?>"></div>
        <div class="profile-body">
            <div class="profile-top">
                <?= avatar_html($profile['profile_image'], $profile['full_name'], (int)$profile['id'], 112, 'ring ring-lg') ?>
                <div class="profile-cta">
                    <?php if ($isMe): ?>
                        <a class="btn btn-outline-signal" href="<?= e(url('profile/edit')) ?>"><i class="bi bi-pencil-square"></i> Edit profile</a>
                    <?php else: ?>
                        <button type="button" class="btn <?= $isFollowing ? 'btn-ghost is-following' : 'btn-signal' ?>" data-follow="<?= (int)$profile['id'] ?>" data-follow-label="full"><?= $isFollowing ? 'Following' : 'Follow' ?></button>
                    <?php endif; ?>
                </div>
            </div>
            <h1 class="profile-name"><?= e($profile['full_name']) ?></h1>
            <div class="profile-handle">@<?= e($profile['username']) ?></div>
            <?php if (trim($profile['bio'] ?? '') !== ''): ?>
                <p class="profile-bio"><?= nl2br(e($profile['bio']), false) ?></p>
            <?php elseif ($isMe): ?>
                <p class="profile-bio text-muted">Add a short bio so people know who you are. <a href="<?= e(url('profile/edit')) ?>">Edit profile</a></p>
            <?php endif; ?>
            <div class="profile-meta"><i class="bi bi-calendar3"></i> Joined <?= e(date('F Y', strtotime($profile['created_at']))) ?></div>
            <div class="profile-stats">
                <div><b><?= (int)$stats['posts'] ?></b> Posts</div>
                <div><b data-followers><?= (int)$stats['followers'] ?></b> Followers</div>
                <div><b><?= (int)$stats['following'] ?></b> Following</div>
            </div>
        </div>
    </section>

    <h2 class="section-title">Posts</h2>
    <div id="feedList">
        <?php if (!$posts): ?>
            <div class="empty-state card-surface">
                <i class="bi bi-journal-text"></i>
                <h2><?= $isMe ? 'You have not posted yet' : 'No posts yet' ?></h2>
                <p><?= $isMe ? 'Share your first update from the home page.' : e($profile['full_name']) . ' has not shared anything yet.' ?></p>
                <?php if ($isMe): ?><a class="btn btn-signal" href="<?= e(url('feed/index')) ?>#composer"><i class="bi bi-pencil-fill"></i> Write a post</a><?php endif; ?>
            </div>
        <?php endif; ?>
        <?php foreach ($posts as $post) { include APP_PATH . '/views/partials/post.php'; } ?>
    </div>
</main>
