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
    exit('A valid category ID is required.');
}
$statement = db()->prepare('DELETE FROM categories WHERE id = ?');
$statement->execute([$id]);
flash('success', $statement->rowCount() ? 'Category deleted; associated posts are now uncategorized.' : 'Category was already deleted.');
redirect('admin/categories/');
