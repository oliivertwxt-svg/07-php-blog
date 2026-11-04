<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
set_site_urls();

$slug = isset($_GET['slug']) && is_string($_GET['slug']) ? trim($_GET['slug']) : '';
$categoryQuery = db()->prepare('SELECT id, name, slug FROM categories WHERE slug = ? LIMIT 1');
$categoryQuery->execute([$slug]);
$category = $categoryQuery->fetch();
if (!$category) {
    http_response_code(404);
}
$posts = [];
if ($category) {
    $query = db()->prepare(
        "SELECT p.title, p.slug, p.excerpt, p.body, p.cover_image, p.published_at, p.created_at,
                c.name AS category_name, c.slug AS category_slug, a.name AS author_name
         FROM posts p JOIN categories c ON c.id = p.category_id JOIN admins a ON a.id = p.author_id
         WHERE p.category_id = ? AND p.status = 'published' AND (p.published_at IS NULL OR p.published_at <= NOW())
         ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC"
    );
    $query->execute([$category['id']]);
    $posts = $query->fetchAll();
}

site_header($category['name'] ?? 'Category not found');
if (!$category) {
    echo '<div class="panel"><h1>Category not found</h1><a href="' . h(url()) . '">Return to the blog</a></div>';
} else {
    echo '<header class="listing-header"><p class="eyebrow">CATEGORY</p><h1>' . h($category['name']) . '</h1><p class="meta">Posts filed under ' . h($category['name']) . '.</p></header>';
    if (!$posts) {
        echo '<div class="panel empty">No published posts in this category yet.</div>';
    } else {
        echo '<div class="post-list">';
        foreach ($posts as $post) {
            public_post_card($post);
        }
        echo '</div>';
    }
}
site_footer();
