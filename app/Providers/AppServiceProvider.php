<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        Scramble::afterOpenApiGenerated(function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::apiKey('header', 'Authorization')
                    ->setDescription('Example: `Authorization: Bearer  {YOUR_API_TOKEN}` .')
                    ->default()
            );
        });

        $this->configureCommands();
        $this->configureModels();
        $this->configureDates();
        $this->configureUrl();

        Gate::define('viewApiDocs', fn ($user) => true);
    }


    public function configureUrl(): void
    {
        URL::forceHttps(app()->isProduction());
    }

    private function configureDates()
    {
        Date::use(CarbonImmutable::class);
    }
    private function configureCommands()
    {
        DB::prohibitDestructiveCommands(App::isProduction());
    }

    private function configureModels()
    {
        Model::shouldBeStrict();
        Model::unguard();
    }
}
