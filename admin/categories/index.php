<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();

$categories = db()->query(
    'SELECT c.id, c.name, c.slug, COUNT(p.id) AS post_count
     FROM categories c LEFT JOIN posts p ON p.category_id = c.id
     GROUP BY c.id ORDER BY c.name'
)->fetchAll();
$message = flash('success');
admin_header('Categories', 'categories');
echo '<div class="toolbar"><p>Organize posts with categories.</p><a class="button" href="' . h(url('admin/categories/create.php')) . '">New category</a></div>';
if ($message) {
    echo '<p class="alert success">' . h($message) . '</p>';
}
if (!$categories) {
    echo '<div class="panel empty">No categories yet. Add one to organize your posts.</div>';
} else {
    echo '<div class="table-wrap"><table><thead><tr><th>Name</th><th>Slug</th><th>Posts</th><th>Actions</th></tr></thead><tbody>';
    foreach ($categories as $category) {
        echo '<tr><td>' . h($category['name']) . '</td><td>' . h($category['slug']) . '</td><td>' . (int) $category['post_count'] . '</td><td><div class="actions"><a href="' . h(url('admin/categories/edit.php?id=' . (int) $category['id'])) . '">Edit</a><form method="post" action="' . h(url('admin/categories/delete.php')) . '" onsubmit="return confirm(\'Delete this category? Posts will become uncategorized.\')">' . csrf_field() . '<input type="hidden" name="id" value="' . (int) $category['id'] . '"><button class="danger" type="submit">Delete</button></form></div></td></tr>';
    }
    echo '</tbody></table></div>';
}
admin_footer();
