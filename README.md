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
- [Result Codes](#result-codes)
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

By default, unit is set to 100, so you can pass amount in GEL, for example `$amount = 300` will redirect to the checkout page with `300GEL` amount.

If you set unit to `1`, then you will be redirected to the checkout page with `3GEL` amount.

To change amount unit, set `TBCPAY_AMOUNT_UNIT=1` in `.env` file or change `amount_unit` directly in `config/tbc.php`.

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

**NOTE:** Exclude `tbcpay/*` routes from CSRF Token verification to avoid `TokenMismatchException` on success/fail URLs.

Add urls in your `app/Http/Middleware/VerifyCsrtToken.php`:

```php
<?php

class VerifyCsrfToken extends Middleware
{
    // Other variables

    protected $except = [
        'tbcpay/*',
    ];
}
```

Send success and fail URLs to TBC:

Success: `http://website.ge/tbcpay/success`

Fail: `http://website.ge/tbcpay/fail`

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

## Result Codes

| Key | Value             | Description                                                                           |
|-----|-------------------|---------------------------------------------------------------------------------------|
| 000 | Approved          | Approved                                                                              |
| 001 | Approved with ID  | Approved, honour with identification                                                  |
| 002 | Approved          | Approved for partial amount                                                           |
| 003 | Approved          | Approved for VIP                                                                      |
| 004 | Approved          | Approved, update track 3                                                              |
| 005 | Approved          | Approved, account type specified by card issuer                                       |
| 006 | Approved          | Approved for partial amount, account type specified by card issuer                    |
| 007 | Approved          | Approved, update ICC                                                                  |
| 100 | Decline           | Decline (general, no comments)                                                        |
| 101 | Decline           | Decline, expired card                                                                 |
| 102 | Decline           | Decline, suspected fraud                                                              |
| 103 | Decline           | Decline, card acceptor contact acquirer                                               |
| 104 | Decline           | Decline, restricted card                                                              |
| 105 | Decline           | Decline, card acceptor call acquirer's security department                            |
| 106 | Decline           | Decline, allowable PIN tries exceeded                                                 |
| 107 | Decline           | Decline, refer to card issuer                                                         |
| 108 | Decline           | Decline, refer to card issuer's special conditions                                    |
| 109 | Decline           | Decline, invalid merchant                                                             |
| 110 | Decline           | Decline, invalid amount                                                               |
| 111 | Decline           | Decline, invalid card number                                                          |
| 112 | Decline           | Decline, PIN data required                                                            |
| 113 | Decline           | Decline, unacceptable fee                                                             |
| 114 | Decline           | Decline, no account of type requested                                                 |
| 115 | Decline           | Decline, requested function not supported                                             |
| 116 | Decline, no funds | Decline, not sufficient funds                                                         |
| 117 | Decline           | Decline, incorrect PIN                                                                |
| 118 | Decline           | Decline, no card record                                                               |
| 119 | Decline           | Decline, transaction not permitted to cardholder                                      |
| 120 | Decline           | Decline, transaction not permitted to terminal                                        |
| 121 | Decline           | Decline, exceeds withdrawal amount limit                                              |
| 122 | Decline           | Decline, security violation                                                           |
| 123 | Decline           | Decline, exceeds withdrawal frequency limit                                           |
| 124 | Decline           | Decline, violation of law                                                             |
| 125 | Decline           | Decline, card not effective                                                           |
| 126 | Decline           | Decline, invalid PIN block                                                            |
| 127 | Decline           | Decline, PIN length error                                                             |
| 128 | Decline           | Decline, PIN kay synch error                                                          |
| 129 | Decline           | Decline, suspected counterfeit card                                                   |
| 180 | Decline           | Decline, by cardholders wish                                                          |
| 200 | Pick-up           | Pick-up (general, no comments)                                                        |
| 201 | Pick-up           | Pick-up, expired card                                                                 |
| 202 | Pick-up           | Pick-up, suspected fraud                                                              |
| 203 | Pick-up           | Pick-up, card acceptor contact card acquirer                                          |
| 204 | Pick-up           | Pick-up, restricted card                                                              |
| 205 | Pick-up           | Pick-up, card acceptor call acquirer's security department                            |
| 206 | Pick-up           | Pick-up, allowable PIN tries exceeded                                                 |
| 207 | Pick-up           | Pick-up, special conditions                                                           |
| 208 | Pick-up           | Pick-up, lost card                                                                    |
| 209 | Pick-up           | Pick-up, stolen card                                                                  |
| 210 | Pick-up           | Pick-up, suspected counterfeit card                                                   |
| 300 | Call acquirer     | Status message: file action successful                                                |
| 301 | Call acquirer     | Status message: file action not supported by receiver                                 |
| 302 | Call acquirer     | Status message: unable to locate record on file                                       |
| 303 | Call acquirer     | Status message: duplicate record, old record replaced                                 |
| 304 | Call acquirer     | Status message: file record field edit error                                          |
| 305 | Call acquirer     | Status message: file locked out                                                       |
| 306 | Call acquirer     | Status message: file action not successful                                            |
| 307 | Call acquirer     | Status message: file data format error                                                |
| 308 | Call acquirer     | Status message: duplicate record, new record rejected                                 |
| 309 | Call acquirer     | Status message: unknown file                                                          |
| 400 | Accepted          | Accepted (for reversal)                                                               |
| 499 | Approved          | Approved, no original message data                                                    |
| 500 | Call acquirer     | Status message: reconciled, in balance                                                |
| 501 | Call acquirer     | Status message: reconciled, out of balance                                            |
| 502 | Call acquirer     | Status message: amount not reconciled, totals provided                                |
| 503 | Call acquirer     | Status message: totals for reconciliation not available                               |
| 504 | Call acquirer     | Status message: not reconciled, totals provided                                       |
| 600 | Accepted          | Accepted (for administrative info)                                                    |
| 601 | Call acquirer     | Status message: impossible to trace back original transaction                         |
| 602 | Call acquirer     | Status message: invalid transaction reference number                                  |
| 603 | Call acquirer     | Status message: reference number/PAN incompatible                                     |
| 604 | Call acquirer     | Status message: POS photograph is not available                                       |
| 605 | Call acquirer     | Status message: requested item supplied                                               |
| 606 | Call acquirer     | Status message: request cannot be fulfilled - required documentation is not available |
| 680 | List ready        | List ready                                                                            |
| 681 | List not ready    | List not ready                                                                        |
| 700 | Accepted          | Accepted (for fee collection)                                                         |
| 800 | Accepted          | Accepted (for network management)                                                     |
| 900 | Accepted          | Advice acknowledged, no financial liability accepted                                  |
| 901 | Accepted          | Advice acknowledged, finansial liability accepted                                     |
| 902 | Call acquirer     | Decline reason message: invalid transaction                                           |
| 903 | Call acquirer     | Status message: re-enter transaction                                                  |
| 904 | Call acquirer     | Decline reason message: format error                                                  |
| 905 | Call acquirer     | Decline reason message: acqiurer not supported by switch                              |
| 906 | Call acquirer     | Decline reason message: cutover in process                                            |
| 907 | Call acquirer     | Decline reason message: card issuer or switch inoperative                             |
| 908 | Call acquirer     | Decline reason message: transaction destination cannot be found for routing           |
| 909 | Call acquirer     | Decline reason message: system malfunction                                            |
| 910 | Call acquirer     | Decline reason message: card issuer signed off                                        |
| 911 | Call acquirer     | Decline reason message: card issuer timed out                                         |
| 912 | Call acquirer     | Decline reason message: card issuer unavailable                                       |
| 913 | Call acquirer     | Decline reason message: duplicate transmission                                        |
| 914 | Call acquirer     | Decline reason message: not able to trace back to original transaction                |
| 915 | Call acquirer     | Decline reason message: reconciliation cutover or checkpoint error                    |
| 916 | Call acquirer     | Decline reason message: MAC incorrect                                                 |
| 917 | Call acquirer     | Decline reason message: MAC key sync error                                            |
| 918 | Call acquirer     | Decline reason message: no communication keys available for use                       |
| 919 | Call acquirer     | Decline reason message: encryption key sync error                                     |
| 920 | Call acquirer     | Decline reason message: security software/hardware error - try again                  |
| 921 | Call acquirer     | Decline reason message: security software/hardware error - no action                  |
| 922 | Call acquirer     | Decline reason message: message number out of sequence                                |
| 923 | Call acquirer     | Status message: request in progress                                                   |
| 950 | Not accepted      | Decline reason message: violation of business arrangement                             |
| XXX | Undefined         | Code to be replaced by card status code or stoplist insertion reason code             |

## Credits

- [Levan Lotuashvili](https://github.com/lotuashvili)
- [Sandro Dzneladze - Plug and Pay](https://github.com/plugandpay)
- [All Contributors](../../contributors)
