<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Log di ogni messaggio inviato/ricevuto (task 2.1.6 / 2.2.x).
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('direction'); // outbound|inbound
            $table->string('type')->default('text'); // text|template|interactive
            $table->json('content')->nullable(); // payload normalizzato (template name+params, testo, ecc.)
            // Stato Meta: queued|sent|delivered|read|failed (outbound); received (inbound)
            $table->string('status')->default('queued');
            $table->string('meta_message_id')->nullable()->index(); // wamid.* per match con status webhook
            $table->json('error')->nullable(); // dettaglio errore Meta in caso di failed
            $table->timestamps();

            $table->index(['tenant_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
