<?php /** @var array $errors @var array $currentUser */ ?>
<?php $err = fn(string $k) => $errors[$k] ?? ''; ?>
<main class="page narrow">
    <h1 class="page-title">Edit profile</h1>
    <form class="card-surface edit-profile" method="post" action="<?= e(url('profile/update')) ?>" enctype="multipart/form-data" novalidate data-profile-form>
        <?= csrf_field() ?>

        <div class="edit-cover" id="coverPreview" style="<?= $currentUser['cover_image'] ? 'background-image:url(' . e(upload_url($currentUser['cover_image'], 'covers')) . ')' : '' ?>">
            <label class="file-pick floating">
                <i class="bi bi-image"></i> <span>Change cover</span>
                <input type="file" name="cover" accept="image/jpeg,image/png,image/gif,image/webp" data-image-input data-preview-bg="#coverPreview">
            </label>
        </div>
        <div class="field-error px-3" data-error-for="cover"><?= e($err('cover')) ?></div>

        <div class="edit-avatar-row">
            <div class="avatar-edit">
                <img src="<?= $currentUser['profile_image'] ? e(upload_url($currentUser['profile_image'], 'avatars')) : '' ?>" alt="" id="avatarPreview" class="<?= $currentUser['profile_image'] ? '' : 'd-none' ?> avatar ring" style="width:96px;height:96px">
                <span id="avatarFallback" class="<?= $currentUser['profile_image'] ? 'd-none' : '' ?>"><?= avatar_html(null, $currentUser['full_name'], (int)$currentUser['id'], 96, 'ring') ?></span>
            </div>
            <div>
                <label class="file-pick">
                    <i class="bi bi-camera"></i> <span>Change photo</span>
                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" data-image-input data-preview-img="#avatarPreview" data-preview-hide="#avatarFallback">
                </label>
                <div class="field-error" data-error-for="avatar"><?= e($err('avatar')) ?></div>
            </div>
        </div>

        <div class="p-3 pt-0">
            <div class="field">
                <label for="full_name">Full name</label>
                <input type="text" class="form-control <?= $err('full_name') ? 'is-invalid' : '' ?>" id="full_name" name="full_name" value="<?= old('full_name', $currentUser['full_name']) ?>" maxlength="60" required>
                <div class="field-error" data-error-for="full_name"><?= e($err('full_name')) ?></div>
            </div>
            <div class="field">
                <label for="username_ro">Username</label>
                <input type="text" class="form-control" id="username_ro" value="@<?= e($currentUser['username']) ?>" disabled>
                <div class="field-hint">Usernames cannot be changed.</div>
            </div>
            <div class="field">
                <label for="bio">Bio</label>
                <textarea class="form-control <?= $err('bio') ? 'is-invalid' : '' ?>" id="bio" name="bio" rows="3" maxlength="200" data-counter="#bioCount"><?= old('bio', $currentUser['bio'] ?? '') ?></textarea>
                <div class="d-flex justify-content-between"><div class="field-error" data-error-for="bio"><?= e($err('bio')) ?></div><span class="char-count" id="bioCount">0/200</span></div>
            </div>

            <?php if ($currentUser['profile_image'] || $currentUser['cover_image']): ?>
            <div class="remove-opts">
                <?php if ($currentUser['profile_image']): ?><label class="form-check"><input class="form-check-input" type="checkbox" name="remove_avatar" value="1"> <span class="form-check-label">Remove my profile photo</span></label><?php endif; ?>
                <?php if ($currentUser['cover_image']): ?><label class="form-check"><input class="form-check-input" type="checkbox" name="remove_cover" value="1"> <span class="form-check-label">Remove my cover photo</span></label><?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="d-flex gap-2 justify-content-end mt-3">
                <a class="btn btn-ghost" href="<?= e(url('profile/show/' . rawurlencode($currentUser['username']))) ?>">Cancel</a>
                <button type="submit" class="btn btn-signal"><i class="bi bi-check-lg"></i> Save profile</button>
            </div>
        </div>
    </form>
</main>
