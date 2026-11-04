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

function slugify(string $title): string
{
    $title = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title) ?: $title;
    $title = strtolower($title);
    $title = preg_replace('/[^a-z0-9]+/', '-', $title) ?? '';
    return trim($title, '-') ?: 'post';
}

function unique_post_slug(string $title, ?int $excludeId = null): string
{
    $base = slugify($title);
    $attempt = 1;

    do {
        $suffix = $attempt === 1 ? '' : '-' . $attempt;
        $candidate = rtrim(substr($base, 0, 220 - strlen($suffix)), '-') . $suffix;
        $query = 'SELECT id FROM posts WHERE slug = ?';
        $parameters = [$candidate];
        if ($excludeId !== null) {
            $query .= ' AND id != ?';
            $parameters[] = $excludeId;
        }

        $statement = db()->prepare($query);
        $statement->execute($parameters);
        $taken = $statement->fetch();
        $attempt++;
    } while ($taken);

    return $candidate;
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
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The image upload failed. Please try again.');
    }
    $temporaryPath = $file['tmp_name'] ?? null;
    if (!is_string($temporaryPath) || !is_uploaded_file($temporaryPath)) {
        throw new RuntimeException('The uploaded image is invalid.');
    }
    $size = filesize($temporaryPath);
    if ($size === false || $size > 2 * 1024 * 1024) {
        throw new RuntimeException('Cover images must be no larger than 2 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($temporaryPath);
    $imageTypes = [
        'image/jpeg' => [IMAGETYPE_JPEG, 'jpg'],
        'image/png' => [IMAGETYPE_PNG, 'png'],
        'image/webp' => [IMAGETYPE_WEBP, 'webp'],
    ];
    $imageInfo = @getimagesize($temporaryPath);
    if (
        !is_string($mime)
        || !isset($imageTypes[$mime])
        || $imageInfo === false
        || $imageInfo[2] !== $imageTypes[$mime][0]
    ) {
        throw new RuntimeException('Choose a valid JPEG, PNG, or WebP image.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $imageTypes[$mime][1];
    $destination = APP_ROOT . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($temporaryPath, $destination)) {
        throw new RuntimeException('The image could not be saved. Check the uploads folder permissions.');
    }
    return $filename;
}

function delete_cover_image(?string $filename): void
{
    if ($filename === null || $filename === '') {
        return;
    }
    if (!preg_match('/\A[a-f0-9]{32,40}\.(?:jpg|png|gif|webp)\z/', $filename)) {
        throw new RuntimeException('The stored cover image filename is invalid; it was not deleted.');
    }

    $path = APP_ROOT . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $filename;
    if (is_file($path) && !unlink($path)) {
        throw new RuntimeException('The cover image could not be deleted from the uploads folder.');
    }
}
