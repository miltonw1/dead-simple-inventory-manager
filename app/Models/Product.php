<?php

namespace App\Models;

use App\Traits\Models\HasUserScope;
use App\Traits\Models\UsesUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Product extends Model
{
    use HasFactory, HasUserScope, LogsActivity, UsesUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'stock',
        'min_stock_warning',
        'price',
        'supplier_id',
        'storage_location_id',
        'user_id',
        'image_path',
        'brand_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['stock', 'last_price_update', 'last_stock_update', 'updated_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                $causer = auth()->user();
                $causerString = $causer ? "{$causer->name}({$causer->id})" : 'System';

                return "Product {$eventName} by {$causerString}";
            });
    }

    /**
     * Get the categories for the product.
     *
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    /**
     * Get the supplier for the product.
     *
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the brand for the product.
     *
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the storage location for the product.
     *
     * @return BelongsTo<StorageLocation, $this>
     */
    public function storageLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class);
    }

    /**
     * Get the user that owns the product.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include products with low stock.
     */
    public function scopeLowStock(Builder $query): void
    {
        $query->whereColumn('stock', '<=', 'min_stock_warning');
    }

    /**
     * Get the warning state for the product.
     */
    public function warning(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                $stock = $attributes['stock'] ?? 0;

                $minStockWarning = $attributes['min_stock_warning'] ?? 0;

                return $stock <= $minStockWarning;
            }
        );
    }
}
