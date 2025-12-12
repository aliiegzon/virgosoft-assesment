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
        Schema::create('trades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('symbol');
            $table->foreignUuid('buy_order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignUuid('sell_order_id')->constrained('orders')->onDelete('cascade');
            $table->decimal('price', 18, 8);
            $table->decimal('amount', 18, 8);
            $table->decimal('volume_usd', 18, 8);
            $table->decimal('fee_usd', 18, 8);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
