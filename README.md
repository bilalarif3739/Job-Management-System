# Job Management System API

A Laravel 12 API backend currently focused on authentication and user profiles. The repository does not currently include a frontend application.

## Current features

- Registration and login using manually issued bearer tokens.
- Optional remember-token login.
- Authenticated current-user, logout, and change-password endpoints.
- Email verification and password-reset endpoints.
- Profile viewing and updates, profile-picture upload/removal, and account deletion.
- A consistent JSON response envelope with `success`, `message`, and `data` fields.

## Requirements

- PHP 8.2 or newer with the `pdo_mysql` extension enabled.
- Composer.
- MySQL or MariaDB. XAMPP's MySQL service is suitable for local development.

## Local setup (XAMPP / MySQL)

1. Start **MySQL** from the XAMPP Control Panel.
2. Create a new, empty database in phpMyAdmin, for example `job_management_system`.
3. Install PHP dependencies:

   ```powershell
   composer install
   ```

4. Create `.env` from `.env.example` if you do not already have one:

   ```powershell
   Copy-Item .env.example .env
   ```

   If `.env` already exists, edit it instead of overwriting it. Set the project-specific values, for example:

   ```dotenv
   APP_NAME="Job Management System"
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://127.0.0.1:8000
   FRONTEND_URL=http://localhost:5173

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=job_management_system
   DB_USERNAME=root
   DB_PASSWORD=

   QUEUE_CONNECTION=sync
   SESSION_DRIVER=file
   CACHE_STORE=file
   ```

   Change `DB_USERNAME` and `DB_PASSWORD` if your XAMPP MySQL account uses different credentials. `QUEUE_CONNECTION=sync` runs queued email jobs immediately during local development. If you use the database queue instead, set it to `database` and run a queue worker with `php artisan queue:work`.

5. Keep your working SMTP settings in `.env`, and do not commit real mail credentials. This app's [mail configuration](config/mail.php) reads `MAIL_SCHEME`; the sample environment file currently uses `MAIL_ENCRYPTION` instead. For SMTP on port 587, configure the scheme and provider values that match your mail service. `FRONTEND_URL` should point to the frontend that handles verification and password-reset links.
6. Generate the application key, clear cached configuration, and run migrations:

   ```powershell
   php artisan key:generate
   php artisan config:clear
   php artisan migrate
   ```

   Run migrations against a new, empty database. Do not use `migrate:fresh` on a database containing data you need.
7. Enable public profile-picture URLs:

   ```powershell
   php artisan storage:link
   ```

8. Start the API:

   ```powershell
   php artisan serve --host=127.0.0.1 --port=8000
   ```

   The local API base URL is `http://127.0.0.1:8000`.

## API endpoints

All API routes are prefixed with `/api`. Protected routes require either an `Authorization: Bearer <token>` header or an `X-Remember-Token: <token>` header.

### Authentication

| Method | Path | Access |
| --- | --- | --- |
| `POST` | `/api/auth/register` | Public |
| `POST` | `/api/auth/login` | Public |
| `GET` | `/api/auth/verify/{token}` | Public, verification token required |
| `POST` | `/api/forgetpassword` | Public |
| `POST` | `/api/forgetpassword/{token}` | Public, password-reset token required |
| `GET` | `/api/auth/me` | Authenticated |
| `POST` | `/api/auth/logout` | Authenticated |
| `POST` | `/api/change-password` | Authenticated |

Registration requires `name`, `email`, `password`, and `password_confirmation`. Passwords must be at least 8 characters and include uppercase and lowercase letters, a number, and a symbol. Login accepts `email`, `password`, and optional boolean `is_remember`.

The regular login token is returned in `data.token`. Remember login returns `data.remembertoken`; use it in the `X-Remember-Token` header. The API stores token hashes rather than the plain authentication tokens.

### Profile

| Method | Path | Access |
| --- | --- | --- |
| `GET` | `/api/profile` | Authenticated |
| `PATCH` | `/api/profile` | Authenticated |
| `POST` | `/api/profile/picture` | Authenticated, multipart upload |
| `DELETE` | `/api/profile/picture` | Authenticated |
| `DELETE` | `/api/profile` | Authenticated, requires `current_password` |

Profile updates accept `name` and/or `email`. Picture uploads use the multipart field `profile_picture` and accept JPG, JPEG, PNG, or WebP images up to 5 MB.

### Other

- `GET /` returns a simple API health message.
- `GET /up` is Laravel's configured health endpoint.

## Email flow notes

- The registration controller currently has its verification-email dispatch commented out. The verification route works only when a valid verification token has already been created.
- The password-reset request queues an email job. With `QUEUE_CONNECTION=sync`, it runs immediately; with the database queue, keep `php artisan queue:work` running.
- The current password-reset endpoint also includes its reset token in the API response. Treat that token as sensitive.

## Current limitations

- `bootstrap/app.php` references `routes/Api/conversations.php` and `routes/Api/library_projects.php`, which are not present. The existing `routes/Api/models.php` references an `OpenRouterModelController` that is also not present; as a result, `php artisan route:list` currently fails while inspecting that route.
- Account deletion currently tries to clean up conversations through a `Conversation` model that is not present in the repository. The account-deletion endpoint needs that stale cleanup removed or the conversation feature restored before it can be relied on.

## Tests

Run the test suite with:

```powershell
php artisan test
```
