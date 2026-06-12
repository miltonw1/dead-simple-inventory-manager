# Subscription Tracking Implementation Plan

## Phase 1: Database Setup and Model definition
- [x] Task: Create migration for `subscriptions` table
    - [x] Generate the migration file using `php artisan make:migration create_subscriptions_table`
    - [x] Define columns (`id` (UUID), `user_id` (FK), `status`, `starts_at`, `ends_at`) and foreign key constraints
- [x] Task: Define the `Subscription` Model
    - [x] Create `App\Models\Subscription.php` using the `UsesUuid` trait
    - [x] Add casts for `starts_at` and `ends_at` to `datetime`
    - [x] Define the `user()` relationship (BelongsTo)
- [x] Task: Update the `User` Model
    - [x] Define the `subscriptions()` relationship (HasMany)
    - [x] Implement the `hasActiveSubscription(): bool` helper method (making sure admins are exempt)
- [x] Task: Add Subscription Factory
    - [x] Create database factory `database/factories/SubscriptionFactory.php` for testing purposes

## Phase 2: Access Control Middleware
- [x] Task: Create the `EnsureActiveSubscription` Middleware
    - [x] Generate middleware `EnsureActiveSubscription` in `App\Http\Middleware`
    - [x] Implement request checking: allow safe methods (`GET`, `HEAD`, `OPTIONS`)
    - [x] Implement active subscription check: return `403 Forbidden` if user has no active subscription and request is unsafe (`POST`, `PUT`, `PATCH`, `DELETE`)
- [x] Task: Register Middleware alias
    - [x] Register the middleware alias `subscription.active` in `bootstrap/app.php`

## Phase 3: Controller and Routes
- [x] Task: Create `SubscriptionController`
    - [x] Create `App\Http\Controllers\SubscriptionController.php`
    - [x] Implement `index` to retrieve the current user's active/latest subscription status
    - [x] Implement `store` to create a new subscription (restricted to admin users)
- [x] Task: Define routes in `routes/api.php`
    - [x] Define route `GET /api/user/subscription` for current user status
    - [x] Define route `POST /api/admin/subscriptions` for creating subscription (Admin only)
    - [x] Apply `subscription.active` middleware to the inventory resource route groups (brands, categories, products, suppliers, storage-locations) in `routes/api.php`

## Phase 4: Testing & Quality Assurance
- [x] Task: Write Feature Tests
    - [x] Create `tests/Feature/SubscriptionTest.php`
    - [x] Test subscription status retrieval endpoint
    - [x] Test admin creation endpoint access and behavior
    - [x] Test that active subscription users can perform writes (POST/PUT/DELETE)
    - [x] Test that inactive/expired subscription users can perform GET but get 403 on writes (POST/PUT/DELETE)
    - [x] Test that admin users are exempt from subscription checks
- [x] Task: Code Formatting and Quality Checks
    - [x] Run test suite with `php artisan test`
    - [x] Run Pint formatter with `composer fmt`
    - [x] Run static analysis with `composer code:analyse`
