<?php

use Illuminate\Support\Facades\Schedule;

// Keep every machine's clock current and settle any day that has ended.
Schedule::command('drinkwell:tick')->everyMinute()->withoutOverlapping(5);

// Expired one-time codes.
Schedule::command('drinkwell:purge')->hourly();
Schedule::command('drinkwell:backup')->dailyAt('02:30');
