<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_match_players', function (Blueprint $table): void {
            $table->string('level_review_status', 16)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('group_match_players', function (Blueprint $table): void {
            $table->dropColumn('level_review_status');
        });
    }
};
