# Laravel TbcPay

[![Latest Stable Version](https://img.shields.io/packagist/v/lotuashvili/laravel-tbcpay.svg)](https://packagist.org/packages/lotuashvili/laravel-tbcpay)
[![Total Downloads](https://img.shields.io/packagist/dt/lotuashvili/laravel-tbcpay.svg)](https://packagist.org/packages/lotuashvili/laravel-tbcpay)
[![Downloads Month](https://img.shields.io/packagist/dm/lotuashvili/laravel-tbcpay.svg)](https://packagist.org/packages/lotuashvili/laravel-tbcpay)

This package allows you to use TBC payments in your Laravel application.

## Table of Contents

- [Installation](#installation)
- [Transaction types](#transaction-types-sms--dms)
- [Generating and placing a certificate](#generating-and-placing-a-certificate)
- [Environment](#environment)
- [Configuration](#configuration)
    - [Amount Unit](#amount-unit)
    - [Merchant URLs](#merchant-urls)
    - [Debug](#debug)
- [Usage](#usage)
    - [Routes and Controller](#setting-up-routes-and-controller)
    - [Payment](#payment)
    - [Other methods](#using-other-methods)
    - [Closing day with cron](#closing-day-with-cron)
- [Transactions History](#transactions-history)
- [Credits](#credits)

## Installation

```
composer require lotuashvili/laravel-tbcpay
```

#### For Laravel <= 5.4

If you're using Laravel 5.4 or lower, you have to manually add a service provider in your `config/app.php` file.
Open `config/app.php` and add `TbcPayServiceProvider` to the `providers` array.

```php
'providers' => [
    # Other providers
    Lotuashvili\LaravelTbcPay\TbcPayServiceProvider::class,
],
```

Then run:

```
php artisan vendor:publish --provider="Lotuashvili\LaravelTbcPay\TbcPayServiceProvider"
```

And run migrations:

```
php artisan migrate
```

## Transaction types (SMS / DMS)

There are two types of transaction within this system: **SMS** and **DMS**.

SMS - is a direct payment method, money is charged in 1 event, as soon as customer enters the credit card details and clicks proceed.  
DMS - is a two step method, first event blocks the money on the card (max 30 days), second event captures the money (second event can be carried out when product is shipped to the customer for example).

Every 24 hours, a merchant must close the business day.

## Generating and placing a certificate

TBC provides SSL certificate in **.p12** format, we need to transform it to **.pem** format. Use command below:

```
openssl pkcs12 -in *.p12 -out tbcpay.pem
```

After transformation, place certificate (**.pem** file) in `storage/certificates` folder. Name it whatever you want (tbcpay.pem in this example) and specify the name and password in `.env`.

```
TBCPAY_CERTIFICATE_NAME=tbcpay.pem
TBCPAY_CERTIFICATE_PASS=YourPassword123
```

## Environment

Set your environment variables:

**Note:** Specify only certificate file name instead of full path. Certificates are placed in `storage/certificates` folder.

Set default currency with ISO 4217 standart. List of all currencies: http://en.wikipedia.org/wiki/ISO_4217 (Default is 981 - GEL).

```
TBCPAY_DEBUG=true
TBCPAY_CERTIFICATE_NAME=tbcpay.pem
TBCPAY_CERTIFICATE_PASS=YourPassword123
TBCPAY_DEFAULT_CURRENCY=981
```

## Configuration

#### Amount unit

You can change default amount unit from configuration or `.env` file. Amount unit is used to multiply final amount before sending request to TBC. TBC bank requires amount in Cents (or Tetri) instead of USD (or GEL).

By default, unit is set to 1, so you can pass amount in GEL, for example `$amount = 300` will redirect to the checkout page with `300GEL` amount.

If you set unit to `100`, then you will be redirected to the checkout page with `3GEL` amount.

To change amount unit, set `TBCPAY_AMOUNT_UNIT=100` in `.env` file or change `amount_unit` directly in `config/tbc.php`.

#### Merchant URLs

If one day TBC updates merchant and submit URLs, you can simply override old URLs from `.env` or `config/tbc.php`.

Set `TBCPAY_MERCHANT_URL` and `TBCPAY_FORM_URL` in `.env` or `merchant_url` and `form_url` directly in `config/tbc.php`.

#### Debug

Simply enable debug from `.env` by setting `TBCPAY_DEBUG=true`, all logs will be in `tbc_logs` table by default. You can access them with `Lotuashvili\LaravelTbcPay\Models\TbcLog` model.

## Usage

### Setting up routes and controller

After publishing files, `app/Http/Controllers/TbcPayController` will be created. Feel free to modify controller as you want.

Open `routes/web.php` and define routes (Remove variables to use default values).

```php
<?php

use Lotuashvili\LaravelTbcPay\TbcPay;

// Other routes

TbcPay::routes(
    $controller, // Default: 'TbcPayController'
    $successMethod, // Default: 'success'
    $failMethod // Default: 'fail'
);
```

### Payment

In your controller, inject `\Lotuashvili\LaravelTbcPay\TbcPay` class to use payments.

```php
<?php

class PaymentController
{
    // Other methods
    
    public function pay(\Lotuashvili\LaravelTbcPay\TbcPay $tbcPay)
    {
        // OPTIONAL: Load model and start transaction by model
        // Model relation will be used in tbc_transactions table
        $model = User::first();

        return $tbcPay->init(
            100, // Amount
            981, // Currency (Optional)
            'Biller name', // Name of biller (Optional)
            'Website payment', // Message to be displayed on a payment page (Optional)
            'ge' // Language of the payment page (Optional)
        )->start($model) // Model parameter is optional
         ->sms() // Optional: Specify transaction type (Supported: sms and dms)
         ->view(); // Return final view of TBC payment to redirect to a payment page
    }
}
```

After returning view, website will be redirected to the payment page. After filling card details, you will be redirected to the success or fail url, that you've specified previously to TBC.

### Using other methods

If you have started DMS authorization (blocked money on account), then you have to actually bill blocked amount after some period of time. You will need `makeDms()` function for that.

You can directly access core processor's methods by calling for example `$tbcPay->reverse_transaction()` (Using magic __call function). For all methods, please check [PlugAndPay TBC Processor's documentation](https://github.com/plugandpay/tbc-credit-card-payment-gateway-php-lib)

### Closing day with cron

If you don't have cron jobs running in your application, please check out [Laravel's documentation](https://laravel.com/docs/5.8/scheduling).

Register command in your `app/Console/Kernel.php`.

```php
protected $commands = [
    // Other commands
    \Lotuashvili\LaravelTbcPay\Commands\CloseDay::class,
];
```

Then add command to schedule (for example use `everyday()`, it will run command everyday at 00:00).

```php
protected function schedule(Schedule $schedule)
{
    // Other commands
    $schedule->command('tbcpay:close-day')
             ->everyday();
}
```

## Transactions history

All transactions will be available in `tbc_transactions` table by default. You can access them with `Lotuashvili\LaravelTbcPay\Models\TbcTransaction` model.

If you have enabled `debug`, then all records with be available in `tbc_logs` table and can access them with `Lotuashvili\LaravelTbcPay\Models\TbcLog` model.

## Credits

- [Levan Lotuashvili](https://github.com/lotuashvili)
- [Sandro Dzneladze - Plug and Pay](https://github.com/plugandpay)
- [All Contributors](../../contributors)
