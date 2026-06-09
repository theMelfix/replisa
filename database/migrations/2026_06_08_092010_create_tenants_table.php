<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aziende clienti. Modello Tech Provider (ADR-003): le credenziali Meta
     * per-tenant vivono qui; le credenziali app-level restano nel .env.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Credenziali Meta scoped alla WABA del cliente (ADR-003)
            $table->string('phone_number_id')->nullable()->unique();
            $table->string('waba_id')->nullable();
            $table->text('access_token')->nullable(); // cast `encrypted` sul model
            $table->string('plan')->default('starter'); // starter|base|pro|business
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
