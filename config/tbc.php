<?php

return [

    /*
     * Debug transactions (Log every action in database)
     */
    'debug' => env('TBCPAY_DEBUG', true),

    /*
     * Table name where all transactions log goes
     */
    'transactions_table' => 'tbc_transactions',

    /*
     * Table name where all debug information goes
     */
    'logs_table' => 'tbc_logs',

    /*
     * Amount unit
     * Final amount is multiplied by this unit
     *
     * Supported:
     * Lari (GEL) = 100
     * Tetri = 1
     */
    'amount_unit' => env('TBCPAY_AMOUNT_UNIT', 1),

    /*
     * Certificate path and passphrase
     */
    'certificate' => [
        'path' => storage_path('certificates/' . env('TBCPAY_CERTIFICATE_NAME', 'tbcpay.pem')),
        'pass' => env('TBCPAY_CERTIFICATE_PASS'),
    ],

    /*
     * TBC's merchant url
     */
    'merchant_url' => env('TBCPAY_MERCHANT_URL', 'https://securepay.ufc.ge:18443/ecomm2/MerchantHandler'),

    /*
     * Form submit url
     */
    'form_url' => env('TBCPAY_FORM_URL', 'https://securepay.ufc.ge/ecomm2/ClientHandler'),

    /*
     * Default currency code (ISO 4217)
     * http://en.wikipedia.org/wiki/ISO_4217
     * GEL = 981
     */
    'default_currency_code' => env('TBCPAY_DEFAULT_CURRENCY', 981),

    /*
     * Default message to be displayed on a TBC's payment page
     */
    'default_message' => 'Website payment',

];
