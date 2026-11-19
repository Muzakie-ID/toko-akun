<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('jenis_akun', 20);
            $table->unsignedSmallInteger('durasi_hari');
            $table->unsignedInteger('harga');
            $table->unsignedInteger('harga_asli')->nullable();
            $table->unsignedInteger('harga_reseller')->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'jenis_akun', 'durasi_hari']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
