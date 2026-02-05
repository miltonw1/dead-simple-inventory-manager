<?php

namespace App\Models;

use App\Traits\Models\UsesUuid;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable
{
    use CausesActivity, HasApiTokens, HasFactory, LogsActivity, Notifiable, UsesUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['password', 'remember_token'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                $causer = auth()->user();
                $causerString = $causer ? "{$causer->name}({$causer->id})" : 'System';

                return "User {$eventName} by {$causerString}";
            });
    }

    /**
     * Get the products for the user.
     *
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the brands for the user.
     *
     * @return HasMany<Brand, $this>
     */
    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    /**
     * Get the suppliers for the user.
     *
     * @return HasMany<Supplier, $this>
     */
    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    /**
     * Get the storage locations for the user.
     *
     * @return HasMany<StorageLocation, $this>
     */
    public function storageLocations(): HasMany
    {
        return $this->hasMany(StorageLocation::class);
    }

    /**
     * Get the categories for the user.
     *
     * @return HasMany<Category, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the is_admin attribute.
     */
    protected function isAdmin(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->role === 'admin',
        );
    }

    /**
     * Set the user's password.
     */
    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => ['password' => Hash::make($value)],
        );
    }
}
