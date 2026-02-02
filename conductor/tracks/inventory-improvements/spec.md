# Inventory Core Improvements Specification

## 1. Low Stock Scope

### Context
Currently, determining if a product has low stock relies on a computed attribute. This prevents efficient database-level filtering and pagination.

### Technical Implementation
- **File**: `app/Models/Product.php`
- **Method**: `scopeLowStock(Builder $query)`
- **Logic**: `$query->whereColumn('stock', '<=', 'min_stock_warning')`

## 2. Product Observer

### Context
The `Product` model currently handles its own "auditing" logic (updating `last_price_update`, etc.) via setters. This clutters the model.

### Technical Implementation
- **Observer**: `App\Observers\ProductObserver`
- **Event**: `updating`
- **Logic**:
  - Check `isDirty('price')` -> Update `last_price_update`
  - Check `isDirty('stock')` -> Update `last_stock_update`

## 3. Inventory Movements (Kardex)

### Context
Stock changes are destructive; the old value is lost. We need a ledger of all movements.

### Database Schema (`inventory_movements`)
| Column | Type | Notes |
| :--- | :--- | :--- |
| `id` | UUID | Primary Key |
| `product_id` | FK | Links to products |
| `user_id` | FK | Who made the change |
| `type` | ENUM | `purchase`, `sale`, `adjustment`, `return` |
| `quantity` | INT | The change amount (+/-) |
| `previous_stock` | INT | Snapshot before change |
| `new_stock` | INT | Snapshot after change |
| `notes` | TEXT | Optional reason |
| `created_at` | TIMESTAMP | When it happened |

### Service Logic (`InventoryService`)
- Must wrap the creation of the movement record and the update of the product stock in a `DB::transaction`.
