<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Short-link tracciati per la richiesta recensione (E3.3.3). I click sul button
 * URL di un template Meta non passano dal webhook: per tracciarli, il template
 * punta a `/r/{token}` (Replisa), che registra il click e reindirizza all'URL
 * recensioni Google del tenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('token', 32)->unique();
            $table->string('destination_url', 2048);
            $table->unsignedInteger('clicks')->default(0);
            $table->timestamp('first_clicked_at')->nullable();
            $table->timestamp('last_clicked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_clicks');
    }
};
