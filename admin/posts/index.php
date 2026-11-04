<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();

$status = $_GET['status'] ?? '';
if (!is_string($status) || !in_array($status, ['', 'draft', 'published'], true)) {
    http_response_code(400);
    exit('Invalid post status filter.');
}
$query = 'SELECT p.id, p.title, p.slug, p.status, p.updated_at, c.name AS category_name, a.name AS author_name
          FROM posts p LEFT JOIN categories c ON c.id = p.category_id JOIN admins a ON a.id = p.author_id';
if ($status !== '') {
    $statement = db()->prepare($query . ' WHERE p.status = ? ORDER BY p.updated_at DESC');
    $statement->execute([$status]);
    $posts = $statement->fetchAll();
} else {
    $posts = db()->query($query . ' ORDER BY p.updated_at DESC')->fetchAll();
}
$message = flash('success');
$error = flash('error');
admin_header('Posts', 'posts');
echo '<div class="toolbar"><p>Manage your blog posts.</p><a class="button" href="' . h(url('admin/posts/create.php')) . '">New post</a></div>';
if ($message) {
    echo '<p class="alert success">' . h($message) . '</p>';
}
if ($error) {
    echo '<p class="alert">' . h($error) . '</p>';
}
echo '<form class="filter-form" method="get" action="' . h(url('admin/posts/')) . '"><label for="status-filter">Filter by status</label><select id="status-filter" name="status"><option value=""' . ($status === '' ? ' selected' : '') . '>All statuses</option><option value="draft"' . ($status === 'draft' ? ' selected' : '') . '>Draft</option><option value="published"' . ($status === 'published' ? ' selected' : '') . '>Published</option></select><button type="submit">Filter</button></form>';
if (!$posts) {
    echo '<div class="panel empty">No posts match this filter. Create your first post or choose another status.</div>';
} else {
    echo '<div class="table-wrap"><table><thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead><tbody>';
    foreach ($posts as $post) {
        echo '<tr><td>' . h($post['title']) . '<div class="meta">By ' . h($post['author_name']) . '</div></td><td>' . h($post['category_name'] ?? 'Uncategorized') . '</td><td><span class="badge status-' . h($post['status']) . '">' . h(ucfirst($post['status'])) . '</span></td><td>' . h(format_date($post['updated_at'])) . '</td><td><div class="actions"><a href="' . h(url('admin/posts/edit.php?id=' . (int) $post['id'])) . '">Edit</a><form method="post" action="' . h(url('admin/posts/delete.php')) . '" onsubmit="return confirm(\'Delete this post?\')">' . csrf_field() . '<input type="hidden" name="id" value="' . (int) $post['id'] . '"><button class="danger" type="submit">Delete</button></form></div></td></tr>';
    }
    echo '</tbody></table></div>';
}
admin_footer();
