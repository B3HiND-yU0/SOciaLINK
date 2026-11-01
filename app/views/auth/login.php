<?php /** @var array $errors */ ?>
<main class="auth-page">
    <?php include APP_PATH . '/views/partials/auth_stage.php'; ?>
    <section class="auth-panel">
        <div class="auth-card">
            <h2>Log in</h2>
            <p class="text-muted">Pick up where you left off.</p>

            <?php if (!empty($errors['form'])): ?>
                <div class="form-alert" role="alert"><i class="bi bi-exclamation-octagon-fill"></i> <?= e($errors['form']) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('auth/attempt')) ?>" novalidate data-login-form>
                <?= csrf_field() ?>
                <div class="field">
                    <label for="username">Username</label>
                    <div class="input-icon"><i class="bi bi-at"></i>
                        <input type="text" class="form-control" id="username" name="username" value="<?= old('username') ?>" autocomplete="username" autofocus required maxlength="20">
                    </div>
                    <div class="field-error" data-error-for="username"></div>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <div class="input-icon"><i class="bi bi-lock"></i>
                        <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" required>
                        <button type="button" class="eye" data-toggle-password="#password" aria-label="Show password"><i class="bi bi-eye"></i></button>
                    </div>
                    <div class="field-error" data-error-for="password"></div>
                </div>
                <button type="submit" class="btn btn-signal btn-lg w-100"><i class="bi bi-box-arrow-in-right"></i> Log in</button>
            </form>

            <p class="auth-switch">New to <?= APP_NAME ?>? <a href="<?= e(url('auth/register')) ?>">Create an account</a></p>
            <div class="demo-hint"><i class="bi bi-lightbulb"></i> Demo accounts: <code>maria</code>, <code>juan</code>, <code>lea</code> &middot; password <code>password</code></div>
        </div>
    </section>
</main>
