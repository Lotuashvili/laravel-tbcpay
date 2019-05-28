<?php

namespace Lotuashvili\LaravelTbcPay;

use Illuminate\Support\ServiceProvider;

class TbcPayServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/tbc.php' => config_path('tbc.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../database/migrations/create_tbc_transactions_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time() - 10) . '_create_tbc_transactions_table.php'),
            __DIR__ . '/../database/migrations/create_tbc_logs_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_tbc_logs_table.php'),
        ], 'migrations');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/tbcpay'),
        ], 'views');

        $this->publishes([
            __DIR__ . '/../controllers/TbcPayController.php.stub' => app_path('Http/Controllers/TbcPayController.php'),
        ], 'controllers');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'tbcpay');
    }
}
