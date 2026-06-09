<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Appuntamenti dei contatti, base per i reminder (E3.2) e le richieste
     * recensione (E3.3).
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->dateTime('scheduled_at');
            // scheduled | confirmed | cancelled | completed
            $table->string('status')->default('scheduled');
            $table->timestamp('reminded_at')->nullable(); // ultimo reminder inviato
            $table->boolean('review_requested')->default(false); // recensione già richiesta
            $table->timestamps();

            $table->index(['tenant_id', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
