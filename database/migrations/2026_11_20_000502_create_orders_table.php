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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('no_invoice', 32)->unique();
            $table->string('metode_bayar', 20);
            $table->string('status', 30)->index();
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('kode_unik')->default(0);
            $table->unsignedInteger('total');
            $table->string('gateway_id')->nullable()->unique();
            $table->string('checkout_url')->nullable();
            $table->string('qr_image_url')->nullable();
            $table->text('qris_payload')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('late_paid_at')->nullable();
            $table->string('bukti_bayar_path')->nullable();
            $table->string('alasan_tolak')->nullable();
            $table->text('catatan_admin')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
