<main class="page narrow text-center empty-state">
    <div class="error-code">404</div>
    <h1>Nothing here</h1>
    <p class="text-muted"><?= e($message ?? 'We could not find that page.') ?></p>
    <a class="btn btn-signal" href="<?= e(url('feed/index')) ?>">Back to home</a>
</main>
