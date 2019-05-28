<?php

namespace Lotuashvili\LaravelTbcPay;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Model;
use Lotuashvili\LaravelTbcPay\Models\TbcLog;
use Lotuashvili\LaravelTbcPay\Models\TbcTransaction;
use WeAreDe\TbcPay\TbcPayProcessor;

class TbcPay
{
    /**
     * @var TbcPayProcessor
     */
    protected $processor;

    /**
     * @var array
     */
    protected $start;

    /**
     * @var string Transaction ID
     */
    protected $trans_id;

    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * @var string Transaction Type
     */
    protected $type = 'SMS';

    /**
     * TbcPay constructor.
     * Initialize TbcPayProcessor and set merchant url
     */
    public function __construct()
    {
        $this->processor = new TbcPayProcessor(config('tbc.certificate.path'), config('tbc.certificate.pass'), request()->ip());
        $this->processor->submit_url = config('tbc.merchant_url', 'https://securepay.ufc.ge:18443/ecomm2/MerchantHandler');
        $this->debug = config('tbc.debug');
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
     * @param string|null $biller
     * @param string|null $message
     * @param string|null $lang
     * @return $this
     */
    public function init(float $amount = 1, int $currency = null, string $biller = null, string $message = null, string $lang = null)
    {
        $lang = strtoupper($lang ?: app()->getLocale());

        // Set language to 'GE' instead of 'KA' (TBC supports GE)
        $language = $lang == 'KA' ? 'GE' : $lang;

        $this->processor->amount = $amount * config('tbc.amount_unit', 1);
        $this->processor->currency = $currency ?: config('tbc.default_currency_code');
        $this->processor->description = $message ?: config('tbc.default_message');
        $this->processor->biller = $biller;
        $this->processor->language = $language;

        return $this;
    }

    /**
     * Set transaction type to SMS
     *
     * @return $this
     */
    public function sms()
    {
        $this->type = 'SMS';

        return $this;
    }

    /**
     * Set transaction type to DMS
     *
     * @return $this
     */
    public function dms()
    {
        $this->type = 'DMS';

        return $this;
    }

    /**
     * Start a transaction based on type
     *
     * @param Model $model
     * @return $this|bool
     */
    public function start(Model $model)
    {
        $type = $this->type;

        if ($this->debug) {
            TbcLog::create([
                'message' => "Starting $type transaction on model " . get_class($model) . ' #' . $model->id,
            ]);
        }

        $method = $type === 'SMS' ? 'sms_start_transaction' : 'dms_start_authorization';

        $start = $this->processor->$method();

        if (isset($start['error'])) {
            if ($this->debug) {
                TbcLog::create([
                    'status' => 0,
                    'message' => "Starting $type transaction failed. " . $start['error'],
                    'payload' => $start,
                ]);
            }

            return false;
        } else if (isset($start['TRANSACTION_ID'])) {
            $this->start = $start;
            $this->trans_id = $start['TRANSACTION_ID'];

            $transaction = TbcTransaction::create([
                'locale' => app()->getLocale(),
                'amount' => $this->processor->amount / config('tbc.amount_unit', 1), // Divide to display amount in GEL instead of Tetri
                'type' => $type,
                'currency' => $this->processor->currency,
                'model_id' => data_get($model, 'id'),
                'model_type' => get_class($model),
                'trans_id' => $this->trans_id,
            ]);

            if ($this->debug) {
                TbcLog::create([
                    'transaction_id' => $transaction->id,
                    'message' => $type . ' transaction started: ' . $this->trans_id,
                    'payload' => $start,
                ]);
            }
        }

        return $this;
    }

    /**
     * Make DMS transaction
     * Charge user after blocking amount
     *
     * @param string $trans_id
     * @param bool $rawPayload
     * @return array|bool
     */
    public function makeDms(string $trans_id, bool $rawPayload = false)
    {
        $this->processor->dms_make_transaction($trans_id);

        return $this->isOk($trans_id, $rawPayload);
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
     * @param string $trans_id
     * @param bool $rawPayload
     * @return array|bool
     */
    public function isOk(string $trans_id = null, bool $rawPayload = false)
    {
        $id = $trans_id ?: $this->trans_id;

        if ($this->debug) {
            TbcLog::create([
                'message' => 'Checking status of transaction: ' . $id,
            ]);
        }

        $result = $this->processor->get_transaction_result($id);

        $transaction = TbcTransaction::where('trans_id', $id)->firstOrFail();

        if ($this->debug) {
            TbcLog::create([
                'transaction_id' => $transaction->id,
                'message' => 'Transaction result: ' . $id,
                'payload' => $result,
            ]);
        }

        $isOk = data_get($result, 'RESULT') == 'OK';

        $transaction->update([
            'is_paid' => $isOk,
            'result_code' => data_get($result, 'RESULT_CODE'),
            'card_number' => data_get($result, 'CARD_NUMBER'),
            'completed_at' => now(),
        ]);

        if ($this->debug) {
            TbcLog::create([
                'status' => $isOk,
                'transaction_id' => $transaction->id,
                'message' => ($isOk ? 'Transaction marked as paid' : 'Transaction unsuccessful') . ': ' . $id,
                'payload' => $result,
            ]);
        }

        return $rawPayload ? $result : $isOk;
    }

    /**
     * Close day
     *
     * @return $this
     */
    public function close()
    {
        $result = $this->processor->close_day();

        if ($this->debug) {
            TbcLog::create([
                'message' => 'Day closed',
                'payload' => $result,
            ]);
        }

        return $this;
    }

    /**
     * Fail handler
     *
     * @return bool
     */
    public function fail()
    {
        if ($this->debug) {
            TbcLog::create([
                'status' => 0,
                'message' => 'Transaction failed.',
            ]);
        }

        return true;
    }

    /**
     * Use TbcPayProcessor's native functions directly with magic function
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->processor, $name)) {
            return $this->processor->$name($arguments);
        }

        throw new BadMethodCallException("Method [{$name}] does not exist");
    }
}
