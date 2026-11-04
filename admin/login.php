<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';

if (current_admin() !== null) {
    redirect('admin/');
}

$error = flash('error');
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if (attempt_login($email, $password)) {
        redirect('admin/');
    }
    $error = 'The email or password was not correct.';
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Admin sign in · SWD Blog</title><link rel="stylesheet" href="<?= h(url('assets/style.css')) ?>"></head>
<body><main class="login-wrap"><a class="brand" href="<?= h(url()) ?>">SWD Blog</a><section class="form-card"><h1>Admin sign in</h1>
<?php if ($error): ?><p class="alert"><?= h($error) ?></p><?php endif; ?>
<form method="post" action="<?= h(url('admin/login.php')) ?>">
<?= csrf_field() ?>
<div class="field"><label for="email">Email</label><input id="email" name="email" type="email" autocomplete="username" maxlength="254" required value="<?= h($email) ?>"></div>
<div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
<button type="submit">Sign in</button></form></section></main></body></html>
