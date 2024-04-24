<?php

namespace App\Listeners;

use App\Account;

use App\Transaction;

use App\Utils\ModuleUtil;
use App\AccountTransaction;
use App\TransactionPayment;
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
        // dd($event);
        // echo "<pre>";
        // print_r($event->transactionPayment->toArray());
        // exit;
        if ($event->transactionPayment->method == 'advance') {
            $this->transactionUtil->updateContactBalance($event->transactionPayment->payment_for, $event->transactionPayment->amount, 'deduct');
        }

        if (!$this->moduleUtil->isModuleEnabled('account', $event->transactionPayment->business_id)) {
            return true;
        }

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
            
            $this->sellAcountTransaction($event);

            $sellTransaction = Account::select('id', 'name')->where('name', 'like', '%receiable%')
            ->whereHas('account_type', function ($query) {
                $query->where('name', 'assets');
            })
            ->first();
            
            $checkamount = AccountTransaction::where('transaction_id',$event->transactionPayment->transaction_id)
            ->where('account_id',$sellTransaction->id)
            ->where('type','credit')
            ->first();

            $paymentTransaction = TransactionPayment::where('transaction_id',$event->transactionPayment->transaction_id)
            ->sum('amount');
            
            $paid = Transaction::select('id','payment_status','final_total')->where('id',$event->transactionPayment->transaction_id)->first();

            if($paid->payment_status == 'partial' && $checkamount->amount >= $paymentTransaction && $event->total_sell == null){
                $this-> accountReceiableTransaction($event);               
            }
            if($paid->payment_status == 'paid' && $checkamount->amount <= $paymentTransaction && $event->total_sell == null){
                $this-> accountReceiableTransaction($event);               
            }

        }
    }

    //For Sales and Account Receiable
    private function sellAcountTransaction($event)
    {
        $payment_status = $this->transactionUtil->updatePaymentStatus($event->transactionPayment->transaction_id, $event->total_sell);
        if ($event->formInput['transaction_type'] == 'sell') {
            $sellTransaction = Account::select('id', 'name')->where('name', 'like', '%sales%')
            ->whereHas('account_type', function ($query) {
                $query->where('name', 'sales');
            })
            ->first();
            $checkselltransaction = AccountTransaction::where('transaction_id',$event->transactionPayment->transaction_id)->where('account_id',$sellTransaction->id)->first();
            if(empty($checkselltransaction)){
                $account_transaction_data = [
                    'amount' => $event->total_sell,
                    'account_id' => $sellTransaction->id,
                    'type' => 'credit',
                    'operation_date' => $event->transactionPayment->paid_on,
                    'created_by' => $event->transactionPayment->created_by,
                    'transaction_id' => $event->transactionPayment->transaction_id,
                    'transaction_payment_id' =>  $event->transactionPayment->id
                ];
                $account_transaction_data['type'] = 'debit';
                if ($event->formInput['amount'] != $event->total_sell && $payment_status == 'partial') {
                    $account_transaction_data['amount'] = $event->total_sell;
                }
                
                if ($event->total_sell != null) {
                    AccountTransaction::createAccountTransaction($account_transaction_data);
                }
            }
            if ($payment_status == 'partial') {
                $sellTransaction = Account::select('id', 'name')->where('name', 'like', '%receiable%')
                ->whereHas('account_type', function ($query) {
                    $query->where('name', 'assets');
                })
                ->first();
                $checkaccounttransaction = AccountTransaction::where('transaction_id',$event->transactionPayment->transaction_id)->where('account_id',$sellTransaction->id)->
                where('type','credit')->first();
                if(empty($checkaccounttransaction)){
                    $account_transaction_data = [
                        'amount' => $event->total_sell - $event->formInput['amount'],
                        'account_id' => $sellTransaction->id,
                        'type' => 'credit',
                        'operation_date' => $event->transactionPayment->paid_on,
                        'created_by' => $event->transactionPayment->created_by,
                        'transaction_id' => $event->transactionPayment->transaction_id,
                        'transaction_payment_id' =>  $event->transactionPayment->id
                    ];
                    AccountTransaction::createAccountTransaction($account_transaction_data);
                }
            }
        }
    }

    // For Cash and Account Receiable
    private function accountReceiableTransaction($event){
        // dd($event);
        $sellTransaction = Account::select('id', 'name')->where('name', 'like', '%receiable%')
        ->whereHas('account_type', function ($query) {
            $query->where('name', 'assets');
        })
        ->first();
        if ($event->formInput['transaction_type'] == 'sell') {
            $account_transaction_data = [
                'amount' => $event->formInput['amount'],
                'account_id' => $sellTransaction->id,
                'type' => 'debit',
                'operation_date' => $event->transactionPayment->paid_on,
                'created_by' => $event->transactionPayment->created_by,
                'transaction_id' => $event->transactionPayment->transaction_id,
                'transaction_payment_id' =>  $event->transactionPayment->id
            ];
            AccountTransaction::createAccountTransaction($account_transaction_data);
        }
    }
}
