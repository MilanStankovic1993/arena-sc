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
        Schema::create('event_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('home_entry_id')->nullable()->constrained('event_entries')->nullOnDelete();
            $table->foreignId('away_entry_id')->nullable()->constrained('event_entries')->nullOnDelete();
            $table->string('round_label')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->enum('status', ['scheduled', 'finished', 'cancelled'])->default('scheduled');
            $table->unsignedInteger('home_score')->nullable();
            $table->unsignedInteger('away_score')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_matches');
    }
};
