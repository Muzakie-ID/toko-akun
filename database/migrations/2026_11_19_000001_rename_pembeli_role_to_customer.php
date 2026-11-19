<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Samakan nama role dengan PRD: customer|reseller|karyawan|admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'pembeli')->update(['role' => 'customer']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('customer')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('pembeli')->change();
        });

        DB::table('users')->where('role', 'customer')->update(['role' => 'pembeli']);
    }
};
