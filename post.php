<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
set_site_urls();

$slug = isset($_GET['slug']) && is_string($_GET['slug']) ? trim($_GET['slug']) : '';
$statement = db()->prepare(
    "SELECT p.id, p.category_id, p.title, p.slug, p.body, p.cover_image, p.published_at, p.created_at,
            c.name AS category_name, c.slug AS category_slug, a.name AS author_name
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
    preg_match_all('/[\p{L}\p{N}]+/u', strip_tags($post['body']), $words);
    $wordCount = count($words[0]);
    $readingMinutes = max(1, (int) ceil($wordCount / 200));
    echo '<article class="article"><header class="article-header"><div class="meta">';
    if ($post['category_name']) {
        echo '<a href="' . h(url('category/' . rawurlencode($post['category_slug']))) . '">' . h($post['category_name']) . '</a> · ';
    }
    echo h($post['author_name']) . ' · ' . h(format_date($post['published_at'] ?: $post['created_at'])) . ' · ' . $readingMinutes . ' min read</div><h1>' . h($post['title']) . '</h1></header>';
    if ($post['cover_image']) {
        echo '<img class="cover article-cover" src="' . h(url('uploads/' . rawurlencode($post['cover_image']))) . '" alt="">';
    }
    echo '<div class="post-body">' . post_body_html($post['body']) . '</div></article>';
    if ($post['category_id'] !== null) {
        $relatedQuery = db()->prepare(
            "SELECT p.title, p.slug, p.excerpt, p.body, p.cover_image, p.published_at, p.created_at,
                    c.name AS category_name, c.slug AS category_slug, a.name AS author_name
             FROM posts p JOIN categories c ON c.id = p.category_id JOIN admins a ON a.id = p.author_id
             WHERE p.category_id = ? AND p.id != ? AND p.status = 'published'
               AND (p.published_at IS NULL OR p.published_at <= NOW())
             ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC LIMIT 3"
        );
        $relatedQuery->execute([$post['category_id'], $post['id']]);
        $relatedPosts = $relatedQuery->fetchAll();
        if ($relatedPosts) {
            echo '<section class="related-posts"><h2>Related posts</h2><div class="post-list">';
            foreach ($relatedPosts as $relatedPost) {
                public_post_card($relatedPost);
            }
            echo '</div></section>';
        }
    }
}
site_footer();
