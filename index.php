<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
set_site_urls();

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 8;
$count = (int) db()->query("SELECT COUNT(*) FROM posts WHERE status = 'published' AND (published_at IS NULL OR published_at <= NOW())")->fetchColumn();
$statement = db()->prepare(
    "SELECT p.id, p.title, p.slug, p.body, p.cover_image, p.published_at, c.name AS category_name, c.slug AS category_slug, a.name AS author_name
     FROM posts p LEFT JOIN categories c ON c.id = p.category_id JOIN admins a ON a.id = p.author_id
     WHERE p.status = 'published' AND (p.published_at IS NULL OR p.published_at <= NOW())
     ORDER BY p.published_at DESC, p.created_at DESC LIMIT :limit OFFSET :offset"
);
$statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
$statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
$statement->execute();
$posts = $statement->fetchAll();

site_header('Latest posts');
echo '<div class="toolbar"><div><h1>Latest posts</h1><p class="meta">Ideas, stories, and updates.</p></div><a class="button" href="' . h(url('search.php')) . '">Search posts</a></div>';
if (!$posts) {
    echo '<div class="panel empty">No published posts yet.</div>';
} else {
    echo '<div class="post-list">';
    foreach ($posts as $post) {
        echo '<article class="post-card">';
        if ($post['cover_image']) {
            echo '<a href="' . h(url('post.php?slug=' . rawurlencode($post['slug']))) . '"><img class="cover" src="' . h(url('uploads/' . rawurlencode($post['cover_image']))) . '" alt=""></a>';
        }
        $categoryLabel = $post['category_name'] ? '<a href="' . h(url('category.php?slug=' . rawurlencode($post['category_slug']))) . '">' . h($post['category_name']) . '</a>' : 'Uncategorized';
        echo '<div class="meta">' . $categoryLabel . ' · ' . h($post['author_name']) . ' · ' . h(format_date($post['published_at'] ?: date('Y-m-d'))) . '</div>';
        echo '<h2><a href="' . h(url('post.php?slug=' . rawurlencode($post['slug']))) . '">' . h($post['title']) . '</a></h2>';
        echo '<p>' . h(mb_strimwidth(strip_tags($post['body']), 0, 240, '…')) . '</p></article>';
    }
    echo '</div>';
    $pages = (int) ceil($count / $perPage);
    if ($pages > 1) {
        echo '<nav class="pagination" aria-label="Pagination">';
        for ($number = 1; $number <= $pages; $number++) {
            echo $number === $page ? '<span>' . $number . '</span>' : '<a href="' . h(url('?page=' . $number)) . '">' . $number . '</a>';
        }
        echo '</nav>';
    }
}
site_footer();
