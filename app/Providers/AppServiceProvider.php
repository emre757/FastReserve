<?php

namespace App\Providers;

use App\Models\Offering;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (
            $this->app->environment('local')
            && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)
        ) {
            $this->app->register(
                \Laravel\Telescope\TelescopeServiceProvider::class,
            );

            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureMorphMap();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function configureMorphMap(): void
    {
        Relation::enforceMorphMap([
            'offering' => Offering::class,
            'team' => Team::class,
            'team_invitation' => TeamInvitation::class,
            'user' => User::class,
        ]);
    }
}
