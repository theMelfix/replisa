<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalogo scadenze (E3.2.6): `tenant_id` nullable → null = scadenza nazionale
 * (IMU, 730, … gestite dal super-admin), valorizzato = scadenza propria del
 * tenant. I tenant ci agganciano promemoria via `deadline_reminders`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deadlines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('due_date');
            $table->string('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deadlines');
    }
};
