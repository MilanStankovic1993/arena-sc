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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('type', ['tournament', 'league']);
            $table->enum('status', ['draft', 'registration', 'ongoing', 'completed'])->default('draft');
            $table->string('location')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('cta_label')->nullable();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->longText('rules')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
