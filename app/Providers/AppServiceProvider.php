<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

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
        $this->configureCommands();
        $this->configureModels();
        $this->configureDates();
        $this->configureUrl();
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
