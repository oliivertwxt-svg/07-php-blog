<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$counts = [
    'Posts' => (int) db()->query('SELECT COUNT(*) FROM posts')->fetchColumn(),
    'Published' => (int) db()->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn(),
    'Drafts' => (int) db()->query("SELECT COUNT(*) FROM posts WHERE status = 'draft'")->fetchColumn(),
    'Categories' => (int) db()->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
];
admin_header('Dashboard', 'dashboard');
echo '<p>Welcome, ' . h(current_admin()['name']) . '.</p><div class="stats">';
foreach ($counts as $label => $count) {
    echo '<article class="stat-card"><span>' . h($label) . '</span><strong>' . $count . '</strong></article>';
}
echo '</div><div class="panel" style="margin-top:22px"><h2>Quick actions</h2><p><a class="button" href="' . h(url('admin/posts/create.php')) . '">Write a post</a> <a class="button secondary" href="' . h(url('admin/categories/create.php')) . '">Add a category</a></p></div>';
admin_footer();
