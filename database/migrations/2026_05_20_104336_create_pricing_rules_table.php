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
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('days_of_week')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('price_60', 10, 2);
            $table->decimal('price_90', 10, 2);
            $table->decimal('price_120', 10, 2);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
