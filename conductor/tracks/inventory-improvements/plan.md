# Track: Inventory Core Improvements

## Goal
Implement critical technical improvements to the inventory system, focusing on performance, code architecture, and data integrity.

## Proposals
This track covers Proposals 1, 2, and 3 from `IDEAS.md`.

### 1. Performance: SQL Scopes for "Low Stock"
**Objective**: Optimize filtering of low-stock products by moving logic from PHP application layer to Database layer.
- [ ] Add `scopeLowStock` to `app/Models/Product.php`.
- [ ] Ensure it generates `WHERE stock <= min_stock_warning`.
- [ ] Verify functionality with a test case.

### 2. Refactoring: Decoupling with Observers
**Objective**: Clean up the `Product` model by moving auditing logic (updating timestamps) to a dedicated Observer.
- [ ] Create `app/Observers/ProductObserver.php`.
- [ ] Move logic for `last_price_update` and `last_stock_update` from model mutators to the Observer's `updating` method.
- [ ] Register the observer in `app/Providers/AppServiceProvider.php` (or use automatic discovery if configured).
- [ ] Verify that updates still trigger the timestamp changes.

### 3. Inventory History (Kardex / Audit Log)
**Objective**: Ensure data integrity and traceability by recording every stock change in an immutable ledger.
- [ ] Create migration for `inventory_movements` table.
- [ ] Create `InventoryMovement` model.
- [ ] Create `InventoryService` to handle stock adjustments transactionally.
    - Method: `adjustStock(Product $product, int $diff, string $type, ?string $notes)`
- [ ] Refactor existing controllers to use `InventoryService` instead of direct model updates.
- [ ] Add tests for the service to ensure `inventory_movements` are created correctly.

## Dependencies
- Existing `Product` model.
- Database access.

## Deliverables
- Improved `Product` model with `scopeLowStock`.
- Cleaner `Product` model using `ProductObserver`.
- New `InventoryMovement` table and model.
- `InventoryService` managing all stock changes.
- Test coverage for new features.
