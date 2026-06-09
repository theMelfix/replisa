<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contatti dei clienti (utenti finali che scrivono su WhatsApp).
     */
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('phone'); // E.164, es. 393384852605
            $table->string('name')->nullable();
            $table->boolean('opted_in')->default(false); // consenso GDPR per marketing
            $table->timestamp('opted_in_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            // Un numero è unico per tenant, non globalmente (multi-tenant)
            $table->unique(['tenant_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};