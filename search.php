<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
set_site_urls();

$term = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$posts = [];
if ($term !== '') {
    if (mb_strlen($term) < 3) {
        $likeTerm = '%' . addcslashes($term, '\\%_') . '%';
        $query = db()->prepare(
            "SELECT p.title, p.slug, p.excerpt, p.body, p.cover_image, p.published_at, p.created_at,
                    c.name AS category_name, c.slug AS category_slug, a.name AS author_name
             FROM posts p LEFT JOIN categories c ON c.id = p.category_id JOIN admins a ON a.id = p.author_id
             WHERE p.status = 'published' AND (p.published_at IS NULL OR p.published_at <= NOW())
               AND (p.title LIKE ? ESCAPE '\\\\' OR p.body LIKE ? ESCAPE '\\\\')
             ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC LIMIT 50"
        );
        $query->execute([$likeTerm, $likeTerm]);
        $posts = $query->fetchAll();
    } else {
        $query = db()->prepare(
            "SELECT p.title, p.slug, p.excerpt, p.body, p.cover_image, p.published_at, p.created_at,
                    c.name AS category_name, c.slug AS category_slug, a.name AS author_name,
                    MATCH(p.title, p.body) AGAINST (? IN NATURAL LANGUAGE MODE) AS relevance
             FROM posts p LEFT JOIN categories c ON c.id = p.category_id JOIN admins a ON a.id = p.author_id
             WHERE p.status = 'published' AND (p.published_at IS NULL OR p.published_at <= NOW())
               AND MATCH(p.title, p.body) AGAINST (? IN NATURAL LANGUAGE MODE)
             ORDER BY relevance DESC, COALESCE(p.published_at, p.created_at) DESC, p.id DESC LIMIT 50"
        );
        $query->execute([$term, $term]);
        $posts = $query->fetchAll();
    }
}

site_header('Search');
echo '<h1>Search posts</h1><form class="search-form" method="get" action="' . h(url('search.php')) . '"><input type="search" name="q" value="' . h($term) . '" placeholder="Search title and content" aria-label="Search"><button type="submit">Search</button></form>';
if ($term !== '') {
    echo '<p class="meta">' . count($posts) . ' result(s) for “' . h($term) . '”</p>';
    if (!$posts) {
        echo '<div class="panel empty">No matching published posts found.</div>';
    } else {
        echo '<div class="post-list">';
        foreach ($posts as $post) {
            public_post_card($post);
        }
        echo '</div>';
    }
}
site_footer();
