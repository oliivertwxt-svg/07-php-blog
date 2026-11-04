<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
set_site_urls();

$slug = trim((string) ($_GET['slug'] ?? ''));
$categoryQuery = db()->prepare('SELECT id, name FROM categories WHERE slug = ? LIMIT 1');
$categoryQuery->execute([$slug]);
$category = $categoryQuery->fetch();
if (!$category) {
    http_response_code(404);
}
$posts = [];
if ($category) {
    $query = db()->prepare(
        "SELECT p.title, p.slug, p.body, p.published_at, a.name AS author_name
         FROM posts p JOIN admins a ON a.id = p.author_id
         WHERE p.category_id = ? AND p.status = 'published' AND (p.published_at IS NULL OR p.published_at <= NOW())
         ORDER BY p.published_at DESC, p.created_at DESC"
    );
    $query->execute([$category['id']]);
    $posts = $query->fetchAll();
}

site_header($category['name'] ?? 'Category not found');
if (!$category) {
    echo '<div class="panel"><h1>Category not found</h1><a href="' . h(url()) . '">Return to the blog</a></div>';
} else {
    echo '<h1>' . h($category['name']) . '</h1>';
    if (!$posts) {
        echo '<div class="panel empty">No published posts in this category yet.</div>';
    } else {
        echo '<div class="post-list">';
        foreach ($posts as $post) {
            echo '<article class="post-card"><div class="meta">' . h($post['author_name']) . ' · ' . h(format_date($post['published_at'] ?: date('Y-m-d'))) . '</div><h2><a href="' . h(url('post.php?slug=' . rawurlencode($post['slug']))) . '">' . h($post['title']) . '</a></h2><p>' . h(mb_strimwidth(strip_tags($post['body']), 0, 240, '…')) . '</p></article>';
        }
        echo '</div>';
    }
}
site_footer();
