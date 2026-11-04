<?php
declare(strict_types=1);

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return APP_BASE_URL . ($path === '' ? '/' : '/' . ltrim($path, '/'));
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return is_string($value) ? $value : null;
}

function slugify(string $text): string
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-') ?: 'post';
}

function site_header(string $title): void
{
    $pageTitle = h($title);
    echo <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$pageTitle} · SWD Blog</title>
  <link rel="stylesheet" href="{$GLOBALS['styleUrl']}">
</head>
<body>
<header class="site-header"><a class="brand" href="{$GLOBALS['homeUrl']}">SWD Blog</a><nav><a href="{$GLOBALS['homeUrl']}">Home</a><a href="{$GLOBALS['searchUrl']}">Search</a><a href="{$GLOBALS['adminUrl']}">Admin</a></nav></header>
<main class="container">
HTML;
}

function site_footer(): void
{
    echo '</main><footer class="site-footer">SWD Blog</footer></body></html>';
}

function admin_header(string $title, string $active): void
{
    $pageTitle = h($title);
    $links = [
        'Dashboard' => ['admin/', 'dashboard'],
        'Posts' => ['admin/posts/', 'posts'],
        'Categories' => ['admin/categories/', 'categories'],
        'View site' => ['', ''],
    ];
    $nav = '';
    foreach ($links as $label => [$path, $key]) {
        $class = $active === $key ? ' class="active"' : '';
        $nav .= '<a' . $class . ' href="' . h(url($path)) . '">' . h($label) . '</a>';
    }
    $logoutUrl = h(url('admin/logout.php'));
    $csrf = h(csrf_token());
    $style = h(url('assets/style.css'));
    echo <<<HTML
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{$pageTitle} · SWD Blog Admin</title><link rel="stylesheet" href="{$style}"></head>
<body class="admin-body">
<aside class="sidebar"><a class="brand" href="{$GLOBALS['adminHomeUrl']}">SWD Blog</a><span class="sidebar-label">ADMIN</span><nav>{$nav}</nav><form method="post" action="{$logoutUrl}" class="logout-form"><input type="hidden" name="csrf_token" value="{$csrf}"><button type="submit">Logout</button></form></aside>
<main class="admin-main"><header class="admin-top"><h1>{$pageTitle}</h1></header><section class="admin-content">
HTML;
}

function admin_footer(): void
{
    echo '</section></main></body></html>';
}

function set_site_urls(): void
{
    $GLOBALS['styleUrl'] = h(url('assets/style.css'));
    $GLOBALS['homeUrl'] = h(url());
    $GLOBALS['searchUrl'] = h(url('search.php'));
    $GLOBALS['adminUrl'] = h(url('admin/'));
    $GLOBALS['adminHomeUrl'] = h(url('admin/'));
}

function format_date(string $date): string
{
    return date('M j, Y', strtotime($date));
}

function store_cover_image(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The image upload failed. Please try again.');
    }
    if (!isset($file['tmp_name'], $file['size']) || (int) $file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Cover images must be no larger than 5 MB.');
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    if (!$imageInfo || !isset($extensions[$mime])) {
        throw new RuntimeException('Choose a valid JPEG, PNG, GIF, or WebP image.');
    }

    $filename = bin2hex(random_bytes(20)) . '.' . $extensions[$mime];
    $destination = APP_ROOT . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('The image could not be saved. Check the uploads folder permissions.');
    }
    return $filename;
}
