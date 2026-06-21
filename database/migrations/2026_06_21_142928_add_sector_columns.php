<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Settore (E3.2.6): categoria del tenant e settore di pertinenza di una
 * scadenza nazionale. `deadlines.sector` null = vale per tutti i settori.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('sector')->nullable()->after('name');
        });

        Schema::table('deadlines', function (Blueprint $table) {
            $table->string('sector')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('sector');
        });

        Schema::table('deadlines', function (Blueprint $table) {
            $table->dropColumn('sector');
        });
    }
};
