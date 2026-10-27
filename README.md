# Shift Management System

A small Symfony API for registering employee shifts: validates business rules (duration,
same-day, overlap, public holidays), persists shifts to SQLite, and records a
notification for every created shift.

## Setup

### Option A — Docker (recommended)

Requires only Docker and Docker Compose.

```bash
docker compose up --build
```

On first start the container installs Composer dependencies, runs the database
migrations (creating `var/data/shifts_dev.db`), and serves the app at
`http://localhost:8000`.

Create a shift:

```bash
curl -i -X POST http://localhost:8000/shifts \
  -H "Content-Type: application/json" \
  -d '{
        "employee_id": "employee-123",
        "date": "2026-10-06",
        "start_time": "09:00",
        "end_time": "17:00",
        "country_code": "LT"
      }'
```

On success the API returns `201 Created` with the stored shift:

```json
{
  "id": "019a2f6e-3c1b-7d4e-9a0f-5b8c2d1e4f6a",
  "employee_id": "employee-123",
  "start": "2026-10-06 09:00",
  "end": "2026-10-06 17:00",
  "country_code": "LT",
  "created_at": "2026-10-27T09:20:11+00:00"
}
```

Any failure returns `400 Bad Request`, for example an overlapping shift:

```json
{
  "message": "Validation error",
  "errors": ["Shift overlaps with an existing shift for this employee."]
}
```

### Option B — Local PHP

Requires PHP 8.2+ with the `pdo_sqlite` extension, and Composer.

```bash
composer install
php bin/console doctrine:migrations:migrate --no-interaction
symfony server:start   # or: php -S 0.0.0.0:8000 -t public
```

### Database initialization

Schema is managed with Doctrine Migrations (`migrations/`). The single migration
creates two tables, `shift` and `shift_notification`. Re-run
`php bin/console doctrine:migrations:migrate` any time after pulling schema changes.
The SQLite file lives under `var/data/` (git-ignored) and is created automatically.

### Configuration

Toggles live as environment variables (see `.env`; `docker-compose.yml` sets its own
values for the Docker setup):

| Variable                 | Purpose                                                     |
|---------------------------|--------------------------------------------------------------|
| `NOTIFICATIONS_ENABLED`   | `true`/`false` — turn the shift-creation notification side effect on or off, with no code change. |
| `NAGER_API_BASE_URL`      | Base URL of the public holiday API (`.../Holidays/{countryCode}/{year}`). |
| `NAGER_API_TIMEOUT`       | HTTP timeout (seconds) for holiday lookups.                  |
| `DATABASE_URL`            | SQLite connection string.                                     |
| `LOCK_DSN`                | Symfony Lock store for the per-employee lock around shift creation (`flock` = local files). |

## Running tests

```bash
docker compose run --rm app php bin/phpunit
# or, with a local PHP 8.2+ install:
vendor/bin/phpunit
```
