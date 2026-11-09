<?php

use App\Services\GroupMatchService;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

Schedule::command('memberships:send-expiry-reminders')->dailyAt('09:00');
Artisan::command('group-matches:reconcile', function (): void {
    app(GroupMatchService::class)->reconcile();
})->purpose('Rezerviši popunjene grupne termine i zatvori istekle prijave.');

Schedule::command('group-matches:reconcile')->everyMinute()->withoutOverlapping();
Schedule::command('queue:work --stop-when-empty --tries=3 --timeout=90')
    ->everyMinute()
    ->withoutOverlapping();
