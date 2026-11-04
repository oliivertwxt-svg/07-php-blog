<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();

$posts = db()->query(
    'SELECT p.id, p.title, p.slug, p.status, p.updated_at, c.name AS category_name, a.name AS author_name
     FROM posts p LEFT JOIN categories c ON c.id = p.category_id JOIN admins a ON a.id = p.author_id
     ORDER BY p.updated_at DESC'
)->fetchAll();
$message = flash('success');
admin_header('Posts', 'posts');
echo '<div class="toolbar"><p>Manage your blog posts.</p><a class="button" href="' . h(url('admin/posts/create.php')) . '">New post</a></div>';
if ($message) {
    echo '<p class="alert success">' . h($message) . '</p>';
}
if (!$posts) {
    echo '<div class="panel empty">No posts yet. Create your first post.</div>';
} else {
    echo '<div class="table-wrap"><table><thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead><tbody>';
    foreach ($posts as $post) {
        echo '<tr><td>' . h($post['title']) . '<div class="meta">By ' . h($post['author_name']) . '</div></td><td>' . h($post['category_name'] ?? 'Uncategorized') . '</td><td class="status">' . h(ucfirst($post['status'])) . '</td><td>' . h(format_date($post['updated_at'])) . '</td><td><div class="actions"><a href="' . h(url('admin/posts/edit.php?id=' . (int) $post['id'])) . '">Edit</a><form method="post" action="' . h(url('admin/posts/delete.php')) . '" onsubmit="return confirm(\'Delete this post?\')">' . csrf_field() . '<input type="hidden" name="id" value="' . (int) $post['id'] . '"><button class="danger" type="submit">Delete</button></form></div></td></tr>';
    }
    echo '</tbody></table></div>';
}
admin_footer();
