<?php

namespace Tests\Feature;

use App\Models\Court;
use App\Models\Equipment;
use App\Models\Reservation;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OpeningPromotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_correction_is_scoped_backed_up_consistent_and_idempotent(): void
    {
        Mail::fake();
        Storage::fake('local');
        config(['arena.seed_admin.email' => '', 'arena.seed_admin.password' => '']);
        $this->seed(DatabaseSeeder::class);
        $court = Court::where('slug', 'padel-teren-1')->sole();
        $ids = [];
        foreach (['2026-10-15 23:00:00', '2026-10-16 10:00:00', '2026-10-18 23:00:00', '2026-10-19 00:00:00'] as $start) {
            $ids[] = DB::table('reservations')->insertGetId([
                'court_id' => $court->id, 'sport_id' => $court->sport_id,
                'status' => 'reserved', 'starts_at' => $start,
                'ends_at' => Carbon::parse($start)->addHour(),
                'court_price' => 3000, 'equipment_price' => 400, 'total_price' => 3400,
                'created_at' => '2026-10-10 12:00:00', 'updated_at' => '2026-10-10 12:00:00',
            ]);
        }
        $itemId = DB::table('reservation_equipment')->insertGetId([
            'reservation_id' => $ids[1], 'equipment_id' => Equipment::first()->id,
            'quantity' => 2, 'unit_price' => 200, 'line_total' => 400,
        ]);
        $this->artisan('reservations:apply-opening-promotion')->assertSuccessful();
        $this->assertSame(3400.0, (float) Reservation::find($ids[1])->total_price);
        $this->assertCount(0, Storage::disk('local')->allFiles('maintenance'));
        $this->artisan('reservations:apply-opening-promotion --apply')->assertSuccessful();
        foreach ([$ids[1], $ids[2]] as $id) {
            $this->assertSame(0.0, (float) Reservation::find($id)->total_price);
            $this->assertSame('reserved', Reservation::find($id)->status->value);
        }
        foreach ([$ids[0], $ids[3]] as $id) {
            $this->assertSame(3400.0, (float) Reservation::find($id)->total_price);
        }
        $this->assertDatabaseHas('reservation_equipment', ['id' => $itemId, 'unit_price' => 0, 'line_total' => 0, 'quantity' => 2]);
        $files = Storage::disk('local')->allFiles('maintenance');
        $this->assertCount(1, $files);
        $original = json_decode(Storage::disk('local')->get($files[0]), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(3400.0, (float) $original['reservations'][0]['total_price']);
        $this->artisan('reservations:apply-opening-promotion --apply')->assertSuccessful();
        $this->assertCount(1, Storage::disk('local')->allFiles('maintenance'));
        Mail::assertNothingQueued();
    }
}
