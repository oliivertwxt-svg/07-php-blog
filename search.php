<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
set_site_urls();

$term = trim((string) ($_GET['q'] ?? ''));
$posts = [];
if (mb_strlen($term) >= 3) {
    $query = db()->prepare(
        "SELECT p.title, p.slug, p.body, p.published_at, c.name AS category_name, a.name AS author_name,
                MATCH(p.title, p.body) AGAINST (? IN NATURAL LANGUAGE MODE) AS relevance
         FROM posts p LEFT JOIN categories c ON c.id = p.category_id JOIN admins a ON a.id = p.author_id
         WHERE p.status = 'published' AND (p.published_at IS NULL OR p.published_at <= NOW())
           AND MATCH(p.title, p.body) AGAINST (? IN NATURAL LANGUAGE MODE)
         ORDER BY relevance DESC, p.published_at DESC LIMIT 50"
    );
    $query->execute([$term, $term]);
    $posts = $query->fetchAll();
}

site_header('Search');
echo '<h1>Search posts</h1><form class="search-form" method="get" action="' . h(url('search.php')) . '"><input type="search" name="q" value="' . h($term) . '" placeholder="Search title and content" aria-label="Search"><button type="submit">Search</button></form>';
if ($term !== '' && mb_strlen($term) < 3) {
    echo '<p class="alert">Enter at least 3 characters to search.</p>';
} elseif ($term !== '') {
    echo '<p class="meta">' . count($posts) . ' result(s) for “' . h($term) . '”</p>';
    if (!$posts) {
        echo '<div class="panel empty">No matching published posts found.</div>';
    } else {
        echo '<div class="post-list">';
        foreach ($posts as $post) {
            $categoryLabel = $post['category_name'] ? '<a href="' . h(url('category.php?slug=' . rawurlencode($post['category_slug']))) . '">' . h($post['category_name']) . '</a>' : 'Uncategorized';
            echo '<article class="post-card"><div class="meta">' . $categoryLabel . ' · ' . h($post['author_name']) . ' · ' . h(format_date($post['published_at'] ?: date('Y-m-d'))) . '</div><h2><a href="' . h(url('post.php?slug=' . rawurlencode($post['slug']))) . '">' . h($post['title']) . '</a></h2><p>' . h(mb_strimwidth(strip_tags($post['body']), 0, 240, '…')) . '</p></article>';
        }
        echo '</div>';
    }
}
site_footer();
