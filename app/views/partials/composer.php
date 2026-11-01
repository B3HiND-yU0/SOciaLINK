<?php /** @var array $currentUser */ ?>
<form class="composer" id="composer" method="post" action="<?= e(url('post/store')) ?>" enctype="multipart/form-data" data-composer>
    <?= csrf_field() ?>
    <div class="composer-row">
        <?= avatar_html($currentUser['profile_image'], $currentUser['full_name'], (int)$currentUser['id'], 46) ?>
        <textarea name="content" rows="2" maxlength="1000" placeholder="What's happening, <?= e(explode(' ', $currentUser['full_name'])[0]) ?>?" aria-label="Write a post" data-autogrow data-counter="#composerCount"></textarea>
    </div>
    <div class="composer-preview d-none" data-preview-box>
        <img src="" alt="Selected photo preview">
        <button type="button" class="preview-remove" data-preview-clear aria-label="Remove photo"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="composer-foot">
        <label class="act-btn file-btn" title="Add a photo">
            <i class="bi bi-image"></i> <span>Photo</span>
            <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" data-image-input data-preview-target="[data-preview-box]">
        </label>
        <span class="composer-hint" id="composerError" role="alert"></span>
        <span class="char-count" id="composerCount">0/1000</span>
        <button type="submit" class="btn btn-signal" data-composer-submit disabled><i class="bi bi-send-fill"></i> Post</button>
    </div>
</form>
