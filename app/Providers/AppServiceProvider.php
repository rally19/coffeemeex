<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(\FluxPro\FluxProServiceProvider::class);
        $this->registerHelpers();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    protected function registerHelpers()
    {
        $helperFile = app_path('Helpers/FunctionHelper.php');
        if (file_exists($helperFile)) {
            require_once $helperFile;
        }
    }
}
