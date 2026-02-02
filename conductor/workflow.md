# Workflow

## Development Environment
- **Docker**: The project is set up to run with Docker Compose (`app`, `webserver`, `db`).
- **Local**: Can also run locally with `php artisan serve`.

## Common Commands

### Setup
- `cp .env.example .env`
- `docker-compose up -d --build`
- `docker-compose exec app php artisan key:generate`
- `docker-compose exec app php artisan migrate`
- `docker-compose exec app php artisan passport:install`

### Testing
- **Run Tests**: `php artisan test`
- **Parallel Tests**: `php artisan test --parallel`
- **Coverage**: `php artisan test --coverage`

### Code Quality
- **Lint/Format**: `composer fmt` (Runs Laravel Pint)
- **Static Analysis**: `composer code:analyse` (Runs PHPStan)

## API Documentation
Access the interactive API documentation at `http://localhost:8000/docs/api` when the server is running.
