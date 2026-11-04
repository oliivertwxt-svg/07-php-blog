<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
set_site_urls();

$pageValue = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = $pageValue === null || $pageValue === false ? 1 : max(1, $pageValue);
$perPage = 6;
$visibility = "status = 'published' AND (published_at IS NULL OR published_at <= NOW())";
$count = (int) db()->query("SELECT COUNT(*) FROM posts WHERE {$visibility}")->fetchColumn();
$statement = db()->prepare(
    "SELECT p.id, p.title, p.slug, p.excerpt, p.body, p.cover_image, p.published_at, p.created_at,
            c.name AS category_name, c.slug AS category_slug, a.name AS author_name
     FROM posts p LEFT JOIN categories c ON c.id = p.category_id JOIN admins a ON a.id = p.author_id
     WHERE p.status = 'published' AND (p.published_at IS NULL OR p.published_at <= NOW())
     ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC LIMIT :limit OFFSET :offset"
);
$statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
$statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
$statement->execute();
$posts = $statement->fetchAll();

site_header('Latest posts');
echo '<div class="home-intro"><div><p class="eyebrow">THE SWD BLOG</p><h1>Ideas worth sharing.</h1><p class="meta">Stories, tutorials, and updates from our community.</p></div><a class="button" href="' . h(url('search.php')) . '">Search posts</a></div>';
if (!$posts) {
    echo '<div class="panel empty">No published posts yet.</div>';
} else {
    echo '<div class="post-list">';
    foreach ($posts as $post) {
        public_post_card($post);
    }
    echo '</div>';
    $pages = (int) ceil($count / $perPage);
    if ($pages > 1) {
        echo '<nav class="pagination" aria-label="Pagination">';
        if ($page > 1) {
            echo '<a href="' . h(url('?page=' . ($page - 1))) . '" aria-label="Previous page">← Previous</a>';
        }
        for ($number = 1; $number <= $pages; $number++) {
            echo $number === $page ? '<span aria-current="page">' . $number . '</span>' : '<a href="' . h(url('?page=' . $number)) . '">' . $number . '</a>';
        }
        if ($page < $pages) {
            echo '<a href="' . h(url('?page=' . ($page + 1))) . '" aria-label="Next page">Next →</a>';
        }
        echo '</nav>';
    }
}
site_footer();
