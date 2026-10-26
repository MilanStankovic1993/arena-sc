<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ApplyOpeningPromotion extends Command
{
    protected $signature = 'reservations:apply-opening-promotion {--apply : Sacuvaj korekciju; bez opcije samo pregled}';

    protected $description = 'Postavlja besplatne termine otvaranja 16–18.10.2026 na 0 RSD, uz privatnu rezervnu kopiju iznosa.';

    public function handle(): int
    {
        return DB::transaction(function (): int {
            // Match the date of play, not the date when the booking was entered.
            $records = DB::table('reservations')
                ->where('starts_at', '>=', '2026-10-16 00:00:00')
                ->where('starts_at', '<', '2026-10-19 00:00:00')
                ->orderBy('id')->lockForUpdate()
                ->get(['id', 'starts_at', 'court_price', 'equipment_price', 'total_price']);
            $items = DB::table('reservation_equipment')->whereIn('reservation_id', $records->pluck('id'))
                ->orderBy('id')->lockForUpdate()->get(['id', 'reservation_id', 'unit_price', 'line_total']);
            $changedIds = $records->filter(fn ($row) => (float) $row->court_price !== 0.0
                || (float) $row->equipment_price !== 0.0 || (float) $row->total_price !== 0.0)->pluck('id')
                ->merge($items->filter(fn ($row) => (float) $row->unit_price !== 0.0 || (float) $row->line_total !== 0.0)->pluck('reservation_id'))
                ->unique()->values();
            $this->info('Promotivni period: '.$records->count().' rezervacija; za korekciju: '.$changedIds->count().'; prethodni zbir: '.$records->sum('total_price').' RSD.');
            if (! $this->option('apply') || $changedIds->isEmpty()) {
                return self::SUCCESS;
            }

            // Keep original amounts before touching financial history. No customer data is exported.
            $backup = 'maintenance/opening-promotion-'.now()->format('Ymd-His').'-'.Str::uuid().'.json';
            $contents = json_encode([
                'period' => ['2026-10-16', '2026-10-18'],
                'reservations' => $records->whereIn('id', $changedIds)->values(),
                'reservation_equipment' => $items->whereIn('reservation_id', $changedIds)->values(),
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            if (! Storage::disk('local')->put($backup, $contents)
                || Storage::disk('local')->get($backup) !== $contents) {
                throw new RuntimeException('Rezervna kopija nije sacuvana. Iznosi nisu promenjeni.');
            }

            // Query updates deliberately avoid reservation observers, notifications and schedule validation.
            DB::table('reservation_equipment')->whereIn('reservation_id', $changedIds)->update(['unit_price' => 0, 'line_total' => 0]);
            DB::table('reservations')->whereIn('id', $changedIds)->update(['court_price' => 0, 'equipment_price' => 0, 'total_price' => 0]);
            $this->info('Korigovano: '.$changedIds->count().'; rezervna kopija: '.Storage::disk('local')->path($backup));

            return self::SUCCESS;
        });
    }
}
