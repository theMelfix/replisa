<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flussi di automazione configurati per tenant (welcome, reminder, review...).
     */
    public function up(): void
    {
        Schema::create('automations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // welcome | appointment_reminder | review_request
            $table->string('trigger')->nullable(); // evento/condizione che attiva il flusso
            $table->json('config')->nullable(); // parametri del flusso (template, tempistiche, ecc.)
            $table->boolean('active')->default(true);
            $table->timestamps();

            // Un solo flusso per tipo per tenant.
            $table->unique(['tenant_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automations');
    }
};
