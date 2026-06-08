# Subscription Tracking Implementation Plan

## Phase 1: Database Setup and Model definition
- [ ] Task: Create migration for `subscriptions` table
    - [ ] Generate the migration file using `php artisan make:migration create_subscriptions_table`
    - [ ] Define columns (`id` (UUID), `user_id` (FK), `status`, `starts_at`, `ends_at`) and foreign key constraints
- [ ] Task: Define the `Subscription` Model
    - [ ] Create `App\Models\Subscription.php` using the `UsesUuid` trait
    - [ ] Add casts for `starts_at` and `ends_at` to `datetime`
    - [ ] Define the `user()` relationship (BelongsTo)
- [ ] Task: Update the `User` Model
    - [ ] Define the `subscriptions()` relationship (HasMany)
    - [ ] Implement the `hasActiveSubscription(): bool` helper method (making sure admins are exempt)
- [ ] Task: Add Subscription Factory
    - [ ] Create database factory `database/factories/SubscriptionFactory.php` for testing purposes

## Phase 2: Access Control Middleware
- [ ] Task: Create the `EnsureActiveSubscription` Middleware
    - [ ] Generate middleware `EnsureActiveSubscription` in `App\Http\Middleware`
    - [ ] Implement request checking: allow safe methods (`GET`, `HEAD`, `OPTIONS`)
    - [ ] Implement active subscription check: return `403 Forbidden` if user has no active subscription and request is unsafe (`POST`, `PUT`, `PATCH`, `DELETE`)
- [ ] Task: Register Middleware alias
    - [ ] Register the middleware alias `subscription.active` in `bootstrap/app.php`

## Phase 3: Controller and Routes
- [ ] Task: Create `SubscriptionController`
    - [ ] Create `App\Http\Controllers\SubscriptionController.php`
    - [ ] Implement `index` to retrieve the current user's active/latest subscription status
    - [ ] Implement `store` to create a new subscription (restricted to admin users)
- [ ] Task: Define routes in `routes/api.php`
    - [ ] Define route `GET /api/user/subscription` for current user status
    - [ ] Define route `POST /api/admin/subscriptions` for creating subscription (Admin only)
    - [ ] Apply `subscription.active` middleware to the inventory resource route groups (brands, categories, products, suppliers, storage-locations) in `routes/api.php`

## Phase 4: Testing & Quality Assurance
- [ ] Task: Write Feature Tests
    - [ ] Create `tests/Feature/SubscriptionTest.php`
    - [ ] Test subscription status retrieval endpoint
    - [ ] Test admin creation endpoint access and behavior
    - [ ] Test that active subscription users can perform writes (POST/PUT/DELETE)
    - [ ] Test that inactive/expired subscription users can perform GET but get 403 on writes (POST/PUT/DELETE)
    - [ ] Test that admin users are exempt from subscription checks
- [ ] Task: Code Formatting and Quality Checks
    - [ ] Run test suite with `php artisan test`
    - [ ] Run Pint formatter with `composer fmt`
    - [ ] Run static analysis with `composer code:analyse`
