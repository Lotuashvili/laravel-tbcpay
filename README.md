# Laravel TbcPay

This package allows you to use TBC payments in your Laravel application.

## Table of Contents

- [Installation](#installation)
- [Generating and placing a certificate](#generating-and-placing-a-certificate)
- [Environment](#environment)
- [Configuration](#configuration)
- [Transactions History](#transactions-history)

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

## Transactions history

All transactions will be available in `tbc_transactions` table by default. You can access them with `Lotuashvili\LaravelTbcPay\Models\TbcTransaction` model.
