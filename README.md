# Strugal Production

A web app for tracking production reports in a factory. It started with the **Laquage** (lacquering) department: operators fill in a daily report with its production lines, follow rejection rates on a dashboard and export everything to Excel. The interface is fully in **French**.

![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![Livewire](https://img.shields.io/badge/Livewire-4-FB70A9)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?logo=tailwindcss&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php&logoColor=white)

## Features

- **Dashboard**: report, line and bar counts, rejection rate, a 14-day activity chart and a conforming/rejected donut chart.
- **Laquage reports**
  - One-page form: report information on top, a line form below. Every production line is saved as soon as it is added.
  - List with search, date filters, pagination, view, edit, delete and Excel download.
  - Report detail page with totals.
  - Excel export built from the workshop's own template.
- **Roles and departments**: administrators see everything; department users only see their own department, and only their own reports.
- **Administration**: manage users (search, create, edit, delete) and departments.
- **Account**: profile, password, two-factor authentication and passkeys, built on Laravel Fortify.
- **UI**: collapsible sidebar, light/dark mode and a responsive layout, styled after the [TailAdmin](https://tailadmin.com) design.

## Tech stack

| Area | Technology |
| --- | --- |
| Backend | Laravel 13, PHP 8.3+ |
| Front end | Livewire 4, Flux UI, Alpine.js, Tailwind CSS 4, ApexCharts, Vite |
| Auth | Laravel Fortify (password, 2FA, passkeys) |
| Excel | PhpSpreadsheet |
| Database | SQLite by default (any Laravel-supported database works) |

## Requirements

- PHP 8.3 or higher with the `gd`, `zip`, `intl`, `bcmath`, `gmp` and `pdo_sqlite` extensions
- Composer 2
- Node.js 22 and npm

## Getting started

```bash
git clone <your-repository-url> strugal-production
cd strugal-production

composer install
cp .env.example .env
php artisan key:generate

touch database/database.sqlite      # if you use SQLite
php artisan migrate

npm install
```

Create the departments and the first administrator:

```bash
ADMIN_EMAIL=you@example.com ADMIN_PASSWORD='a-strong-password' php artisan app:install
```

If you leave `ADMIN_PASSWORD` out, a random password is generated and printed once. The command does nothing if an administrator already exists.

> `php artisan db:seed` also works, but it creates a demo admin (`admin@gmail.com` / `password`). Change that password straight away, and never use it in production.

Start the app:

```bash
php artisan serve        # http://localhost:8000
npm run dev              # in a second terminal
```

For production assets, run `npm run build` instead of `npm run dev`.

## Configuration

Main `.env` values:

| Variable | Purpose |
| --- | --- |
| `APP_NAME` | Name shown in the sidebar and page titles |
| `APP_URL` | URL the app is served from |
| `APP_LOCALE` | Language (`fr` by default) |
| `APP_TIMEZONE` | Timezone used for report dates (e.g. `Africa/Casablanca`) |
| `DB_CONNECTION`, `DB_DATABASE` | Database settings |

The Excel export reads its template from `storage/app/templates/departments.xlsx`. Keep that file in place, otherwise the export returns an error.

## Roles

| Role | Access |
| --- | --- |
| `admin` | All departments and all reports, users and departments administration |
| `department` | Only the pages of their own department, and only the reports they created |

## Tests

```bash
php artisan test
```

## Deployment

A `Dockerfile` and a `docker-compose.yml` are included, with a step-by-step guide for a ZimaOS (or any Docker) server in [`DEPLOY-ZIMAOS.md`](DEPLOY-ZIMAOS.md).

```bash
docker compose up -d --build
```

## Project structure

```
app/Livewire/            Dashboard, Laquage report pages, admin pages
app/Models/              User, Department, LaquageReport, LaquageReportLine
app/Services/            Excel exporter
lang/fr*                 French translations
resources/views/         Layouts, Blade/Livewire views and reusable UI components
routes/web.php           Application routes
```

## Contributing

Open an issue to discuss a change, then send a pull request. Please run `php artisan test` and `./vendor/bin/pint` before submitting.
