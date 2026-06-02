<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("
                ALTER TABLE reservations
                MODIFY status ENUM('pending', 'approved', 'completed', 'cancelled', 'rejected', 'reserved')
                NOT NULL DEFAULT 'approved'
            ");
        }

        DB::table('reservations')
            ->whereIn('status', ['pending', 'approved', 'completed'])
            ->update(['status' => 'reserved']);

        DB::table('reservations')
            ->whereIn('status', ['cancelled', 'rejected'])
            ->update(['status' => 'cancelled']);

        if ($driver === 'mysql') {
            DB::statement("
                ALTER TABLE reservations
                MODIFY status ENUM('reserved', 'cancelled')
                NOT NULL DEFAULT 'reserved'
            ");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("
                ALTER TABLE reservations
                MODIFY status ENUM('pending', 'approved', 'completed', 'cancelled', 'rejected', 'reserved')
                NOT NULL DEFAULT 'approved'
            ");
        }

        DB::table('reservations')
            ->where('status', 'reserved')
            ->update(['status' => 'approved']);
    }
};
