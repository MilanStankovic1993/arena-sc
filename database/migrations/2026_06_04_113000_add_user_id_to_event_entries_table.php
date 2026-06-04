<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_entries', function (Blueprint $table): void {
            $table->foreignIdFor(User::class)->nullable()->after('event_id')->constrained()->nullOnDelete();
            $table->unsignedInteger('draws')->default(0)->after('wins');
        });

        $fallbackUserId = User::query()->value('id');

        if ($fallbackUserId) {
            DB::table('event_entries')
                ->whereNull('user_id')
                ->update(['user_id' => $fallbackUserId]);
        }

        Schema::table('event_entries', function (Blueprint $table): void {
            $table->unique(['event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('event_entries', function (Blueprint $table): void {
            $table->dropUnique(['event_id', 'user_id']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('draws');
        });
    }
};
