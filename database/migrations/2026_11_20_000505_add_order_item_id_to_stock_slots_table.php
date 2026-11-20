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
        Schema::table('stock_slots', function (Blueprint $table) {
            // Slot tersedia = order_item_id null. Unik: satu slot hanya untuk satu item pesanan.
            $table->foreignId('order_item_id')->nullable()->unique()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_slots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_item_id');
        });
    }
};
