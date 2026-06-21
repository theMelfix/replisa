<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Licenza offline (E5): l'admin assegna un piano a un tenant senza pagamento
 * Stripe. Colonna dedicata `manual_plan` (nullable) — separata da `plan` (che
 * ha default 'starter') per distinguere chiaramente "nessuna licenza" da una
 * licenza assegnata. Scadenza opzionale (`manual_plan_expires_at`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('manual_plan')->nullable()->after('plan');
            $table->timestamp('manual_plan_expires_at')->nullable()->after('manual_plan');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['manual_plan', 'manual_plan_expires_at']);
        });
    }
};
