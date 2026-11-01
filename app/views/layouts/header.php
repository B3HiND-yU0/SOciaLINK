<?php /** @var array|null $currentUser */ /** @var string $pageTitle */ ?>
<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e(APP_NAME . ' - ' . APP_TAGLINE) ?>">
    <meta name="theme-color" content="#5b4bff">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='18' fill='%235b4bff'/%3E%3Cpath d='M26 38l12-12M23 31l-4 4a7 7 0 0010 10l4-4M41 33l4-4a7 7 0 00-10-10l-4 4' stroke='white' stroke-width='5' stroke-linecap='round' fill='none'/%3E%3C/svg%3E">
    <script>
        (function () {
            try {
                var t = localStorage.getItem('sl-theme');
                if (!t) { t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; }
                document.documentElement.setAttribute('data-bs-theme', t);
            } catch (e) {}
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/style.css')) ?>?v=1" rel="stylesheet">
    <link href="<?= e(asset('css/motion.css')) ?>?v=1" rel="stylesheet">
    <link href="<?= e(asset('css/responsive.css')) ?>?v=1" rel="stylesheet">
</head>
<body class="<?= $currentUser ? 'is-app' : 'is-guest' ?>">
<?php if ($currentUser): ?>
<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="<?= e(url('feed/index')) ?>" aria-label="<?= APP_NAME ?> home">
            <span class="brand-mark" aria-hidden="true"><i class="bi bi-link-45deg"></i></span>
            <span class="brand-word"><span class="so">SO</span><span class="cia">cia</span><span class="lk">LINK</span></span>
        </a>

        <form class="topbar-search" action="<?= e(url()) ?>" method="get" role="search">
            <input type="hidden" name="r" value="search/index">
            <i class="bi bi-search"></i>
            <input type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search people or #topics" aria-label="Search" maxlength="60" autocomplete="off">
        </form>

        <div class="topbar-actions">
            <button type="button" class="icon-btn" data-theme-toggle aria-label="Switch light or dark theme" title="Switch theme">
                <i class="bi bi-moon-stars-fill icon-dark-hide"></i>
                <i class="bi bi-sun-fill icon-light-hide"></i>
            </button>
            <a class="btn btn-signal d-none d-md-inline-flex" href="<?= e(url('feed/index')) ?>#composer"><i class="bi bi-plus-lg"></i> New post</a>
            <div class="dropdown">
                <button class="avatar-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu">
                    <?= avatar_html($currentUser['profile_image'], $currentUser['full_name'], (int)$currentUser['id'], 40, 'ring') ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end sl-menu">
                    <li class="menu-head">
                        <strong><?= e($currentUser['full_name']) ?></strong>
                        <span>@<?= e($currentUser['username']) ?></span>
                    </li>
                    <li><a class="dropdown-item" href="<?= e(url('profile/show/' . rawurlencode($currentUser['username']))) ?>"><i class="bi bi-person-circle"></i> My profile</a></li>
                    <li><a class="dropdown-item" href="<?= e(url('profile/edit')) ?>"><i class="bi bi-pencil-square"></i> Edit profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="post" action="<?= e(url('auth/logout')) ?>">
                            <?= csrf_field() ?>
                            <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right"></i> Log out</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>
<?php endif; ?>

<?php $flashes = pull_flash(); if ($flashes): ?>
<div class="toast-stack" role="status" aria-live="polite">
    <?php
    $icons = ['success' => 'check-circle-fill', 'danger' => 'exclamation-octagon-fill', 'warning' => 'exclamation-triangle-fill', 'info' => 'info-circle-fill'];
    foreach ($flashes as $f): ?>
        <div class="sl-toast sl-toast-<?= e($f['type']) ?>">
            <i class="bi bi-<?= e($icons[$f['type']] ?? 'info-circle-fill') ?>"></i>
            <span><?= e($f['message']) ?></span>
            <button type="button" class="toast-close" aria-label="Dismiss">&times;</button>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
