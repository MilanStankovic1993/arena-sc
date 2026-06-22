<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('memberships:send-expiry-reminders')->dailyAt('09:00');
Schedule::command('queue:work --stop-when-empty --tries=3 --timeout=90')
    ->everyMinute()
    ->withoutOverlapping();
