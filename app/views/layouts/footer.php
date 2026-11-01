<?php /** @var array|null $currentUser */ ?>
<?php if ($currentUser): ?>
<nav class="bottom-nav" aria-label="Primary">
    <a href="<?= e(url('feed/index')) ?>" class="<?= (($_GET['r'] ?? 'feed/index') === 'feed/index' || ($_GET['r'] ?? '') === '') ? 'active' : '' ?>"><i class="bi bi-house-door-fill"></i><span>Home</span></a>
    <a href="<?= e(url('search/index')) ?>" class="<?= str_starts_with($_GET['r'] ?? '', 'search') ? 'active' : '' ?>"><i class="bi bi-search"></i><span>Search</span></a>
    <a href="<?= e(url('feed/index')) ?>#composer" class="bottom-nav-new" aria-label="New post"><i class="bi bi-plus-lg"></i></a>
    <a href="<?= e(url('profile/show/' . rawurlencode($currentUser['username']))) ?>" class="<?= str_starts_with($_GET['r'] ?? '', 'profile') ? 'active' : '' ?>"><i class="bi bi-person-fill"></i><span>Me</span></a>
</nav>

<!-- Edit post modal (shared by every post card) -->
<div class="modal fade" id="editPostModal" tabindex="-1" aria-labelledby="editPostTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content sl-modal" method="post" enctype="multipart/form-data" id="editPostForm" action="#">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h2 class="modal-title" id="editPostTitle">Edit post</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <textarea class="form-control" name="content" rows="5" maxlength="1000" id="editPostContent" placeholder="What's on your mind?"></textarea>
                <div class="edit-image d-none" id="editPostImageWrap">
                    <img src="" alt="Current photo" id="editPostImage">
                    <label class="form-check"><input class="form-check-input" type="checkbox" name="remove_image" value="1" id="editPostRemove"> <span class="form-check-label">Remove this photo</span></label>
                </div>
                <label class="file-pick mt-3">
                    <i class="bi bi-image"></i> <span>Replace or add a photo</span>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" data-image-input>
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-signal"><i class="bi bi-check2"></i> Save changes</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
    window.SL = {
        csrf: <?= json_encode(csrf_token()) ?>,
        likeUrl: <?= json_encode(url('like/toggle/')) ?>,
        followUrl: <?= json_encode(url('follow/toggle/')) ?>,
        postUpdateUrl: <?= json_encode(url('post/update/')) ?>,
        moreUrl: <?= json_encode(url('feed/more')) ?>
    };
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('js/app.js')) ?>?v=1"></script>
<script src="<?= e(asset('js/motion.js')) ?>?v=1"></script>
</body>
</html>
