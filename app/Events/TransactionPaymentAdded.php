<?php

namespace App\Events;

use App\TransactionPayment;
use Illuminate\Queue\SerializesModels;

class TransactionPaymentAdded
{
    use SerializesModels;

    public $transactionPayment;
    public $formInput;
    public $total_sell;

    /**
     * Create a new event instance.
     *
     * @param  Order  $order
     * @param  array $formInput = []
     * @return void
     */
    public function __construct(TransactionPayment $transactionPayment, $formInput = [] , $total_sell)
    {
        $this->transactionPayment = $transactionPayment;
        $this->formInput = $formInput;
        $this->total_sell = $total_sell;
    }
}
