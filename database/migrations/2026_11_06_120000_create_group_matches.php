<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_matches', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('sport_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reservation_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('status', 16);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->dateTime('closes_at');
            $table->unsignedSmallInteger('duration_minutes');
            $table->decimal('quoted_price', 10, 2);
            $table->unsignedTinyInteger('team_size');
            $table->boolean('mixed_level')->default(false);
            $table->uuid('team_invite')->nullable();
            $table->text('closing_note')->nullable();
            $table->timestamps();
            $table->index(['status', 'closes_at']);
            $table->index(['sport_id', 'starts_at']);
        });

        Schema::create('group_match_players', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('group_match_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('side', 1);
            $table->boolean('organizer')->default(false);
            $table->unsignedTinyInteger('self_rating');
            $table->unsignedTinyInteger('level');
            $table->json('answers');
            $table->dateTime('confirmed_at')->nullable();
            $table->timestamps();
            $table->unique(['group_match_id', 'email']);
            $table->index(['email', 'confirmed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_match_players');
        Schema::dropIfExists('group_matches');
    }
};
