<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_match_players', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unique(['group_match_id', 'user_id']);
        });

        Schema::create('player_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('starting_rating');
            $table->timestamps();
            $table->unique(['user_id', 'sport_id']);
        });

        Schema::create('player_ratings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('player_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rater_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->timestamps();
            $table->unique(['player_profile_id', 'rater_id']);
        });

        Schema::create('player_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('player_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('group_match_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('own_score');
            $table->unsignedSmallInteger('opponent_score');
            $table->timestamps();
            $table->unique(['player_profile_id', 'group_match_id']);
        });

        Schema::create('player_invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('group_match_id')->constrained()->cascadeOnDelete();
            $table->boolean('challenge');
            $table->boolean('accepted')->nullable();
            $table->timestamps();
            $table->unique(['sender_id', 'recipient_id', 'group_match_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_invitations');
        Schema::dropIfExists('player_results');
        Schema::dropIfExists('player_ratings');
        Schema::dropIfExists('player_profiles');
        Schema::table('group_match_players', function (Blueprint $table): void {
            $table->dropUnique(['group_match_id', 'user_id']);
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
