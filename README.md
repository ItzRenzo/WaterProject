# Water Project

A simple PHP-based water delivery inventory and order management system.

## Project Structure

- `codes/` — main PHP application (admin controllers, html views, assets).
- `Css/` — stylesheets.
- `Database/` — database files and DB config (`db_config.php`).
- `vendor/` — third-party dependencies (Composer / dompdf).

## Requirements

- PHP 7.4 or newer
- Web server (Apache, Nginx) or the built-in PHP server
- MySQL or compatible database
- Composer (optional, if you need to reinstall dependencies)

## Quick Start (local)

1. Copy the repository into your webserver document root, or serve the `codes/` folder directly:

```powershell
php -S localhost:8000 -t codes/
```

2. Configure the database connection in `Database/db_config.php`.

3. Create a database and import any provided SQL (if `Database/database` contains SQL, import it), or run your project-specific setup scripts.

4. Open the app in a browser at:

- `http://localhost:8000/html/login.html`

## Dependencies

If you need to (re)install PHP dependencies managed by Composer:

```powershell
composer install
```

## Notes

- The primary application files live under `codes/` — explore `codes/Controllers/`, `codes/admin/`, and `codes/html/` for routes and views.
- `vendor/` already contains `dompdf` used for PDF generation.

## Contributing

If you'd like additions or changes to this README (setup steps, environment notes, screenshots, or credentials flow), open an issue or request specific edits.

---


