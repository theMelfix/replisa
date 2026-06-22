<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add-on Recensioni (E4.2.6): concessione manuale dell'add-on al tenant. È
 * comunque incluso nel piano Business (config plans.addons.reviews.included_in).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('reviews_addon')->default(false)->after('manual_plan_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('reviews_addon');
        });
    }
};
