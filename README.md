<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

# one-chat-api (Laravel 11)

This project is a Laravel 11 API backend configured for:

- Authentication against a separate user database using Laravel Sanctum personal access tokens.
- Project details served from a dedicated projects database.
- Daily timesheet CRUD against a dedicated timesheet database.

Key files added/updated:

- `bootstrap/app.php` – API routes enabled at `routes/api.php` and global `CorsMiddleware` registered.
- `routes/api.php` – Auth, Project, and Timesheet endpoints.
- `app/Http/Middleware/CorsMiddleware.php` – Simple configurable CORS.
- `app/Http/Controllers/AuthController.php` – Login (via external user DB), logout, and me endpoints.
- `app/Http/Controllers/ProjectController.php` – Project list and detail.
- `app/Http/Controllers/TimesheetController.php` – Timesheet list/create/update/delete.
- `config/database.php` – Three extra DB connections: `userdb`, `projectsdb`, `timesheetdb`.
- `.env.example` – Environment keys for the three DBs, table, and column names.

## Quick start

1) Install dependencies

```bash
composer install
```

2) Copy environment file and set values

```bash
copy .env.example .env
php artisan key:generate
```

Edit `.env` to point to your MySQL instances. Out of the box the default app connection is `sqlite` (database file at `database/database.sqlite`). If you prefer MySQL for app internals, uncomment the `DB_*` variables.

3) Database migrations (for app internals & Sanctum tokens)

The personal access tokens table is created on the default connection. If you keep `sqlite`, you can run:

```bash
php artisan migrate
```

If you switch the default to MySQL, ensure the database exists and credentials are correct before running migrate.

## API Endpoints

- `POST /api/auth/login` – Body: `{ email, password }` – returns `{ token, user }`
- `POST /api/auth/logout` – Header: `Authorization: Bearer <token>`
- `GET /api/auth/me` – Header: `Authorization: Bearer <token>`
- `GET /api/projects` – Header: `Authorization: Bearer <token>`
- `GET /api/projects/{id}` – Header: `Authorization: Bearer <token>`
- `GET /api/timesheets` – Query: `date`, `user_id` (optional)
- `POST /api/timesheets` – Body: `{ date, user_id, project_id, hours, notes? }`
- `PUT/PATCH /api/timesheets/{id}` – Partial update body allowed
- `DELETE /api/timesheets/{id}`

## Environment variables

Set these to match your actual schema when provided.

- User DB (used for login): `USER_DB_HOST`, `USER_DB_DATABASE`, `USER_DB_USERNAME`, `USER_DB_PASSWORD`, `USER_TABLE`, `USER_EMAIL_COLUMN`, `USER_PASSWORD_COLUMN`, `USER_NAME_COLUMN`.
- Projects DB: `PROJECT_DB_HOST`, `PROJECT_DB_DATABASE`, `PROJECT_DB_USERNAME`, `PROJECT_DB_PASSWORD`, `PROJECTS_TABLE`, `PROJECTS_ID_COLUMN`.
- Timesheet DB: `TIMESHEET_DB_HOST`, `TIMESHEET_DB_DATABASE`, `TIMESHEET_DB_USERNAME`, `TIMESHEET_DB_PASSWORD`, `TIMESHEETS_TABLE`, `TIMESHEETS_ID_COLUMN`, `TIMESHEETS_DATE_COLUMN`, `TIMESHEETS_USER_ID_COLUMN`, `TIMESHEETS_PROJECT_ID_COLUMN`, `TIMESHEETS_HOURS_COLUMN`, `TIMESHEETS_NOTES_COLUMN`.

## CORS

Configure origins in `.env` via `CORS_ALLOWED_ORIGINS`. For development, `*` is allowed by default. In production, set a comma-separated whitelist.

## Notes

- Authentication uses Sanctum personal access tokens. First login issues a token based on verifying credentials in `userdb`; a local `users` row is created/synchronized to hold the token relationship.
- Project/timesheet controllers use the named connections `projectsdb` and `timesheetdb`. Once schemas are finalized, update the table/column environment variables or swap to Eloquent models if preferred.
