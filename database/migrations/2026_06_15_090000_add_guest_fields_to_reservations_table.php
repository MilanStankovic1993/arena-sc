<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            if (! Schema::hasColumn('reservations', 'guest_name')) {
                $table->string('guest_name')->nullable()->after('user_id');
            }

            if (! Schema::hasColumn('reservations', 'guest_phone')) {
                $table->string('guest_phone')->nullable()->after('guest_name');
            }

            if (! Schema::hasColumn('reservations', 'guest_email')) {
                $table->string('guest_email')->nullable()->after('guest_phone');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            try {
                DB::statement('ALTER TABLE reservations DROP FOREIGN KEY reservations_user_id_foreign');
            } catch (Throwable) {
                //
            }

            DB::statement('ALTER TABLE reservations MODIFY user_id BIGINT UNSIGNED NULL');

            try {
                DB::statement('ALTER TABLE reservations ADD CONSTRAINT reservations_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL');
            } catch (Throwable) {
                //
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            try {
                DB::statement('ALTER TABLE reservations DROP FOREIGN KEY reservations_user_id_foreign');
            } catch (Throwable) {
                //
            }

            DB::statement('ALTER TABLE reservations MODIFY user_id BIGINT UNSIGNED NOT NULL');

            try {
                DB::statement('ALTER TABLE reservations ADD CONSTRAINT reservations_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
            } catch (Throwable) {
                //
            }
        }

        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropColumn(['guest_name', 'guest_phone', 'guest_email']);
        });
    }
};
