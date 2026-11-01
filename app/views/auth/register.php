<?php /** @var array $errors */ ?>
<?php $err = fn(string $k) => $errors[$k] ?? ''; ?>
<main class="auth-page">
    <?php include APP_PATH . '/views/partials/auth_stage.php'; ?>
    <section class="auth-panel">
        <div class="auth-card">
            <h2>Create your account</h2>
            <p class="text-muted">It takes less than a minute.</p>

            <form method="post" action="<?= e(url('auth/store')) ?>" enctype="multipart/form-data" novalidate data-register-form>
                <?= csrf_field() ?>

                <div class="avatar-pick">
                    <label class="avatar-drop" title="Add a profile picture">
                        <img src="" alt="" id="avatarPreview" class="d-none">
                        <i class="bi bi-camera" id="avatarIcon"></i>
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" data-image-input data-preview-img="#avatarPreview" data-preview-hide="#avatarIcon">
                    </label>
                    <div>
                        <strong>Profile picture</strong>
                        <small class="d-block text-muted">Optional. JPG, PNG, GIF or WEBP up to 4 MB.</small>
                        <div class="field-error" data-error-for="avatar"><?= e($err('avatar')) ?></div>
                    </div>
                </div>

                <div class="field">
                    <label for="full_name">Full name</label>
                    <input type="text" class="form-control <?= $err('full_name') ? 'is-invalid' : '' ?>" id="full_name" name="full_name" value="<?= old('full_name') ?>" maxlength="60" autocomplete="name" required>
                    <div class="field-error" data-error-for="full_name"><?= e($err('full_name')) ?></div>
                </div>

                <div class="field">
                    <label for="username">Username</label>
                    <div class="input-icon"><i class="bi bi-at"></i>
                        <input type="text" class="form-control <?= $err('username') ? 'is-invalid' : '' ?>" id="username" name="username" value="<?= old('username') ?>" maxlength="20" autocomplete="username" required>
                    </div>
                    <div class="field-hint">3–20 letters, numbers or underscores.</div>
                    <div class="field-error" data-error-for="username"><?= e($err('username')) ?></div>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="input-icon"><i class="bi bi-lock"></i>
                        <input type="password" class="form-control <?= $err('password') ? 'is-invalid' : '' ?>" id="password" name="password" autocomplete="new-password" required>
                        <button type="button" class="eye" data-toggle-password="#password" aria-label="Show password"><i class="bi bi-eye"></i></button>
                    </div>
                    <div class="strength" data-strength><span></span><span></span><span></span><span></span></div>
                    <div class="field-hint" data-strength-label>At least 8 characters with a letter and a number.</div>
                    <div class="field-error" data-error-for="password"><?= e($err('password')) ?></div>
                </div>

                <div class="field">
                    <label for="password_confirm">Confirm password</label>
                    <div class="input-icon"><i class="bi bi-shield-lock"></i>
                        <input type="password" class="form-control <?= $err('password_confirm') ? 'is-invalid' : '' ?>" id="password_confirm" name="password_confirm" autocomplete="new-password" required>
                    </div>
                    <div class="field-error" data-error-for="password_confirm"><?= e($err('password_confirm')) ?></div>
                </div>

                <button type="submit" class="btn btn-signal btn-lg w-100"><i class="bi bi-person-plus-fill"></i> Create account</button>
            </form>
            <p class="auth-switch">Already have an account? <a href="<?= e(url('auth/login')) ?>">Log in</a></p>
        </div>
    </section>
</main>
