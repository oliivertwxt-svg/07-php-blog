<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
set_site_urls();

$slug = trim((string) ($_GET['slug'] ?? ''));
$statement = db()->prepare(
    "SELECT p.title, p.body, p.cover_image, p.published_at, c.name AS category_name, c.slug AS category_slug, a.name AS author_name
     FROM posts p LEFT JOIN categories c ON c.id = p.category_id JOIN admins a ON a.id = p.author_id
     WHERE p.slug = ? AND p.status = 'published' AND (p.published_at IS NULL OR p.published_at <= NOW()) LIMIT 1"
);
$statement->execute([$slug]);
$post = $statement->fetch();
if (!$post) {
    http_response_code(404);
}

site_header($post ? $post['title'] : 'Post not found');
if (!$post) {
    echo '<div class="panel"><h1>Post not found</h1><p>This post may have been removed or is not published.</p><a href="' . h(url()) . '">Return to the blog</a></div>';
} else {
    echo '<article><div class="meta">';
    if ($post['category_name']) {
        echo '<a href="' . h(url('category.php?slug=' . rawurlencode($post['category_slug']))) . '">' . h($post['category_name']) . '</a> · ';
    }
    echo h($post['author_name']) . ' · ' . h(format_date($post['published_at'] ?: date('Y-m-d'))) . '</div><h1>' . h($post['title']) . '</h1>';
    if ($post['cover_image']) {
        echo '<img class="cover" src="' . h(url('uploads/' . rawurlencode($post['cover_image']))) . '" alt="">';
    }
    echo '<div class="post-body">' . h($post['body']) . '</div></article>';
}
site_footer();
