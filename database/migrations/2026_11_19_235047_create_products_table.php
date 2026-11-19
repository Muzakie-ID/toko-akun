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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('nama');
            $table->string('slug')->unique();
            $table->text('deskripsi')->nullable();
            $table->string('banner')->nullable();
            $table->string('logo')->nullable();
            $table->unsignedSmallInteger('garansi_hari')->default(0);
            $table->decimal('rating', 2, 1)->default(0);
            $table->unsignedInteger('total_terjual')->default(0);
            $table->boolean('is_aktif')->default(true);
            $table->boolean('is_populer')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
