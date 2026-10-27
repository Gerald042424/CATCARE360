# CatCare 360

## About the system

CatCare 360 is a web-based cat adoption, monitoring, and volunteer-management system for McKinley West Cats.

It supports the full care journey: cat records, adopter applications, evaluation, approval, adoption follow-ups, and volunteer task reporting.

## Main modules

| Module | Purpose |
| --- | --- |
| Public portal | Lets visitors browse the system and create an adopter account. |
| Adopter portal | Handles applications, cat profiles, adoption progress, and required updates. |
| Staff portal | Manages cats, applications, adopter reviews, volunteers, monitoring, and reports. |
| Volunteer portal | Lets approved volunteers view assigned tasks and submit care reports. |
| Monitoring | Tracks the post-adoption 3-3-3 check-ins and compliance follow-ups. |

## Main features

- Account registration with email verification
- Cat profile and adoption application management
- Document review and adoption evaluation workflow
- Role-based access: adopter, volunteer, and staff administrator
- Volunteer task assignment and reporting
- Post-adoption monitoring and compliance tracking
- Admin analytics, reports, and follow-up flags

## System requirements

- PHP 8.2 or newer
- MySQL or MariaDB
- Apache with `mod_rewrite` enabled
- Composer
- XAMPP for local development

## Local installation

### 1. Start local services

Start **Apache** and **MySQL** from the XAMPP Control Panel.

### 2. Configure environment values

Copy `env` to `.env` and configure these values:

```ini
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost/CATCARE360/public/'
database.default.database = catcare360
database.default.username = root
database.default.password =
```

### 3. Create the database

```powershell
C:\xampp\mysql\bin\mysql.exe -uroot -e "CREATE DATABASE IF NOT EXISTS catcare360 CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"
```

### 4. Install dependencies

```powershell
composer install
```

### 5. Create the system tables and sample records

```powershell
php spark migrate
php spark db:seed CatCareSeeder
```

### 6. Open the system

| Page | URL |
| --- | --- |
| Public portal | `http://localhost/CATCARE360/public/` |
| Staff login | `http://localhost/CATCARE360/public/admin/login` |
| Volunteer access | Use the Sign In button on the public portal. |

## Production deployment checklist

1. Set the web-server document root to `public/`.
2. Create a production `.env` file with live database and email settings.
3. Set `CI_ENVIRONMENT = production`.
4. Run `composer install --no-dev --optimize-autoloader`.
5. Ensure `writable/` and `public/uploads/` are writable by the web server.
6. Run `php spark migrate --all` for database migrations.
7. Replace development seed records and credentials before public use.

## Vercel deployment

The repository uses one Vercel Service named `catcare-app`, built from
`Dockerfile.vercel`. Set the project framework to **Services** so Vercel runs
the PHP application container instead of serving the PHP entry file as a
static asset.

Configure these Production environment variables in the Vercel project before
deploying. The database must be reachable from the public internet and support
TLS when `DATABASE_DEFAULT_ENCRYPT` is set to `true`.

```text
APP_BASE_URL=https://your-project.vercel.app
DATABASE_DEFAULT_HOSTNAME=your-database-host
DATABASE_DEFAULT_DATABASE=your-database-name
DATABASE_DEFAULT_USERNAME=your-database-user
DATABASE_DEFAULT_PASSWORD=your-database-password
DATABASE_DEFAULT_PORT=3306
DATABASE_DEFAULT_ENCRYPT=true
SESSION_DRIVER=CodeIgniter\Session\Handlers\DatabaseHandler
SESSION_SAVEPATH=ci_sessions
```

Do not add the local `.env` file to Vercel: it contains development settings
and is intentionally excluded from the container build.

## Repository safety

- `.env` is excluded because it contains sensitive configuration.
- User uploads are excluded to protect documents, IDs, selfies, and photos.
- Runtime logs, cache files, and sessions are excluded.
