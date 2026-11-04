<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$counts = db()->query(
    "SELECT
        (SELECT COUNT(*) FROM posts) AS posts,
        (SELECT COUNT(*) FROM posts WHERE status = 'published') AS published,
        (SELECT COUNT(*) FROM posts WHERE status = 'draft') AS drafts,
        (SELECT COUNT(*) FROM categories) AS categories"
)->fetch();
$latestPosts = db()->query(
    'SELECT p.id, p.title, p.status, p.created_at, c.name AS category_name
     FROM posts p LEFT JOIN categories c ON c.id = p.category_id
     ORDER BY p.created_at DESC, p.id DESC LIMIT 5'
)->fetchAll();
$categoryCounts = db()->query(
    'SELECT c.id, c.name, COUNT(p.id) AS post_count
     FROM categories c LEFT JOIN posts p ON p.category_id = c.id
     GROUP BY c.id, c.name ORDER BY c.name'
)->fetchAll();
admin_header('Dashboard', 'dashboard');
echo '<p>Welcome, ' . h(current_admin()['name']) . '.</p><div class="stats">';
foreach (['Posts' => 'posts', 'Published' => 'published', 'Drafts' => 'drafts', 'Categories' => 'categories'] as $label => $key) {
    echo '<article class="stat-card"><span>' . h($label) . '</span><strong>' . (int) $counts[$key] . '</strong></article>';
}
echo '</div><div class="dashboard-grid"><section class="panel"><h2>Latest posts</h2>';
if (!$latestPosts) {
    echo '<p class="empty">No posts yet.</p>';
} else {
    echo '<div class="table-wrap"><table><thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Created</th></tr></thead><tbody>';
    foreach ($latestPosts as $post) {
        echo '<tr><td><a href="' . h(url('admin/posts/edit.php?id=' . (int) $post['id'])) . '">' . h($post['title']) . '</a></td><td>' . h($post['category_name'] ?? 'Uncategorized') . '</td><td><span class="badge status-' . h($post['status']) . '">' . h(ucfirst($post['status'])) . '</span></td><td>' . h(format_date($post['created_at'])) . '</td></tr>';
    }
    echo '</tbody></table></div>';
}
echo '</section><section class="panel"><h2>Posts per category</h2>';
if (!$categoryCounts) {
    echo '<p class="empty">No categories yet.</p>';
} else {
    echo '<ul class="category-counts">';
    foreach ($categoryCounts as $category) {
        echo '<li><a href="' . h(url('admin/categories/edit.php?id=' . (int) $category['id'])) . '">' . h($category['name']) . '</a><strong>' . (int) $category['post_count'] . '</strong></li>';
    }
    echo '</ul>';
}
echo '</section></div><div class="panel quick-actions"><h2>Quick actions</h2><p><a class="button" href="' . h(url('admin/posts/create.php')) . '">Write a post</a> <a class="button secondary" href="' . h(url('admin/categories/create.php')) . '">Add a category</a></p></div>';
admin_footer();
