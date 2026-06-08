# Subscription Tracking Specification

## 1. Overview
This feature introduces a subscription tracking system to regulate user access. Users with inactive or expired subscriptions will be restricted to read-only access (GET requests) on their inventory resources. Write operations (POST, PUT, PATCH, DELETE) will be blocked at the API level by returning a `403 Forbidden` response.

## 2. Database Schema

### `subscriptions` Table
This table will store subscription records for users. A user can have multiple subscription records over time (history).

| Column | Type | Nullable | Notes |
| :--- | :--- | :--- | :--- |
| `id` | UUID | No | Primary Key |
| `user_id` | Foreign Key | No | Cascades on `users.id` |
| `status` | VARCHAR | No | Status of subscription (`active`, `expired`, `cancelled`) |
| `starts_at` | TIMESTAMP | No | Subscription start date/time |
| `ends_at` | TIMESTAMP | No | Subscription end date/time |
| `created_at` | TIMESTAMP | Yes | Laravel created_at timestamp |
| `updated_at` | TIMESTAMP | Yes | Laravel updated_at timestamp |

## 3. Domain Logic & Models

### `Subscription` Model
- Namespace: `App\Models\Subscription`
- Trait: Uses `App\Traits\Models\UsesUuid`
- Casts:
  - `starts_at` -> `datetime`
  - `ends_at` -> `datetime`
- Relationships:
  - `user()`: BelongsTo `User`

### `User` Model Updates
- Relationship:
  - `subscriptions()`: HasMany `Subscription`
- Helper Method:
  - `hasActiveSubscription(): bool`
    - Returns `true` if the user is an `admin` (exempt from restrictions).
    - Returns `true` if there exists a subscription with status `active` where the current date/time falls between `starts_at` and `ends_at`.
    - Otherwise, returns `false`.

## 4. Control of Access (Middleware)

### `EnsureActiveSubscription` Middleware
- Namespace: `App\Http\Middleware\EnsureActiveSubscription`
- Responsibility:
  - If the request is a safe HTTP method (e.g., `GET`, `HEAD`, `OPTIONS`), allow the request.
  - If the request is a write operation (e.g., `POST`, `PUT`, `PATCH`, `DELETE`) AND the authenticated user does NOT have an active subscription:
    - Return a HTTP `403 Forbidden` response with a JSON payload: `{"message": "Your subscription is inactive. Please renew to perform write operations."}`.

## 5. API Endpoints
To support managing subscriptions, we will introduce endpoints for retrieving status and administrative control.
- `POST /api/admin/subscriptions`: Create a new subscription for a user (Admin only).
- `GET /api/user/subscription`: Retrieve the current user's active/latest subscription status.

## 6. Acceptance Criteria
- [ ] Users can always fetch (GET) products, categories, brands, suppliers, etc. even if their subscription is inactive.
- [ ] Users without an active subscription receive a `403 Forbidden` response when attempting to create/update/delete any inventory resource.
- [ ] Users with an active subscription can perform all actions normally.
- [ ] Admins are exempt from the subscription restriction check.
- [ ] Database migrations are created and run successfully.
- [ ] Unit/feature tests are written to verify subscription access control and status check.
