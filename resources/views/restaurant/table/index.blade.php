@extends('layouts.app')
@section('title', __('restaurant.tables'))
@section('css')
<style>
        .slider {
        display: flex;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch; /* Enables smooth scrolling on iOS */
        scroll-snap-align: start; /* Ensures the entire slider snaps to the beginning of each image */
        flex-direction: row; /* Ensure images are displayed horizontally */
    }

    .slider img {
        max-width: 100%;
        height: auto;
        /* Set specific dimensions for all images */
        width: 300px; /* Adjust the width as needed */
        height: 200px; /* Adjust the height as needed */
        object-fit: cover; /* Ensure the image covers the specified dimensions */
    }

</style>
@endsection
@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('restaurant.tables')
            <small>@lang('restaurant.manage_your_tables')</small>
        </h1>
        <!-- <ol class="breadcrumb">
                                                <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
                                                <li class="active">Here</li>
                                            </ol> -->
    </section>

    <!-- Main content -->
    <section class="content">

        <div class="box">
            <div class="box-header">
                <h3 class="box-title">@lang('restaurant.all_your_tables')</h3>
                @can('restaurant.create')
                    <div class="box-tools">
                        <button type="button" class="btn btn-block btn-primary btn-modal"
                            data-href="{{ action('Restaurant\TableController@create') }}" data-container=".tables_modal">
                            <i class="fa fa-plus"></i> @lang('messages.add')</button>
                    </div>
                @endcan
            </div>
            <div class="box-body">
                @can('restaurant.view')
                    <table class="table table-bordered table-striped" id="tables_table">
                        <thead>
                            <tr>
                                <th>@lang('restaurant.table')</th>
                                <th>@lang('purchase.business_location')</th>
                                <th>@lang('restaurant.description')</th>
                                <th>@lang('messages.action')</th>
                            </tr>
                        </thead>
                    </table>
                @endcan
            </div>
        </div>

        <div class="modal fade tables_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>

    </section>
    <!-- /.content -->

@endsection

@section('javascript')
<script type="text/javascript" src="//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {

        $(document).on('submit', 'form#table_add_form', function(e) {
            e.preventDefault();

            var formData = new FormData(this);

            $.ajax({
                method: "POST",
                url: $(this).attr("action"),
                dataType: "json",
                data: formData,
                contentType: false, // Prevent jQuery from setting content type
                processData: false, // Prevent jQuery from processing data
                success: function(result) {
                    if (result.success) {
                        $('div.tables_modal').modal('hide');
                        toastr.success(result.msg);
                        tables_table.ajax.reload();
                    } else {
                        // Handle validation errors
                        if (result.errors) {
                            // Iterate over validation errors and display them using toastr
                            $.each(result.errors, function(key, value) {
                                toastr.error(value[
                                    0
                                ]); // Display the first error message for each field
                            });
                        } else {
                            // Display a generic error message
                            toastr.error(result.msg);
                        }
                    }
                },
                error: function(jqXHR) {
                    if (jqXHR.responseJSON && jqXHR.responseJSON.errors) {
                        $('.btn-submit').removeAttr("disabled")
                        var errors = jqXHR.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            toastr.error(value[0]);
                        });
                    } else {
                        toastr.error('An error occurred while processing your request.');
                    }
                }

            });
        });
        //Brands table
        var tables_table = $('#tables_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '/modules/tables',
            columnDefs: [{
                "targets": 3,
                "orderable": false,
                "searchable": false
            }],
            columns: [{
                    data: 'name',
                    name: 'res_tables.name'
                },
                {
                    data: 'location',
                    name: 'BL.name'
                },
                {
                    data: 'description',
                    name: 'description'
                },
                {
                    data: 'action',
                    name: 'action'
                }
            ],
        });

        $(document).on('click', 'button.edit_table_button', function() {

            $("div.tables_modal").load($(this).data('href'), function() {

                $(this).modal('show');

                $('form#table_edit_form').submit(function(e) {
                    e.preventDefault();
                    var formData = new FormData(this);

                    $.ajax({
                        method: "POST",
                        url: $(this).attr("action"),
                        dataType: "json",
                        data: formData,
                        contentType: false, // Prevent jQuery from setting content type
                        processData: false, // Prevent jQuery from processing data
                        success: function(result) {
                            if (result.success == true) {
                                $('div.tables_modal').modal('hide');
                                toastr.success(result.msg);
                                tables_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                });
            });
        });

        $(document).on('click', 'button.delete_table_button', function() {
            swal({
                title: LANG.sure,
                text: LANG.confirm_delete_table,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    var href = $(this).data('href');
                    var data = $(this).serialize();

                    $.ajax({
                        method: "DELETE",
                        url: href,
                        dataType: "json",
                        data: data,
                        success: function(result) {
                            if (result.success == true) {
                                toastr.success(result.msg);
                                tables_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });

        $('.slider').slick({
            slidesToShow: 3, // Number of slides to show at a time
            slidesToScroll: 1, // Number of slides to scroll at a time
            autoplay: true, // Autoplay the slider
            autoplaySpeed: 2000, // Autoplay speed in milliseconds
            responsive: [
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 2
                    }
                },
                {
                    breakpoint: 480,
                    settings: {
                        slidesToShow: 1
                    }
                }
            ]
        });
    });
</script>
@endsection
