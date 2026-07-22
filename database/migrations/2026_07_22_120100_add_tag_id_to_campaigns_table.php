<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Segmento di una campagna (E3.4.3): `tag_id` null = tutti i contatti opted-in;
 * valorizzato = solo gli opted-in con quell'etichetta. Il tag può essere
 * eliminato senza perdere lo storico della campagna (nullOnDelete).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->foreignId('tag_id')->nullable()->after('tenant_id')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tag_id');
        });
    }
};
