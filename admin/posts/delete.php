<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}
verify_csrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('A valid post ID is required.');
}
$find = db()->prepare('SELECT cover_image FROM posts WHERE id = ?');
$find->execute([$id]);
$post = $find->fetch();
$statement = db()->prepare('DELETE FROM posts WHERE id = ?');
$statement->execute([$id]);
if ($post) {
    delete_cover_image($post['cover_image']);
}
flash('success', $statement->rowCount() ? 'Post deleted.' : 'Post was already deleted.');
redirect('admin/posts/');
