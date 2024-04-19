<?php

namespace App\Listeners;

use App\Account;

use App\Utils\ModuleUtil;

use App\AccountTransaction;
use App\Utils\TransactionUtil;
use App\Events\TransactionPaymentAdded;

class AddAccountTransaction
{
    protected $transactionUtil;
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param TransactionUtil $transactionUtil
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil, ModuleUtil $moduleUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(TransactionPaymentAdded $event)
    {
        // echo "<pre>";print_r($event->transactionPayment->toArray());exit;
        if ($event->transactionPayment->method == 'advance') {
            $this->transactionUtil->updateContactBalance($event->transactionPayment->payment_for, $event->transactionPayment->amount, 'deduct');
        }
        
        if (!$this->moduleUtil->isModuleEnabled('account', $event->transactionPayment->business_id)) {
            return true;
        }

        $payment_status = $this->transactionUtil->updatePaymentStatus($event->transactionPayment->transaction_id, $event->total_sell);

        //Create new account transaction
        if (!empty($event->formInput['account_id']) && $event->transactionPayment->method != 'advance') {
            $type = !empty($event->transactionPayment->payment_type) ? $event->transactionPayment->payment_type : AccountTransaction::getAccountTransactionType($event->formInput['transaction_type']);
            $account_transaction_data = [
                'amount' => $event->formInput['amount'],
                'account_id' => $event->formInput['account_id'],
                'type' => $type,
                'operation_date' => $event->transactionPayment->paid_on,
                'created_by' => $event->transactionPayment->created_by,
                'transaction_id' => $event->transactionPayment->transaction_id,
                'transaction_payment_id' =>  $event->transactionPayment->id
            ];

            //If change return then set type as debit
            if ($event->formInput['transaction_type'] == 'sell' && isset($event->formInput['is_return']) && $event->formInput['is_return'] == 1) {
                $account_transaction_data['type'] = 'debit';
            }
            AccountTransaction::createAccountTransaction($account_transaction_data);
            
            if($event->formInput['transaction_type'] == 'sell'){
                $sellTransaction = Account::select('id','name')->where('name', 'like', '%sales%')
                ->whereHas('account_type', function($query) {
                    $query->where('name', 'sales');
                })
                ->first();
                $account_transaction_data['type'] = 'debit';
                if($event->formInput['amount'] != $event->total_sell && $payment_status == 'partial'){
                    $account_transaction_data['amount'] = $event->total_sell;
                }

                if($event->total_sell != null){
                    $account_transaction_data['account_id'] = $sellTransaction->id;
                }

                // dd($account_transaction_data);
                AccountTransaction::createAccountTransaction($account_transaction_data);

                if($payment_status == 'partial'){
                    $sellTransaction = Account::select('id','name')->where('name', 'like', '%receiable%')
                    ->whereHas('account_type', function($query) {
                        $query->where('name', 'assets');
                    })
                    ->first();
                    $account_transaction_data['type'] = 'credit';
                    $account_transaction_data['amount'] = $event->total_sell - $event->formInput['amount'];
                    $account_transaction_data['account_id'] = $sellTransaction->id;
                    AccountTransaction::createAccountTransaction($account_transaction_data);
                    // dd($account_transaction_data);
                }else{

                }
            }
            dd('transaction done hogye han');


        }
    }
}
