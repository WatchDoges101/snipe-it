<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SyncTimezoneFromSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        $settings = Setting::getSettings();
        $timezone = $settings?->timezone ?: config('app.timezone', 'UTC');

        if (!empty($timezone)) {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
            $this->syncDatabaseTimezone($timezone);
        }

        return $next($request);
    }

    protected function syncDatabaseTimezone(string $timezone): void
    {
        try {
            $driver = DB::connection()->getDriverName();

            if ($driver === 'mysql') {
                DB::statement('SET time_zone = ?', [$timezone]);

                return;
            }

            if ($driver === 'pgsql') {
                DB::statement("SET TIME ZONE '{$timezone}'");
            }
        } catch (\Throwable $e) {
            try {
                $offset = now($timezone)->format('P');
                $driver = DB::connection()->getDriverName();

                if ($driver === 'mysql') {
                    DB::statement('SET time_zone = ?', [$offset]);
                } elseif ($driver === 'pgsql') {
                    DB::statement("SET TIME ZONE '{$offset}'");
                }
            } catch (\Throwable $e) {
                // Ignore DB timezone sync failures and continue using PHP timezone settings.
            }
        }
    }
}
