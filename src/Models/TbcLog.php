<?php

namespace Lotuashvili\LaravelTbcPay\Models;

use Illuminate\Database\Eloquent\Model;

class TbcLog extends Model
{
    /**
     * @var array Fillable Attributes
     */
    protected $fillable = [
        'transaction_id',
        'status',
        'message',
        'payload',
    ];

    /**
     * @var array Attribute Casting
     */
    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * TbcLog constructor
     * Set table name from config
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('tbc.logs_table', 'tbc_logs');
    }

    /**
     * Load transaction relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transaction()
    {
        return $this->belongsTo(TbcTransaction::class);
    }
}
