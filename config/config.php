<?php
/** SOciaLINK - Application configuration */
date_default_timezone_set('Asia/Manila');

define('APP_NAME', 'SOciaLINK');
define('APP_TAGLINE', 'Stay linked to the people who matter.');
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('UPLOAD_PATH', ROOT_PATH . '/public/uploads');
define('MAX_UPLOAD_BYTES', 4 * 1024 * 1024); // 4 MB
define('POSTS_PER_PAGE', 8);

// Base URL (works in any sub-folder, e.g. /socialink/public)
$__script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
define('BASE_URL', rtrim(dirname($__script), '/'));

ini_set('display_errors', '0');
error_reporting(E_ALL);
