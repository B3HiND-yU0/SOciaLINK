<?php
/** Global helper functions used by controllers and views. */

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $route = '', array $params = []): string
{
    $q = $route !== '' ? ['r' => $route] + $params : $params;
    return BASE_URL . '/index.php' . ($q ? '?' . http_build_query($q) : '');
}

function asset(string $path): string
{
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

function upload_url(?string $file, string $folder): string
{
    return BASE_URL . '/uploads/' . $folder . '/' . rawurlencode((string)$file);
}

function redirect(string $location): void
{
    header('Location: ' . $location);
    exit;
}

/** Redirect to the previous local page (never an external site). */
function redirect_back(string $fallback, string $fragment = ''): void
{
    $target = $fallback;
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    if ($ref !== '') {
        $p = parse_url($ref);
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $refHost = ($p['host'] ?? '') . (isset($p['port']) ? ':' . $p['port'] : '');
        if (!empty($p['host']) && ($refHost === $host || $p['host'] === $host)) {
            $target = ($p['path'] ?? '/') . (isset($p['query']) ? '?' . $p['query'] : '');
        }
    }
    redirect($target . ($fragment !== '' ? '#' . $fragment : ''));
}

/* ---------- session / auth ---------- */
function auth_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function is_logged_in(): bool
{
    return auth_id() !== null;
}

/* ---------- flash + old input ---------- */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flash(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function old(string $key, string $default = ''): string
{
    return e($_SESSION['old'][$key] ?? $default);
}

function pull_errors(): array
{
    $e = $_SESSION['errors'] ?? [];
    unset($_SESSION['errors']);
    return $e;
}

function clear_old(): void
{
    unset($_SESSION['old']);
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    $sent = $_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($sent) && $sent !== '' && hash_equals(csrf_token(), $sent);
}

/* ---------- text + time ---------- */
function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 45)      return 'just now';
    if ($diff < 3600)    return max(1, (int)floor($diff / 60)) . 'm ago';
    if ($diff < 86400)   return (int)floor($diff / 3600) . 'h ago';
    if ($diff < 604800)  return (int)floor($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}

/** Escape first, then turn line breaks, links and #hashtags into markup. */
function format_text(string $text): string
{
    $safe = e($text);
    $safe = preg_replace_callback('~(https?://[^\s<]+)~i', function ($m) {
        return '<a href="' . $m[1] . '" target="_blank" rel="noopener noreferrer nofollow">' . $m[1] . '</a>';
    }, $safe);
    $safe = preg_replace_callback('/(?<![\w&#\/=\?])#([\p{L}0-9_]{2,30})(?![^<]*>)/u', function ($m) {
        return '<a class="hashtag" href="' . e(url('search/index', ['q' => '#' . $m[1], 'type' => 'posts'])) . '">#' . $m[1] . '</a>';
    }, $safe);
    return nl2br($safe, false);
}

/** Avatar: uploaded picture or a coloured initials badge. */
function avatar_html(?string $image, string $name, int $seed, int $size = 44, string $extra = ''): string
{
    $style = 'width:' . $size . 'px;height:' . $size . 'px;';
    if ($image) {
        return '<img class="avatar ' . e($extra) . '" style="' . $style . '" src="' . e(upload_url($image, 'avatars')) . '" alt="' . e($name) . '" loading="lazy">';
    }
    $parts = preg_split('/\s+/', trim($name));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $initials .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    $h1 = ($seed * 47) % 360;
    $h2 = ($h1 + 55) % 360;
    $style .= 'font-size:' . round($size * 0.38) . 'px;background:linear-gradient(135deg,hsl(' . $h1 . ',78%,58%),hsl(' . $h2 . ',82%,52%));';
    return '<span class="avatar avatar-initials ' . e($extra) . '" style="' . $style . '" aria-label="' . e($name) . '">' . e($initials !== '' ? $initials : '?') . '</span>';
}

/**
 * Validate + store an uploaded image. Returns [filename|null, error|null].
 * No file chosen => [null, null].
 */
function save_image(string $field, string $folder): array
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    $f = $_FILES[$field];
    if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
        return [null, 'Image is too large for this server. Please choose a smaller photo (under 2 MB).'];
    }
    if ($f['error'] !== UPLOAD_ERR_OK) {
        return [null, 'The image could not be uploaded. Please try again.'];
    }
    if ($f['size'] > MAX_UPLOAD_BYTES) {
        return [null, 'Image is too large. Maximum size is 4 MB.'];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($f['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime]) || @getimagesize($f['tmp_name']) === false) {
        return [null, 'Only JPG, PNG, GIF or WEBP images are allowed.'];
    }
    $dir = UPLOAD_PATH . '/' . $folder;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
        return [null, 'Could not save the image on the server.'];
    }
    return [$name, null];
}

function delete_image(?string $file, string $folder): void
{
    if ($file && preg_match('/^[a-f0-9]{24}\.(jpg|png|gif|webp)$/', $file)) {
        $path = UPLOAD_PATH . '/' . $folder . '/' . $file;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

function render_partial(string $name, array $vars = []): string
{
    extract($vars, EXTR_SKIP);
    ob_start();
    include APP_PATH . '/views/partials/' . $name . '.php';
    return ob_get_clean();
}
