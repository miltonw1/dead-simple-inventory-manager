# Dead Simple Inventory Manager - Roadmap & TODO

This file tracks improvements and new features based on user feedback.

## 📦 New Entities

- [ ] **Brand Management**:
    - **Description**: Add the ability to categorize products by brand for better filtering and reporting.
    - **Technical Details**:
        - **Model**: `Brand` using `UsesUuid` and `HasUserScope` traits.
        - **Migration**: Create `brands` table and add `brand_id` foreign key to `products` (nullable).
        - **API**: Standard CRUD endpoints in `BrandController`.
        - **Authorization**: `BrandPolicy` to ensure data isolation per user.

## 💰 Bulk Operations & Sales

- [ ] **Bulk Price Adjustments**:
    - **Description**: Enable mass price updates across categories or brands.
    - **Technical Details**:
        - **Controller**: `BulkOperationController` with methods for category/brand scopes.
        - **Logic**: Use `DB::transaction` to ensure atomicity. Iterative Eloquent updates to trigger `last_price_update` timestamps.
        - **Payload**: `{ "type": "percentage|fixed", "value": 10, "direction": "increase|decrease" }`.

- [ ] **Automated Sales System**:
    - **Description**: Create a dedicated sale entity that automatically manages inventory.
    - **Technical Details**:
        - **Model**: `Sale` (`product_id`, `quantity`, `unit_price`, `total_price`, `sold_at`).
        - **Workflow**: `POST /api/bulk-operations/products` -> Create `Sale` record -> Trigger stock reduction.
        - **Automation**: Use an Observer or Service class to handle the decrement of `Product->stock` and validation of available inventory.

        ```
            `POST /api/bulk-operations/categories` -> {
                operation: "increase percent price" | "increase fixed price" | "decrease percent price" | "decrease fixed price" | "decrease fixed stock"
                targets: [
                    { id: 123, amount: 1.1 }
                ]
            }
            `POST /api/bulk-operations/products` -> {
                operation: "set stock" | "set price" | "increase stock" | "increase price" | "decrease stock" | "decrease price"
                targets: [
                    { id: 1, amount: 10 },
                    { id: 2, amount: 30 },
                    { id: 3, amount: 30 },
                    { id: 4, amount: 30 },
                    { id: 5, amount: 30 },
                    { id: 6, amount: 30 },
                    { id: 7, amount: 30 },
                ]
            }

        ```


## 🛡️ Audit & Traceability

- [ ] **Activity Logging**:
    - **Description**: Track all sensitive actions performed by users.
    - **Technical Details**:
        - **Model**: `ActivityLog` (polymorphic).
        - **Fields**: `user_id`, `action`, `subject_id`, `subject_type`, `payload` (json with old/new values), `ip_address`.
        - **Scope**: Log price changes, user management, and bulk operations.

- [ ] **Stock Movement History**:
    - **Description**: Maintain a detailed ledger of every stock change.
    - **Technical Details**:
        - **Model**: `StockMovement`.
        - **Fields**: `product_id`, `user_id`, `quantity` (delta), `type` (sale, adjustment, restock, damage), `before`, `after`.
        - **Enforcement**: Ensure every update to `product.stock` creates a movement record.

## ⚡ Workflow Optimization (UX)

- [ ] **Quick Stock Reduction**:
    - **Description**: Dedicated endpoint for manual adjustments (e.g., damage or internal use).
    - **Technical Details**: `POST /api/products/{product}/reduce-stock` using `$product->decrement('stock', $amount)`.

## 🖼️ Rich Content

- [ ] **Product Image**:
    - **Description**: Support for a single product image.
    - **Technical Details**:
        - **Controller**: Use `ProductController` to handle image operations.
        - **Endpoint**: `POST /api/products/{product}/image` for upload/update.
        - **Migration**: Add `image_path` column (nullable) to `products` table.
        - **Storage**: Only one image per product stored in `Storage`.
        - **Process**: Convert and resize images before storing it.
