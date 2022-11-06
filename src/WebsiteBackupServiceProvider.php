<?php

namespace Backdoor\WebsiteBackup;

use Backdoor\WebsiteBackup\WebsiteBackup;
use Illuminate\Support\ServiceProvider;

class WebsiteBackupServiceProvider extends ServiceProvider {

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('WebsiteBackup', function ($app) {
            return new WebsiteBackup;
        });
    }
}

