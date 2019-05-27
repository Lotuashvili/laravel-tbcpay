<?php

namespace Lotuashvili\LaravelTbcPay;

use Illuminate\Database\Eloquent\Model;
use Lotuashvili\LaravelTbcPay\Models\TbcTransaction;
use WeAreDe\TbcPay\TbcPayProcessor;

class TbcPay
{
    /**
     * @var TbcPayProcessor
     */
    public $processor;

    /**
     * @var array
     */
    protected $start;

    /**
     * @var string Transaction ID
     */
    protected $trans_id;

    /**
     * TbcPay constructor.
     * Initialize TbcPayProcessor and set merchant url
     */
    public function __construct()
    {
        $this->processor = new TbcPayProcessor(config('tbc.cert_path'), config('tbc.cert_pass'), request()->ip());
        $this->processor->submit_url = config('tbc.merchant_url', 'https://securepay.ufc.ge:18443/ecomm2/MerchantHandler');
    }

    /**
     * Return raw processor
     *
     * @return TbcPayProcessor
     */
    public function raw()
    {
        return $this->processor;
    }

    /**
     * Initialize payment
     *
     * @param float $amount
     * @param int|null $currency
     * @param string|null $message
     * @param string|null $lang
     * @return $this
     */
    public function init(float $amount = 1, int $currency = null, string $message = null, string $lang = null)
    {
        $lang = strtoupper($lang ?: app()->getLocale());

        // Set language to 'GE' instead of 'KA' (TBC supports GE)
        $language = $lang == 'KA' ? 'GE' : $lang;

        $this->processor->amount = $amount * config('tbc.amount_unit', 1);
        $this->processor->currency = $currency ?: config('tbc.default_currency_code');
        $this->processor->description = $message ?: config('tbc.default_message');
        $this->processor->language = $language;

        return $this;
    }

    /**
     * Start a SMS transaction
     *
     * @param Model $model
     * @return $this|bool
     */
    public function start(Model $model)
    {
        $start = $this->processor->sms_start_transaction();

        if (isset($start['error'])) {
            // Log::error($start['error']);
            return false;
        } else if (isset($start['TRANSACTION_ID'])) {
            $this->start = $start;
            $this->trans_id = $start['TRANSACTION_ID'];

            TbcTransaction::create([
                'locale' => app()->getLocale(),
                'amount' => $this->processor->amount / config('tbc.amount_unit', 1), // Divide to display amount in GEL instead of Tetri
                'currency' => $this->processor->currency,
                'model_id' => data_get($model, 'id'),
                'model_type' => get_class($model),
                'trans_id' => $this->trans_id,
            ]);

            // Log::debug('[PAYMENT] Starting. Transaction ID: ' . $this->trans_id);
        }

        return $this;
    }

    /**
     * Payment view
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function view()
    {
        return view('tbcpay::tbc_start', ['start' => $this->start]);
    }

    /**
     * Check if transaction is completed and update status in database
     *
     * @param null $trans_id
     * @param bool $rawPayload
     * @return array|bool
     */
    public function isOk($trans_id = null, $rawPayload = false)
    {
        $result = $this->processor->get_transaction_result($trans_id ?: $this->trans_id);

        // Log::debug('[PAYMENT] OK. Transaction ID: ' . $trans_id);
        // Log::debug('[PAYMENT] OK. Transaction Result: ' . json_encode($result));

        $transaction = TbcTransaction::where('trans_id', $trans_id)->firstOrFail();

        $transaction->update([
            'is_paid' => data_get($result, 'RESULT') == 'OK',
            'result_code' => data_get($result, 'RESULT_CODE'),
            'card_number' => data_get($result, 'CARD_NUMBER'),
            'completed_at' => now(),
        ]);

        return $rawPayload ? $result : (data_get($result, 'RESULT') == 'OK');
    }

    /**
     * Close day
     *
     * @return $this
     */
    public function close()
    {
        $result = $this->processor->close_day();
        // Log::info('[PAYMENT] Closing day (' . json_encode($result) . ')');

        return $this;
    }

    /**
     * Fail handler
     *
     * @return bool
     */
    public static function fail()
    {
        // Log::useFiles(storage_path() . '/logs/payment.log', 'error');
        // Log::error('[PAYMENT] Fail');

        return true;
    }
}
