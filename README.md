# SWD Blog CMS

A lightweight PHP 8 and MySQL blog CMS for XAMPP, with a public blog and an authenticated admin area.

## Features

- Public post cards with covers, excerpts, categories, author names, dates, and six-post pagination.
- Clean post and category URLs, category pages, natural-language search, and short-query fallback search.
- Readable post pages with estimated reading time and related posts.
- Admin sign-in, category and post CRUD, status filtering, and a dashboard with aggregate counts and recent activity.
- CSRF protection for state-changing admin actions and safe cover-image uploads (JPEG, PNG, or WebP up to 2 MB).
- CLI tools for creating an admin account and seeding 12 sample posts.

## Setup with XAMPP

1. Install and start Apache and MySQL from the XAMPP Control Panel.
2. Place the project folder at `C:\xampp\htdocs\swd\07-php-blog`.
3. Open `http://localhost/phpmyadmin/` and create or select the `swd_blog` database. For a clean install, import `database/schema.sql` from the repository. To restore an existing exported database, import your `database.sql` dump instead.
4. The default database connection is `127.0.0.1:3306`, database `swd_blog`, user `root`, and an empty password. If yours differs, configure `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` in the Apache/PHP environment.
5. Ensure `uploads` is writable by Apache. Apache `mod_rewrite` and `.htaccess` overrides must be enabled for clean URLs.
6. Create an administrator from PowerShell or Command Prompt:

   ```powershell
   C:\xampp\php\php.exe C:\xampp\htdocs\swd\07-php-blog\create-admin.php "Your Name" you@example.com
   ```

   Enter and confirm a password of at least 12 characters when prompted. On Windows, PowerShell hides password input.
7. Optionally add sample content:

   ```powershell
   C:\xampp\php\php.exe C:\xampp\htdocs\swd\07-php-blog\seed.php
   ```

8. Visit `http://localhost/swd/07-php-blog/` and sign in at `http://localhost/swd/07-php-blog/admin/login.php`.

Set `APP_BASE_URL` if the project is mounted at a different URL path. The app expects MySQL with InnoDB and FULLTEXT support.

## Screenshots

Screenshots are not included yet. Add screenshots of the public homepage, article page, and admin dashboard here when available.

## Export the database from phpMyAdmin

1. Open `http://localhost/phpmyadmin/` and select the `swd_blog` database in the sidebar.
2. Choose **Export**, select **Quick** and **SQL**, then click **Export**.
3. Save the downloaded file as `database.sql` in a secure location outside `C:\xampp\htdocs` (the dump contains admin password hashes and should not be publicly downloadable).
4. To restore it later, create/select the target database in phpMyAdmin, choose **Import**, select `database.sql`, and click **Import** or **Go**.

For a new empty database, use the repository's `database/schema.sql` instead of an exported dump.

## Folder structure

```text
07-php-blog/
├── admin/                 # Dashboard, login, post and category management
├── app/                   # Bootstrap, database, auth, CSRF, and shared helpers
├── assets/                # Site stylesheet
├── database/
│   └── schema.sql         # Fresh-install database schema
├── uploads/               # Cover images; script execution is blocked
├── category.php           # Category listing route target
├── create-admin.php       # CLI administrator setup
├── index.php              # Public homepage
├── post.php               # Public article route target
├── search.php             # Public search
└── seed.php               # CLI sample-content seeder
```
