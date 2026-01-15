<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
      //  $this->app->register(\Laravel\Passport\PassportServiceProvider::class);

    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);


    }
}
