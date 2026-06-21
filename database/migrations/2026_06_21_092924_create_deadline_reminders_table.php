<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Promemoria di scadenza configurato da un tenant (E3.2.6): collega il tenant a
 * una scadenza (nazionale o propria) con il template da inviare e i giorni di
 * anticipo. Allo scattare, lo scheduler crea una campagna verso i contatti
 * opted-in. `dispatched_at` evita reinvii per la stessa scadenza.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deadline_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deadline_id')->constrained()->cascadeOnDelete();
            $table->string('template_name');
            $table->string('language', 5)->default('it');
            $table->boolean('include_name')->default(true);
            $table->unsignedSmallInteger('days_before')->default(7);
            $table->boolean('active')->default(true);
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'deadline_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deadline_reminders');
    }
};
