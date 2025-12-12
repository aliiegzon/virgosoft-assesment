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
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('symbol');
            $table->enum('side', ['buy', 'sell']);
            $table->decimal('price', 18, 8);
            $table->decimal('amount', 18, 8);
            $table->unsignedTinyInteger('status')->default(1);
            $table->decimal('locked_value', 18, 8)->default(0);
            $table->timestamps();

            $table->index(['symbol', 'side', 'status', 'price']);
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
