<?php

use App\Models\Brand;
use App\Models\StorageLocation;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index();

            $table->string('name');
            $table->string('code')->default('');
            $table->decimal('price', 8, 2)->unsigned()->nullable();
            $table->bigInteger('stock')->default(0);
            $table->bigInteger('min_stock_warning')->default(0);
            $table->string('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_url')->nullable();

            $table->timestamp('last_stock_update')->nullable();
            $table->timestamp('last_price_update')->nullable();

            $table->foreignIdFor(Supplier::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignIdFor(StorageLocation::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignIdFor(Brand::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();

            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
