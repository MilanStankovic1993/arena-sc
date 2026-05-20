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
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable()->unique();
            $table->string('image')->nullable();
            $table->string('short_description')->nullable();
            $table->text('description')->nullable();
            $table->decimal('rental_price', 10, 2)->default(0);
            $table->decimal('sale_price', 10, 2)->default(0);
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->boolean('is_rentable')->default(true);
            $table->boolean('is_sellable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
