<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lead dalla landing (E5.1.2): richieste di contatto/demo inviate dal form
 * pubblico. Non sono tenant-owned (arrivano da visitatori anonimi): nessun
 * `tenant_id`, nessun TenantScope.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('business')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('new'); // new|contacted|converted|archived
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
