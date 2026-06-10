<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('membership_plans', 'reservation_limit')) {
            Schema::table('membership_plans', function (Blueprint $table): void {
                $table->unsignedInteger('reservation_limit')->default(1);
            });
        }

        if (
            Schema::hasColumn('membership_plans', 'booking_days_per_week')
            && Schema::hasColumn('membership_plans', 'reservation_limit')
        ) {
            DB::table('membership_plans')->update([
                'reservation_limit' => DB::raw('booking_days_per_week'),
            ]);

            Schema::table('membership_plans', function (Blueprint $table): void {
                $table->dropColumn('booking_days_per_week');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('membership_plans', 'booking_days_per_week')) {
            Schema::table('membership_plans', function (Blueprint $table): void {
                $table->unsignedInteger('booking_days_per_week')->default(1);
            });
        }

        if (
            Schema::hasColumn('membership_plans', 'reservation_limit')
            && Schema::hasColumn('membership_plans', 'booking_days_per_week')
        ) {
            DB::table('membership_plans')->update([
                'booking_days_per_week' => DB::raw('reservation_limit'),
            ]);

            Schema::table('membership_plans', function (Blueprint $table): void {
                $table->dropColumn('reservation_limit');
            });
        }
    }
};
