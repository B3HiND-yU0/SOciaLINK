<?php
/** @var array $post  one post row incl. username, full_name, profile_image, counts, comments[] */
$me = auth_id();
$isOwner = $me === (int)$post['user_id'];
$comments = $post['comments'] ?? [];
$nComments = count($comments);
$edited = !empty($post['updated_at']) && strtotime($post['updated_at']) > strtotime($post['created_at']) + 2;
$profileUrl = url('profile/show/' . rawurlencode($post['username']));
?>
<article class="post-card" id="post-<?= (int)$post['id'] ?>">
    <header class="post-head">
        <a href="<?= e($profileUrl) ?>" class="post-avatar"><?= avatar_html($post['profile_image'], $post['full_name'], (int)$post['user_id'], 46) ?></a>
        <div class="post-who">
            <a class="post-name" href="<?= e($profileUrl) ?>"><?= e($post['full_name']) ?></a>
            <span class="post-meta">@<?= e($post['username']) ?> &middot; <time datetime="<?= e($post['created_at']) ?>" title="<?= e(date('M j, Y g:i A', strtotime($post['created_at']))) ?>"><?= e(time_ago($post['created_at'])) ?></time><?= $edited ? ' &middot; edited' : '' ?></span>
        </div>
        <?php if ($isOwner): ?>
        <div class="dropdown ms-auto">
            <button class="icon-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Post options"><i class="bi bi-three-dots"></i></button>
            <ul class="dropdown-menu dropdown-menu-end sl-menu">
                <li>
                    <button type="button" class="dropdown-item" data-post-edit
                        data-id="<?= (int)$post['id'] ?>"
                        data-content="<?= e($post['content']) ?>"
                        data-image="<?= $post['image'] ? e(upload_url($post['image'], 'posts')) : '' ?>">
                        <i class="bi bi-pencil"></i> Edit post
                    </button>
                </li>
                <li>
                    <form method="post" action="<?= e(url('post/delete/' . (int)$post['id'])) ?>" data-confirm="Delete this post and its comments? This cannot be undone.">
                        <?= csrf_field() ?>
                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash3"></i> Delete post</button>
                    </form>
                </li>
            </ul>
        </div>
        <?php endif; ?>
    </header>

    <?php if (trim($post['content']) !== ''): ?>
        <div class="post-body"><?= format_text($post['content']) ?></div>
    <?php endif; ?>
    <?php if ($post['image']): ?>
        <a class="post-image" href="<?= e(upload_url($post['image'], 'posts')) ?>" target="_blank" rel="noopener">
            <img src="<?= e(upload_url($post['image'], 'posts')) ?>" alt="Photo shared by <?= e($post['full_name']) ?>" loading="lazy">
        </a>
    <?php endif; ?>

    <div class="post-actions">
        <button type="button" class="act-btn like-btn <?= !empty($post['liked_by_me']) ? 'is-liked' : '' ?>" data-like="<?= (int)$post['id'] ?>" aria-pressed="<?= !empty($post['liked_by_me']) ? 'true' : 'false' ?>">
            <i class="bi bi-heart icon-off"></i><i class="bi bi-heart-fill icon-on"></i>
            <span class="like-count"><?= (int)$post['like_count'] ?></span>
            <span class="visually-hidden">likes</span>
        </button>
        <button type="button" class="act-btn" data-focus-comment="<?= (int)$post['id'] ?>">
            <i class="bi bi-chat-dots"></i> <span><?= $nComments ?></span><span class="visually-hidden"> comments</span>
        </button>
    </div>

    <section class="comments-wrap" aria-label="Comments">
        <?php if ($nComments > 2): ?>
            <button type="button" class="comments-toggle" data-toggle-comments>View all <?= $nComments ?> comments</button>
        <?php endif; ?>
        <?php if ($nComments): ?>
        <div class="comments <?= $nComments > 2 ? 'is-collapsed' : '' ?>">
            <?php foreach ($comments as $c): $mine = $me === (int)$c['user_id']; ?>
            <div class="comment" id="comment-<?= (int)$c['id'] ?>">
                <a href="<?= e(url('profile/show/' . rawurlencode($c['username']))) ?>"><?= avatar_html($c['profile_image'], $c['full_name'], (int)$c['user_id'], 32) ?></a>
                <div class="comment-main">
                    <div class="comment-bubble" data-comment-view>
                        <a class="comment-name" href="<?= e(url('profile/show/' . rawurlencode($c['username']))) ?>"><?= e($c['full_name']) ?></a>
                        <span class="comment-text"><?= format_text($c['content']) ?></span>
                    </div>
                    <?php if ($mine): ?>
                    <form class="comment-edit d-none" method="post" action="<?= e(url('comment/update/' . (int)$c['id'])) ?>" data-comment-form>
                        <?= csrf_field() ?>
                        <input type="text" name="content" class="form-control form-control-sm" value="<?= e($c['content']) ?>" maxlength="500" required aria-label="Edit comment">
                        <button class="btn btn-signal btn-sm" type="submit">Save</button>
                        <button class="btn btn-ghost btn-sm" type="button" data-comment-cancel>Cancel</button>
                    </form>
                    <?php endif; ?>
                    <div class="comment-meta">
                        <time datetime="<?= e($c['created_at']) ?>"><?= e(time_ago($c['created_at'])) ?></time>
                        <?php if (!empty($c['updated_at']) && strtotime($c['updated_at']) > strtotime($c['created_at']) + 2): ?><span>edited</span><?php endif; ?>
                        <?php if ($mine): ?>
                            <button type="button" class="link-btn" data-comment-edit>Edit</button>
                            <form method="post" action="<?= e(url('comment/delete/' . (int)$c['id'])) ?>" data-confirm="Delete this comment?" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="link-btn danger">Delete</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form class="comment-new" method="post" action="<?= e(url('comment/store')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
            <input type="text" name="content" class="form-control" maxlength="500" placeholder="Write a comment…" aria-label="Write a comment" required id="comment-input-<?= (int)$post['id'] ?>" autocomplete="off">
            <button class="icon-btn send-btn" type="submit" aria-label="Send comment"><i class="bi bi-send-fill"></i></button>
        </form>
    </section>
</article>
