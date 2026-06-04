<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sports', function (Blueprint $table): void {
            $table->boolean('supports_online_booking')->default(true)->after('cover_image');
        });

        DB::table('sports')->update(['supports_online_booking' => true]);
    }

    public function down(): void
    {
        Schema::table('sports', function (Blueprint $table): void {
            $table->dropColumn('supports_online_booking');
        });
    }
};
