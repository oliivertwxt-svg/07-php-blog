<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();

$errors = [];
$values = ['title' => '', 'slug' => '', 'body' => '', 'category_id' => '', 'status' => 'draft'];
$categories = db()->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $values['title'] = trim((string) ($_POST['title'] ?? ''));
    $values['slug'] = slugify(trim((string) ($_POST['slug'] ?? '')) ?: $values['title']);
    $values['body'] = trim((string) ($_POST['body'] ?? ''));
    $values['category_id'] = (string) ($_POST['category_id'] ?? '');
    $values['status'] = (string) ($_POST['status'] ?? 'draft');
    if ($values['title'] === '' || mb_strlen($values['title']) > 200) {
        $errors[] = 'Title is required and must be 200 characters or fewer.';
    }
    if ($values['slug'] === '') {
        $errors[] = 'A valid URL slug is required.';
    }
    if (mb_strlen($values['slug']) > 220) {
        $errors[] = 'Slug must be 220 characters or fewer.';
    }
    if ($values['body'] === '') {
        $errors[] = 'Post content is required.';
    }
    if (!in_array($values['status'], ['draft', 'published'], true)) {
        $errors[] = 'Choose a valid post status.';
    }
    $categoryIds = array_map('intval', array_column($categories, 'id'));
    if ($values['category_id'] !== '' && !in_array((int) $values['category_id'], $categoryIds, true)) {
        $errors[] = 'Choose a valid category.';
    }
    $slugCheck = db()->prepare('SELECT id FROM posts WHERE slug = ?');
    $slugCheck->execute([$values['slug']]);
    if ($slugCheck->fetch()) {
        $errors[] = 'That slug is already in use.';
    }
    $cover = null;
    if (!$errors && isset($_FILES['cover_image'])) {
        try {
            $cover = store_cover_image($_FILES['cover_image']);
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        }
    }
    if (!$errors) {
        $statement = db()->prepare(
            "INSERT INTO posts (category_id, author_id, title, slug, excerpt, body, status, cover_image, published_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, IF(? = 'published', NOW(), NULL))"
        );
        $statement->execute([
            $values['category_id'] !== '' ? (int) $values['category_id'] : null,
            current_admin()['id'],
            $values['title'],
            $values['slug'],
            mb_strimwidth(strip_tags($values['body']), 0, 300, ''),
            $values['body'],
            $values['status'],
            $cover,
            $values['status'],
        ]);
        flash('success', 'Post created.');
        redirect('admin/posts/');
    }
}
admin_header('New post', 'posts');
if ($errors) {
    echo '<div class="alert"><ul>';
    foreach ($errors as $error) {
        echo '<li>' . h($error) . '</li>';
    }
    echo '</ul></div>';
}
?>
<form class="form-card" method="post" enctype="multipart/form-data" action="<?= h(url('admin/posts/create.php')) ?>">
<?= csrf_field() ?>
<div class="field"><label for="title">Title</label><input id="title" name="title" maxlength="200" required value="<?= h($values['title']) ?>"></div>
<div class="field"><label for="slug">Slug</label><input id="slug" name="slug" maxlength="220" value="<?= h($values['slug']) ?>" placeholder="generated-from-title"></div>
<div class="field"><label for="category_id">Category</label><select id="category_id" name="category_id"><option value="">Uncategorized</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (string) $category['id'] === $values['category_id'] ? 'selected' : '' ?>><?= h($category['name']) ?></option><?php endforeach; ?></select></div>
<div class="field"><label for="body">Content</label><textarea id="body" name="body" required><?= h($values['body']) ?></textarea></div>
<div class="field"><label for="cover_image">Cover image (JPEG, PNG, GIF, WebP; max 5 MB)</label><input id="cover_image" name="cover_image" type="file" accept="image/jpeg,image/png,image/gif,image/webp"></div>
<div class="field"><label for="status">Status</label><select id="status" name="status"><option value="draft" <?= $values['status'] === 'draft' ? 'selected' : '' ?>>Draft</option><option value="published" <?= $values['status'] === 'published' ? 'selected' : '' ?>>Published</option></select></div>
<button type="submit">Create post</button> <a class="button secondary" href="<?= h(url('admin/posts/')) ?>">Cancel</a>
</form>
<?php admin_footer(); ?>
