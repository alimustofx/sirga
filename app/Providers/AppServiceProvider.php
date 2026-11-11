<?php

namespace App\Providers;

use App\Support\Audit;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Date::use(CarbonImmutable::class);
        DB::prohibitDestructiveCommands(app()->isProduction());

        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        Event::listen(Login::class, function (Login $e) {
            $e->user->forceFill(['last_login_at' => now()])->save();
            Audit::log('auth.login', null, null, ['nip' => $e->user->nip], null, $e->user);
        });
        Event::listen(Logout::class, function (Logout $e) {
            if ($e->user) {
                Audit::log('auth.logout', null, null, null, null, $e->user);
            }
        });
        Event::listen(Failed::class, function (Failed $e) {
            Audit::log('auth.failed', null, null, ['nip' => (string) ($e->credentials['nip'] ?? '')]);
        });
    }
}
