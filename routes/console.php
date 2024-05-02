<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
    $toto[' COUCOU '] =  4;
    \Log::warning('Yo !');
    $this->info("Hein ?!");
})->purpose('Display an inspiring quote');
Artisan::command('ior:clear', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    Artisan::call('route:cache');
    Artisan::call('optimize:clear');
    Artisan::call('permission:cache-reset');

    $this->info("Done.");
})->purpose('Clear all caches');

