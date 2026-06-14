<?php

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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('provider')->default('manual')->after('status');
            $table->string('provider_subscription_id')->nullable()->after('provider');
            $table->string('provider_payment_id')->nullable()->after('provider_subscription_id');
            $table->string('external_reference')->nullable()->after('provider_payment_id');
            $table->string('plan')->nullable()->after('external_reference');
            $table->decimal('amount', 10, 2)->nullable()->after('plan');
            $table->string('currency', 3)->nullable()->after('amount');
            $table->string('last_payment_status')->nullable()->after('currency');
            $table->timestamp('cancelled_at')->nullable()->after('ends_at');

            $table->index(['provider', 'provider_subscription_id']);
            $table->index('external_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['provider', 'provider_subscription_id']);
            $table->dropIndex(['external_reference']);

            $table->dropColumn([
                'provider',
                'provider_subscription_id',
                'provider_payment_id',
                'external_reference',
                'plan',
                'amount',
                'currency',
                'last_payment_status',
                'cancelled_at',
            ]);
        });
    }
};
