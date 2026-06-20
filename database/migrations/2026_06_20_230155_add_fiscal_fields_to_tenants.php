<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dati fiscali del tenant (E6.2/anti-spam): raccolti in registrazione e usati
 * per la fatturazione. La P.IVA è unica e validata (checksum + VIES).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('vat_number')->nullable()->unique()->after('name');
            $table->string('tax_code')->nullable()->after('vat_number');
            $table->string('address')->nullable()->after('tax_code');
            $table->string('city')->nullable()->after('address');
            $table->string('postal_code', 10)->nullable()->after('city');
            $table->string('province', 2)->nullable()->after('postal_code');
            $table->string('country', 2)->default('IT')->after('province');
            $table->string('sdi_code', 7)->nullable()->after('country');
            $table->string('pec')->nullable()->after('sdi_code');
            $table->timestamp('vat_validated_at')->nullable()->after('pec');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropUnique(['vat_number']);
            $table->dropColumn([
                'vat_number', 'tax_code', 'address', 'city', 'postal_code',
                'province', 'country', 'sdi_code', 'pec', 'vat_validated_at',
            ]);
        });
    }
};
