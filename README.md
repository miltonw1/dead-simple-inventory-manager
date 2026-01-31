# Dead Simple Inventory Manager

A lightweight inventory management system API built with Laravel 11 and PHP 8.4.

## Features

- **Product Management**: Track name, code, price, and descriptions.
- **Stock Tracking**: Monitor stock levels with minimum stock warnings.
- **Supplier Management**: Keep track of your suppliers and their contact details.
- **Category Organization**: Group products into categories.
- **Storage Locations**: Manage where your inventory is stored.
- **API First**: Powered by Laravel Passport for secure API access.
- **Auto Documentation**: Built-in API documentation using [Scramble](https://scramble.dedoc.co/).

## Requirements

- **PHP 8.4+**
- **Composer**
- **MySQL / PostgreSQL / SQLite**

## Installation

### A. Local Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/malows/dead-simple-inventory-manager.git
   cd dead-simple-inventory-manager
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Setup environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Run migrations:**
   ```bash
   # Ensure you have configured your database in the .env file
   php artisan migrate
   ```

5. **Install Passport:**
   ```bash
   php artisan passport:install
   ```

6. **Create storage symbolic link:**
   ```bash
   php artisan storage:link
   ```

7. **Start the server:**
   ```bash
   php artisan serve
   ```

### B. Docker Installation

1. **Build and start containers:**
   ```bash
   docker-compose up -d --build
   ```

2. **Initialize the application:**
   ```bash
   docker-compose exec app php artisan key:generate
   docker-compose exec app php artisan migrate
   docker-compose exec app php artisan passport:install
   docker-compose exec app php artisan storage:link
   ```

The API will be available at `http://localhost:8000`.

## API Documentation

Once the server is running, you can access the interactive API documentation at:
- `http://localhost:8000/docs/api`

## Development

- **Tests**: 
  - Standard: `php artisan test`
  - Parallel: `php artisan test --parallel` (Faster execution)
  - Coverage: `php artisan test --coverage` (Check code coverage)
- **Static Analysis**: Run `composer code:analyse`
- **Code Style**: Run `composer fmt`

## License

The project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
