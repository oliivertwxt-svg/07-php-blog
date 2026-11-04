<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();

$errors = [];
$name = '';
$slug = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $slug = slugify(trim((string) ($_POST['slug'] ?? '')) ?: $name);
    if ($name === '' || mb_strlen($name) > 80) {
        $errors[] = 'Name is required and must be 80 characters or fewer.';
    }
    if (mb_strlen($slug) > 90) {
        $errors[] = 'Slug must be 90 characters or fewer.';
    }
    $check = db()->prepare('SELECT id FROM categories WHERE slug = ?');
    $check->execute([$slug]);
    if ($check->fetch()) {
        $errors[] = 'That slug is already in use.';
    }
    if (!$errors) {
        $statement = db()->prepare('INSERT INTO categories (name, slug) VALUES (?, ?)');
        $statement->execute([$name, $slug]);
        flash('success', 'Category created.');
        redirect('admin/categories/');
    }
}
admin_header('New category', 'categories');
if ($errors) {
    echo '<div class="alert"><ul>';
    foreach ($errors as $error) {
        echo '<li>' . h($error) . '</li>';
    }
    echo '</ul></div>';
}
?>
<form class="form-card" method="post" action="<?= h(url('admin/categories/create.php')) ?>">
<?= csrf_field() ?>
<div class="field"><label for="name">Name</label><input id="name" name="name" maxlength="80" required value="<?= h($name) ?>"></div>
<div class="field"><label for="slug">Slug</label><input id="slug" name="slug" maxlength="90" value="<?= h($slug) ?>" placeholder="generated-from-name"></div>
<button type="submit">Create category</button> <a class="button secondary" href="<?= h(url('admin/categories/')) ?>">Cancel</a>
</form>
<?php admin_footer(); ?>
