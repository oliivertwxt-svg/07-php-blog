# SWD Blog CMS

PHP 8 and MySQL blog CMS for `http://localhost/swd/07-php-blog/`.

## Setup

1. Start Apache and MySQL in XAMPP.
2. Import `database/schema.sql` into MySQL (for example, with phpMyAdmin).
3. If your local MySQL credentials differ from the XAMPP defaults, set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` in the Apache/PHP environment. Defaults are `127.0.0.1:3306`, database `swd_blog`, user `root`, and an empty password.
4. Ensure the `uploads` directory is writable by Apache.
5. From a terminal, create an admin account:

   ```powershell
   C:\xampp\php\php.exe create-admin.php "Your Name" you@example.com
   ```

   Enter and confirm a password of at least 12 characters when prompted. On Windows, PowerShell hides password input.
6. Visit `http://localhost/swd/07-php-blog/` and sign in at `/admin/login.php`.

Set `APP_BASE_URL` if the project is mounted at a different URL path. The app expects MySQL with InnoDB and FULLTEXT support.
