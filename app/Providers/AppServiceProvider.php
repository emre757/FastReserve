<?php

namespace App\Providers;

use App\Contracts\Payments\PaymentAccountGateway;
use App\Contracts\Payments\PaymentGateway;
use App\Models\Offering;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Payments\Stripe\StripePaymentAccountGateway;
use App\Payments\Stripe\StripePaymentGateway;
use App\Support\Database\OrderedTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use LogicException;
use Stripe\StripeClient;

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

        $this->app->singleton(StripeClient::class, function (): StripeClient {
            $secret = config('services.stripe.secret');

            if (! is_string($secret) || $secret === '') {
                throw new LogicException('Stripe secret key is not configured.');
            }

            return new StripeClient($secret);
        });

        $this->app->bind(
            PaymentAccountGateway::class,
            StripePaymentAccountGateway::class,
        );

        $this->app->bind(
            PaymentGateway::class,
            StripePaymentGateway::class,
        );

        $this->app->scoped(
            OrderedTransaction::class,
            function (Application $app): OrderedTransaction {
                $database = $app->make(DatabaseManager::class);

                return new OrderedTransaction(
                    $database->connection(),
                    config('locking'),
                );
            },
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureMorphMap();

        Model::preventLazyLoading();
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
