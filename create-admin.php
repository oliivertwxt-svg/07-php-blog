<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php create-admin.php \"Your Name\" you@example.com\n");
    exit(1);
}
$name = trim($argv[1]);
$email = strtolower(trim($argv[2]));
if ($name === '' || mb_strlen($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 160) {
    fwrite(STDERR, "Provide a name of at most 100 characters and a valid email address.\n");
    exit(1);
}

function read_hidden_password(string $prompt): string
{
    if (PHP_OS_FAMILY === 'Windows') {
        $command = 'powershell.exe -NoProfile -Command "$secure=Read-Host \'' . $prompt . '\' -AsSecureString; $ptr=[Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure); try { [Console]::Write([Runtime.InteropServices.Marshal]::PtrToStringBSTR($ptr)) } finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($ptr) }"';
        $password = shell_exec($command);
        if (!is_string($password)) {
            throw new RuntimeException('Could not securely read the password using PowerShell.');
        }
        return rtrim($password, "\r\n");
    }

    if (!function_exists('stream_isatty') || !stream_isatty(STDIN)) {
        throw new RuntimeException('Run this command from an interactive terminal so the password can be hidden.');
    }
    fwrite(STDOUT, $prompt . ': ');
    shell_exec('stty -echo');
    $password = fgets(STDIN);
    shell_exec('stty echo');
    fwrite(STDOUT, PHP_EOL);
    return rtrim((string) $password, "\r\n");
}

try {
    $password = read_hidden_password('Admin password');
    $confirmation = read_hidden_password('Confirm password');
    if ($password !== $confirmation) {
        throw new RuntimeException('The passwords did not match.');
    }
    if (strlen($password) < 12) {
        throw new RuntimeException('Use a password with at least 12 characters.');
    }
    if (strlen($password) > 72) {
        throw new RuntimeException('Use a password with no more than 72 bytes.');
    }

    $check = db()->prepare('SELECT id FROM admins WHERE email = ?');
    $check->execute([$email]);
    if ($check->fetch()) {
        throw new RuntimeException('An admin already exists with that email address.');
    }
    $insert = db()->prepare('INSERT INTO admins (name, email, password_hash) VALUES (?, ?, ?)');
    $insert->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
    fwrite(STDOUT, "Admin account created for {$email}.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
