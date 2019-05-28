<?php

namespace Lotuashvili\LaravelTbcPay\Commands;

use Illuminate\Console\Command;
use Lotuashvili\LaravelTbcPay\TbcPay;

class CloseDay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tbcpay:close-day';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close TBC\'s business day';

    /**
     * @var TbcPay
     */
    protected $tbc;

    /**
     * Create a new command instance.
     *
     * @param TbcPay $tbc
     */
    public function __construct(TbcPay $tbc)
    {
        parent::__construct();

        $this->tbc = $tbc;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->tbc->close();

        return $this->info('Business day closed successfully');
    }
}
