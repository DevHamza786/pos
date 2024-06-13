@extends('layouts.app')
@section('title', __('account.balance_sheet'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('account.balance_sheet')
        </h1>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row no-print">
            <div class="col-sm-12">
                @component('components.filters', ['title' => __('report.filters')])
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('bal_sheet_location_id', __('purchase.business_location') . ':') !!}
                            {!! Form::select('bal_sheet_location_id', $business_locations, null, [
                                'class' => 'form-control select2',
                                'style' => 'width:100%',
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-sm-3 col-xs-6">
                        <label for="end_date">@lang('messages.filter_by_date'):</label>
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-calendar"></i>
                            </span>
                            <input type="text" id="end_date" value="{{ @format_date('now') }}" class="form-control"
                                readonly>
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>
        <br>
        <div class="box box-solid">
            <div class="box-header print_section">
                <h3 class="box-title">{{ session()->get('business.name') }} - @lang('account.balance_sheet') - <span
                        id="hidden_date">{{ @format_date('now') }}</span></h3>
            </div>
            <div class="box-body">
                <table class="table table-border-center-col no-border table-pl-12" id="balance_sheet">
                    <thead>
                        <tr class="bg-gray">
                            <th>Balance Sheet</th>
                            <th>@lang('account.debit')</th>
                            <th>@lang('account.credit')</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><b>Assets</b></td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>
                    </tbody>
                    <tbody id="account_balances_details">
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray">
                            <th>@lang('sale.total')</th>
                            <td>
                                <span class="remote-data" id="total_credit">
                                    <i class="fas fa-sync fa-spin fa-fw"></i>
                                </span>
                            </td>
                            <td>
                                <span class="remote-data" id="total_debit">
                                    <i class="fas fa-sync fa-spin fa-fw"></i>
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="box-footer">
                <button type="button" class="btn btn-primary no-print pull-right"onclick="window.print()">
                    <i class="fa fa-print"></i> @lang('messages.print')</button>
            </div>
        </div>

    </section>
    <!-- /.content -->
@stop
@section('javascript')

    <script type="text/javascript">
        $(document).ready(function() {
            //Date picker
            $('#end_date').datepicker({
                autoclose: true,
                format: datepicker_date_format
            });
            update_balance_sheet();

            $('#end_date').change(function() {
                update_balance_sheet();
                $('#hidden_date').text($(this).val());
            });
            $('#bal_sheet_location_id').change(function() {
                update_balance_sheet();
            });
        });

        function update_balance_sheet() {
            var loader = '<i class="fas fa-sync fa-spin fa-fw"></i>';
            $('span.remote-data').each(function() {
                $(this).html(loader);
            });

            $('table#balance_sheet tbody#account_balances_details').html(
                '<tr><td colspan="3"><i class="fas fa-sync fa-spin fa-fw"></i></td></tr>');

            var end_date = $('input#end_date').val();
            var location_id = $('#bal_sheet_location_id').val()
            $.ajax({
                url: "{{ action('AccountReportsController@balanceSheet') }}?end_date=" + end_date +
                    '&location_id=' + location_id,
                dataType: "json",
                success: function(result) {
                    console.log(result)
                    $('span#supplier_due').text(__currency_trans_from_en(result.supplier_due, true));
                    __write_number($('input#hidden_supplier_due'), result.supplier_due);

                    $('span#customer_due').text(__currency_trans_from_en(result.customer_due, true));
                    __write_number($('input#hidden_customer_due'), result.customer_due);

                    $('span#closing_stock').text(__currency_trans_from_en(result.closing_stock, true));
                    __write_number($('input#hidden_closing_stock'), result.closing_stock);

                    var investments_row = '';
                    var Liabilities_row = '';
                    var account_balances = result.account_balances;
                    $('table#balance_sheet tbody#account_balances_details').html('');

                    for (var key in account_balances) {
                        if (key !== 'Sales' && key !== 'Purchase' && key !== 'Expense') {
                            if (account_balances.hasOwnProperty(key)) {
                                var accnt_bal = parseFloat(account_balances[key]);
                                var accnt_bal_with_sym = __currency_trans_from_en(Math.abs(account_balances[
                                    key]), true);
                                var debit_or_credit_class = getDebitOrCreditClass(key, accnt_bal);

                                // Temporarily store the Investments row to append it later
                                if(key === 'Account Payable'){
                                    Liabilities_row = '<tr><td class="pl-20-td">' + key +
                                        ':</td><td>&nbsp;</td><td><input type="hidden" class="' + debit_or_credit_class +
                                        '" value="' + Math.abs(accnt_bal) + '">' + accnt_bal_with_sym +
                                        '</td></tr>';
                                }else if(key === 'Investments') {
                                    investments_row = '<tr><td class="pl-20-td">' + key +
                                        ':</td><td>&nbsp;</td><td><input type="hidden" class="' + debit_or_credit_class +
                                        '" value="' + Math.abs(accnt_bal) + '">' + accnt_bal_with_sym +
                                        '</td></tr>';
                                } else {
                                    var account_tr;
                                    if (debit_or_credit_class == 'debit') {
                                        account_tr = '<tr><td class="pl-20-td">' + key +
                                            ':</td><td>&nbsp;</td><td><input type="hidden" class="' +
                                            debit_or_credit_class + '" value="' +
                                            Math.abs(accnt_bal) + '">' + accnt_bal_with_sym + '</td></tr>';
                                    } else {
                                        account_tr = '<tr><td class="pl-20-td">' + key +
                                            ':</td><td><input type="hidden" class="' + debit_or_credit_class +
                                            '" value="' + Math.abs(accnt_bal) + '">' + accnt_bal_with_sym +
                                            '</td><td>&nbsp;</td></tr>';
                                    }

                                    $('table#balance_sheet tbody#account_balances_details').append(account_tr);
                                }
                            }
                        }
                    }

                    // Append the Liablities row before Investments
                    $('table#balance_sheet tbody#account_balances_details').append(
                        '<tr><td><b>Liablities</b></td><td>&nbsp;</td><td>&nbsp;</td></tr>');

                    if (Liabilities_row !== '') {
                        $('table#balance_sheet tbody#account_balances_details').append(Liabilities_row);
                    }

                    // Append the Capital row before Investments
                    $('table#balance_sheet tbody#account_balances_details').append(
                        '<tr><td><b>Capital</b></td><td>&nbsp;</td><td>&nbsp;</td></tr>');
                        
                    // Append the Investments row last
                    if (investments_row !== '') {
                        $('table#balance_sheet tbody#account_balances_details').append(investments_row);
                    }


                    var capital_account_details = result.capital_account_details;
                    $('table#balance_sheet tbody#capital_account_balances').html('');
                    for (var key in capital_account_details) {
                        var accnt_bal = __currency_trans_from_en(result.capital_account_details[key]);
                        var accnt_bal_with_sym = __currency_trans_from_en(result.capital_account_details[key],
                            true);
                        var account_tr = '<tr><td class="pl-20-td">' + key +
                            ':</td><td><input type="hidden" class="asset" value="' + accnt_bal + '">' +
                            accnt_bal_with_sym + '</td></tr>';
                        $('table#assets_table tbody#capital_account_balances').append(account_tr);
                    }

                    var total_debit = 0;
                    var total_credit = 0;
                    $('input.debit').each(function() {
                        total_debit += __read_number($(this));
                    });
                    $('input.credit').each(function() {
                        total_credit += __read_number($(this));
                    });

                    $('span#total_debit').text(__currency_trans_from_en(total_debit, true));
                    $('span#total_credit').text(__currency_trans_from_en(total_credit, true));

                }
            });
        }

        function getDebitOrCreditClass(account_name, account_balance) {
            // Define logic to determine if the account should be debit or credit
            var debit_accounts = ['Cash In Hand', 'Account Receivable', 'Expense', 'Purchase'];
            var credit_accounts = ['Sales', 'Account Payable', 'Investments'];

            if (debit_accounts.includes(account_name)) {
                return 'credit';
            }
            if (credit_accounts.includes(account_name)) {
                return 'debit';
            }
            // Default case for other accounts not explicitly listed
            return account_balance >= 0 ? 'credit' : 'debit';
        }
    </script>

@endsection
