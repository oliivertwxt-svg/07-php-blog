<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$find = db()->prepare('SELECT * FROM posts WHERE id = ?');
$find->execute([$id ?: 0]);
$post = $find->fetch();
if (!$post) {
    http_response_code(404);
    exit('Post not found.');
}
$errors = [];
$values = ['title' => $post['title'], 'slug' => $post['slug'], 'body' => $post['body'], 'category_id' => (string) ($post['category_id'] ?? ''), 'status' => $post['status']];
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
    if ($values['body'] === '') {
        $errors[] = 'Post content is required.';
    }
    if (mb_strlen($values['slug']) > 220) {
        $errors[] = 'Slug must be 220 characters or fewer.';
    }
    if (!in_array($values['status'], ['draft', 'published'], true)) {
        $errors[] = 'Choose a valid post status.';
    }
    $categoryIds = array_map('intval', array_column($categories, 'id'));
    if ($values['category_id'] !== '' && !in_array((int) $values['category_id'], $categoryIds, true)) {
        $errors[] = 'Choose a valid category.';
    }
    $slugCheck = db()->prepare('SELECT id FROM posts WHERE slug = ? AND id != ?');
    $slugCheck->execute([$values['slug'], $id]);
    if ($slugCheck->fetch()) {
        $errors[] = 'That slug is already in use.';
    }
    $cover = $post['cover_image'];
    if (!$errors && isset($_FILES['cover_image']) && ($_FILES['cover_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        try {
            $cover = store_cover_image($_FILES['cover_image']);
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        }
    }
    if (!$errors) {
        $statement = db()->prepare(
            "UPDATE posts SET category_id = ?, title = ?, slug = ?, excerpt = ?, body = ?, status = ?,
             cover_image = ?, published_at = IF(? = 'published', COALESCE(published_at, NOW()), NULL)
             WHERE id = ?"
        );
        $statement->execute([
            $values['category_id'] !== '' ? (int) $values['category_id'] : null,
            $values['title'],
            $values['slug'],
            mb_strimwidth(strip_tags($values['body']), 0, 300, ''),
            $values['body'],
            $values['status'],
            $cover,
            $values['status'],
            $id,
        ]);
        flash('success', 'Post updated.');
        redirect('admin/posts/');
    }
}
admin_header('Edit post', 'posts');
if ($errors) {
    echo '<div class="alert"><ul>';
    foreach ($errors as $error) {
        echo '<li>' . h($error) . '</li>';
    }
    echo '</ul></div>';
}
?>
<form class="form-card" method="post" enctype="multipart/form-data" action="<?= h(url('admin/posts/edit.php?id=' . $id)) ?>">
<?= csrf_field() ?>
<div class="field"><label for="title">Title</label><input id="title" name="title" maxlength="200" required value="<?= h($values['title']) ?>"></div>
<div class="field"><label for="slug">Slug</label><input id="slug" name="slug" maxlength="220" value="<?= h($values['slug']) ?>" required></div>
<div class="field"><label for="category_id">Category</label><select id="category_id" name="category_id"><option value="">Uncategorized</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (string) $category['id'] === $values['category_id'] ? 'selected' : '' ?>><?= h($category['name']) ?></option><?php endforeach; ?></select></div>
<div class="field"><label for="body">Content</label><textarea id="body" name="body" required><?= h($values['body']) ?></textarea></div>
<?php if ($post['cover_image']): ?><p>Current cover: <a target="_blank" rel="noopener" href="<?= h(url('uploads/' . rawurlencode($post['cover_image']))) ?>">View image</a></p><?php endif; ?>
<div class="field"><label for="cover_image">Replace cover image (JPEG, PNG, GIF, WebP; max 5 MB)</label><input id="cover_image" name="cover_image" type="file" accept="image/jpeg,image/png,image/gif,image/webp"></div>
<div class="field"><label for="status">Status</label><select id="status" name="status"><option value="draft" <?= $values['status'] === 'draft' ? 'selected' : '' ?>>Draft</option><option value="published" <?= $values['status'] === 'published' ? 'selected' : '' ?>>Published</option></select></div>
<button type="submit">Save changes</button> <a class="button secondary" href="<?= h(url('admin/posts/')) ?>">Cancel</a>
</form>
<?php admin_footer(); ?>
