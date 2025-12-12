<?php

namespace App\Providers;

use Carbon\Carbon;
use DateInterval;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\AuthCode;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Bridge\UserRepository;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Laravel\Passport\PersonalAccessClient;
use Laravel\Passport\Token;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\PasswordGrant;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') || $this->app->environment('staging')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Passport::useTokenModel(Token::class);
        Passport::useClientModel(Client::class);
        Passport::useAuthCodeModel(AuthCode::class);
        Passport::usePersonalAccessClientModel(PersonalAccessClient::class);

        Passport::tokensExpireIn(Carbon::now()->addHours(6));
        Passport::refreshTokensExpireIn(Carbon::now()->addMonth());

        $server = app(AuthorizationServer::class);

        $grant = new PasswordGrant(
            app(UserRepository::class),
            app(RefreshTokenRepository::class)
        );

        $grant->setRefreshTokenTTL(DateInterval::createFromDateString('1 month'));

        $server->enableGrantType(
            $grant,
            DateInterval::createFromDateString('10 hours')
        );
    }
}
