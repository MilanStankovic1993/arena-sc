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
        Schema::create('event_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('team_name');
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->unsignedInteger('played')->default(0);
            $table->unsignedInteger('wins')->default(0);
            $table->unsignedInteger('losses')->default(0);
            $table->unsignedInteger('points')->default(0);
            $table->unsignedInteger('score_for')->default(0);
            $table->unsignedInteger('score_against')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'team_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_entries');
    }
};
