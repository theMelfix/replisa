<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sessioni di conversazione 24h Meta. Una conversazione è la finestra di
     * billing di Meta: ha una categoria e determina se/quanto è fatturabile.
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            // marketing | utility | authentication | service
            $table->string('category')->nullable();
            $table->boolean('billable')->default(true);
            $table->timestamp('opened_at');
            $table->timestamp('expires_at')->nullable(); // opened_at + 24h
            $table->timestamps();

            $table->index(['tenant_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
