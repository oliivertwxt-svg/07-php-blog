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

## Admin content and cover uploads

The admin area supports category and post CRUD. Post slugs are generated from titles and made unique with numeric suffixes. Cover uploads are limited to 2 MB and JPEG, PNG, and WebP.

Upload protections:

- The PHP upload error code and `is_uploaded_file()` check reject incomplete uploads and local files supplied in place of HTTP uploads.
- The size is measured from the temporary file and capped at 2 MB, limiting oversized upload and storage exhaustion attempts.
- `finfo` checks the file contents for an allowed MIME type instead of trusting the browser-supplied filename or content type, reducing extension/MIME spoofing.
- `getimagesize()` verifies that the file parses as the image format reported by `finfo`, rejecting non-image files and mismatched or malformed image payloads.
- A `bin2hex(random_bytes(16))` filename prevents attacker-controlled paths, filename collisions, and executable extensions from being used for stored uploads.
- `move_uploaded_file()` only moves a verified PHP HTTP-upload temporary file into `uploads/`; the directory's `.htaccess` also blocks script execution there.
