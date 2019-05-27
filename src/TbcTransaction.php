<?php

namespace Lotuashvili\LaravelTbcPay\Models;

use Illuminate\Database\Eloquent\Model;

class TbcTransaction extends Model
{
    /**
     * @var array Fillable Attributes
     */
    protected $fillable = [
        'locale',
        'model_id',
        'model_type',
        'amount',
        'currency',
        'trans_id',
        'is_paid',
        'result_code',
        'card_number',
        'completed_at',
    ];

    /**
     * @var array Attribute Casting
     */
    protected $casts = [
        'is_paid' => 'boolean',
    ];

    /**
     * @var array Date Attributes
     */
    protected $dates = [
        'completed_at',
    ];

    /**
     * TbcTransaction constructor
     * Set table name from config
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('tbc.transactions_table', 'tbc_transactions');
    }

    /**
     * Load model relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function model()
    {
        return $this->morphTo();
    }
}
