<?php

use App\Enums\ReservationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['reservation_id', 'user_id']);
        });

        $now = now();

        DB::table('reservations')
            ->orderBy('id')
            ->chunkById(500, function ($reservations) use ($now): void {
                $rows = $reservations
                    ->map(fn ($reservation): array => [
                        'reservation_id' => $reservation->id,
                        'user_id' => $reservation->user_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->all();

                if ($rows !== []) {
                    DB::table('reservation_participants')->insertOrIgnore($rows);
                }
            });

        DB::table('users')
            ->orderBy('id')
            ->chunkById(200, function ($users): void {
                foreach ($users as $user) {
                    $baseQuery = DB::table('reservation_participants')
                        ->join('reservations', 'reservations.id', '=', 'reservation_participants.reservation_id')
                        ->where('reservation_participants.user_id', $user->id);

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'total_reservations' => (clone $baseQuery)->count(),
                            'cancelled_reservations' => (clone $baseQuery)
                                ->where('reservations.status', ReservationStatus::Cancelled->value)
                                ->count(),
                            'last_reservation_at' => (clone $baseQuery)->max('reservations.starts_at'),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_participants');
    }
};
