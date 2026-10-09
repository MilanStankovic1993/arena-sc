<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('pricing_rules')->whereIn('name', [
            'Padel radni dan popodne',
            'Padel vikend popodne',
            'Basket radni dan popodne',
            'Basket vikend popodne',
        ])->where('end_time', '23:00:00')->update(['end_time' => '00:00:00']);
    }

    public function down(): void
    {
        DB::table('pricing_rules')->whereIn('name', [
            'Padel radni dan popodne',
            'Padel vikend popodne',
            'Basket radni dan popodne',
            'Basket vikend popodne',
        ])->where('end_time', '00:00:00')->update(['end_time' => '23:00:00']);
    }
};
